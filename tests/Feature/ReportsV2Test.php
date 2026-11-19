<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportsV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@test.ph', 'password' => 'Secret#12345', 'role' => 'admin']);
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'V-' . random_int(10000, 99999), 'name' => 'Item ' . random_int(1, 99999), 'type' => 'generic',
            'category' => 'otc', 'unit' => 'tablet', 'price' => 10, 'stock' => 20, 'expiration_date' => now()->addYear(),
        ], $o));
    }

    private function sell(User $user, Product $p, int $qty, bool $senior = false): void
    {
        $this->actingAs($user)->postJson('/sales', [
            'items' => [['product_id' => $p->id, 'quantity' => $qty]], 'senior_pwd' => $senior, 'cash_received' => 1000,
        ])->assertOk();
    }

    /** Product Type */
    public function test_product_form_requires_a_type_and_rx_checkbox_sets_category(): void
    {
        $admin = $this->admin();
        $base = ['name' => 'Syrup', 'price' => 50, 'stock' => 5, 'expiration_date' => now()->addYear()->toDateString(), 'unit' => 'bottle'];

        $this->actingAs($admin)->post('/products', $base)->assertSessionHasErrors('type');
        $this->actingAs($admin)->post('/products', $base + ['type' => 'wrong'])->assertSessionHasErrors('type');

        $this->actingAs($admin)->post('/products', $base + ['type' => 'grocery'])->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Syrup', 'type' => 'grocery', 'category' => 'otc']);

        $this->actingAs($admin)->post('/products', array_merge($base, ['name' => 'Rx Med', 'type' => 'branded', 'requires_rx' => 1]))->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Rx Med', 'type' => 'branded', 'category' => 'rx']);
    }

    public function test_products_can_be_filtered_by_type(): void
    {
        $this->product(['name' => 'Alpha Branded', 'type' => 'branded']);
        $this->product(['name' => 'Beta Grocery', 'type' => 'grocery']);

        $this->actingAs($this->admin())->get('/products?filter=grocery')
            ->assertOk()->assertSee('Beta Grocery')->assertDontSee('Alpha Branded');
    }

    /** Daily Sales & Stock */
    public function test_daily_report_shows_end_of_day_stock_and_sold_amounts(): void
    {
        $admin = $this->admin();
        $p = $this->product(['price' => 10, 'stock' => 20]);
        $p->forceFill(['created_at' => now()->subDays(5)])->save(); // umiral na bago ang kahapon
        $this->product(['type' => 'grocery', 'name' => 'Milk']);

        $this->sell($admin, $p, 5);

        $today = $this->actingAs($admin)->get('/reports?type=daily&date=' . today()->toDateString())->assertOk()->viewData('daily');
        $row = collect($today['columns']['generic']['rows'])->firstWhere('id', $p->id);
        $this->assertSame(15, $row['stock']);
        $this->assertSame(5, $row['qty']);
        $this->assertEqualsWithDelta(50.0, $row['net'], 0.001);
        $this->assertEqualsWithDelta(50.0, $today['grand'], 0.001);

        // Kahapon: wala pang benta, kaya buo pa ang stock (15 + 5 na nabenta pagkatapos)
        $yesterday = $this->actingAs($admin)->get('/reports?type=daily&date=' . today()->subDay()->toDateString())->viewData('daily');
        $row = collect($yesterday['columns']['generic']['rows'])->firstWhere('id', $p->id);
        $this->assertSame(20, $row['stock']);
        $this->assertSame(0, $row['qty']);
    }

    public function test_daily_report_category_filter_and_export(): void
    {
        $admin = $this->admin();
        $this->product(['type' => 'grocery', 'name' => 'Milk']);

        $daily = $this->actingAs($admin)->get('/reports?type=daily&category=grocery')->assertOk()->viewData('daily');
        $this->assertSame(['grocery'], array_keys($daily['columns']));

        $this->actingAs($admin)->get('/reports/export?type=daily')->assertOk();
    }

    public function test_detailed_report_can_be_filtered_by_category(): void
    {
        $admin = $this->admin();
        $generic = $this->product(['name' => 'Gen Med']);
        $grocery = $this->product(['name' => 'Grocery Thing', 'type' => 'grocery']);
        $this->sell($admin, $generic, 1);
        $this->sell($admin, $grocery, 2);

        $query = '?type=detailed&from=' . today()->toDateString() . '&to=' . today()->toDateString();

        $this->actingAs($admin)->get('/reports' . $query . '&category=grocery')
            ->assertOk()->assertSee('Grocery Thing')->assertDontSee('Gen Med');
    }

    /** Analytics */
    public function test_analytics_pages_work_for_every_period_and_total_matches_sales(): void
    {
        $admin = $this->admin();
        $p = $this->product(['price' => 10]);
        $this->sell($admin, $p, 5); // ₱50

        foreach (['today', 'week', 'month', 'year'] as $period) {
            $this->actingAs($admin)->get('/reports/analytics?period=' . $period)->assertOk()->assertSee('Top 5 best sellers');
        }

        $response = $this->actingAs($admin)->get('/reports/analytics?period=week')->assertOk();
        $this->assertEqualsWithDelta(50.0, $response->viewData('now')['net']['all'], 0.001);

        $series = $response->viewData('chart')['series'];
        $this->assertCount(7, $series);
        $this->assertEqualsWithDelta(50.0, end($series)['total'], 0.001);
    }

    public function test_analytics_with_no_sales_does_not_break(): void
    {
        $this->actingAs($this->admin())->get('/reports/analytics?period=today')->assertOk()->assertSee('No sales in this period');
    }

    /** 403 page */
    public function test_cashier_sees_the_styled_403_page(): void
    {
        $cashier = User::create(['name' => 'Ana', 'email' => 'ana@test.ph', 'password' => 'Secret#12345', 'role' => 'cashier']);

        $this->actingAs($cashier)->get('/users')->assertForbidden()->assertSee('Admins only');
    }

    /** Walang maling "catalog is empty" kapag may produkto pero walang laman ang napiling filter */
    public function test_empty_filter_does_not_say_the_catalog_is_empty(): void
    {
        $this->product(['name' => 'Only Generic Item']);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/products?filter=grocery')
            ->assertOk()->assertSee('No Grocery products yet')->assertSee('View all products')->assertDontSee('Your catalog is empty');

        $this->actingAs($admin)->get('/products?q=zzzz')->assertOk()->assertSee('No results for');
    }

    public function test_truly_empty_catalog_shows_the_catalog_empty_message(): void
    {
        $this->actingAs($this->admin())->get('/products')->assertOk()->assertSee('Your catalog is empty');
    }
}
