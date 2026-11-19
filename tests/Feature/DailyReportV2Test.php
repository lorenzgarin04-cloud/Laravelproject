<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DailyReportV2Test extends TestCase
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
            'sku' => 'D-' . random_int(10000, 99999), 'name' => 'Item ' . random_int(1, 99999), 'type' => 'generic',
            'category' => 'otc', 'unit' => 'tablet', 'price' => 10, 'stock' => 20, 'expiration_date' => now()->addYear(),
        ], $o));
    }

    public function test_reports_page_is_now_the_daily_report(): void
    {
        $this->product();

        $this->actingAs($this->admin())->get('/reports')->assertOk()
            ->assertSee('Daily Report')->assertSee('Last stock')->assertSee('Added / Removed')->assertSee('Stock left')
            ->assertDontSee('Sales &amp; Stock', false);
    }

    public function test_sales_and_manual_changes_are_tracked_in_last_stock_sold_added_and_stock_left(): void
    {
        $admin = $this->admin();
        $p = $this->product(['stock' => 20]);

        $this->actingAs($admin)->postJson('/sales', [
            'items' => [['product_id' => $p->id, 'quantity' => 5]], 'cash_received' => 100,
        ])->assertOk();
        $this->actingAs($admin)->patchJson("/products/{$p->id}/stock", ['delta' => 1])->assertOk();

        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'type' => 'sale', 'quantity' => -5, 'stock_after' => 15]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $p->id, 'type' => 'adjust', 'quantity' => 1, 'stock_after' => 16]);

        $daily = $this->actingAs($admin)->get('/reports?type=daily&date=' . today()->toDateString())->assertOk()->viewData('daily');
        $row = collect($daily['columns']['generic']['rows'])->firstWhere('id', $p->id);

        $this->assertSame(20, $row['last']);
        $this->assertSame(5, $row['qty']);
        $this->assertSame(1, $row['added']);
        $this->assertSame(16, $row['stock']);
    }

    public function test_new_products_and_stock_edits_are_logged(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', [
            'name' => 'Logged Item', 'price' => 10, 'stock' => 10, 'expiration_date' => now()->addYear()->toDateString(),
            'type' => 'grocery', 'unit' => 'pack',
        ])->assertRedirect();

        $product = Product::where('name', 'Logged Item')->firstOrFail();
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'initial', 'quantity' => 10]);

        $this->actingAs($admin)->put("/products/{$product->id}", [
            'name' => 'Logged Item', 'price' => 10, 'stock' => 7, 'expiration_date' => now()->addYear()->toDateString(),
            'type' => 'grocery', 'unit' => 'pack',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'adjust', 'quantity' => -3, 'stock_after' => 7]);
    }

    public function test_a_product_did_not_exist_before_it_was_created(): void
    {
        $p = $this->product();

        $daily = $this->actingAs($this->admin())->get('/reports?type=daily&date=' . today()->subDays(10)->toDateString())->viewData('daily');

        $this->assertNull(collect($daily['columns']['generic']['rows'])->firstWhere('id', $p->id));
    }

    public function test_dashboard_has_todays_sales_by_type_and_still_has_recent_transactions(): void
    {
        $admin = $this->admin();
        $p = $this->product(['type' => 'branded', 'price' => 10]);
        $this->actingAs($admin)->postJson('/sales', ['items' => [['product_id' => $p->id, 'quantity' => 2]], 'cash_received' => 100])->assertOk();

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee("Today's Sales by Type", false)->assertSee('Recent Transactions')->assertSee("Today's Report", false)
            ->assertSee('View Daily Report');
    }

    public function test_pos_says_available_products(): void
    {
        $this->product();

        $this->actingAs($this->admin())->get('/sales')->assertOk()->assertSee('Available Products')->assertDontSee('Available Medicines');
    }
}
