<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function user(string $role = 'admin', string $email = 'a@test.ph'): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Secret#12345', 'role' => $role]);
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'F-' . random_int(10000, 99999), 'name' => 'Med ' . random_int(1, 99999), 'category' => 'otc', 'unit' => 'tablet',
            'price' => 10, 'stock' => 50, 'expiration_date' => now()->addYear(),
        ], $o));
    }

    /** Bawal ibenta ang gamot na 30 araw (o mas malapit) na lang bago mag-expire */
    public function test_products_expiring_within_30_days_cannot_be_sold(): void
    {
        $cashier = $this->user('cashier', 'c@test.ph');

        foreach ([0, 1, 7, 29, 30] as $days) {
            $p = $this->product(['expiration_date' => today()->addDays($days)]);

            $this->actingAs($cashier)->postJson('/sales', [
                'items' => [['product_id' => $p->id, 'quantity' => 1]], 'cash_received' => 100,
            ])->assertStatus(422);

            $this->assertSame(50, $p->fresh()->stock, "Expiring in {$days} days should not be sold");
        }
    }

    public function test_products_expiring_in_more_than_30_days_can_be_sold(): void
    {
        $p = $this->product(['expiration_date' => today()->addDays(31)]);

        $this->actingAs($this->user('cashier', 'c@test.ph'))->postJson('/sales', [
            'items' => [['product_id' => $p->id, 'quantity' => 1]], 'cash_received' => 100,
        ])->assertOk();

        $this->assertSame(49, $p->fresh()->stock);
    }

    public function test_pos_search_hides_products_that_expire_within_30_days(): void
    {
        $this->product(['name' => 'Soon Expiring Med', 'expiration_date' => today()->addDays(10)]);
        $this->product(['name' => 'Fresh Med', 'expiration_date' => today()->addDays(200)]);

        $this->actingAs($this->user('cashier', 'c@test.ph'))->getJson('/sales/search?q=Med')
            ->assertOk()->assertJsonCount(1)->assertJsonFragment(['name' => 'Fresh Med']);
    }

    /** #2 Critical = low stock + expiring + expired */
    public function test_critical_badge_counts_every_alert(): void
    {
        foreach (range(1, 5) as $i) {
            $this->product(['stock' => 3]); // low stock
        }
        $this->product(['expiration_date' => now()->addDays(10)]);  // expiring soon
        $this->product(['expiration_date' => now()->subDays(2)]);   // expired
        $this->product(); // maayos

        $this->actingAs($this->user())->get('/dashboard')->assertOk()->assertSee('7 Critical');
    }

    /** #3 +/- stock: bawat pindot ay may epekto */
    public function test_stock_buttons_are_atomic_and_never_go_below_zero(): void
    {
        $admin = $this->user();
        $p = $this->product(['stock' => 1]);

        $this->actingAs($admin)->patchJson("/products/{$p->id}/stock", ['delta' => 1])->assertOk()->assertJson(['stock' => 2]);
        $this->actingAs($admin)->patchJson("/products/{$p->id}/stock", ['delta' => 1])->assertOk()->assertJson(['stock' => 3]);

        foreach (range(1, 5) as $i) {
            $this->actingAs($admin)->patchJson("/products/{$p->id}/stock", ['delta' => -1])->assertOk();
        }
        $this->assertSame(0, $p->fresh()->stock);
    }

    /** #4 Ang discount ay hinati sa bawat linya at tugma sa Revenue */
    public function test_senior_discount_is_allocated_per_line_and_matches_revenue(): void
    {
        $a = $this->product(['price' => 10.01]);
        $b = $this->product(['price' => 10.02]);

        $this->actingAs($this->user('cashier', 'c@test.ph'))->postJson('/sales', [
            'items' => [['product_id' => $a->id, 'quantity' => 1], ['product_id' => $b->id, 'quantity' => 1]],
            'senior_pwd' => true, 'cash_received' => 100,
        ])->assertOk();

        $sale = Sale::first();
        $items = SaleItem::all();

        $this->assertEqualsWithDelta((float) $sale->discount, (float) $items->sum('discount'), 0.001);
        $this->assertEqualsWithDelta((float) $sale->total, (float) $items->sum(fn ($i) => $i->line_total - $i->discount), 0.001);
    }

    public function test_reports_show_discount_and_net_columns_and_export_works(): void
    {
        $p = $this->product();
        $admin = $this->user();

        $this->actingAs($admin)->postJson('/sales', [
            'items' => [['product_id' => $p->id, 'quantity' => 5]], 'senior_pwd' => true, 'cash_received' => 100,
        ])->assertOk();

        $query = '?type=detailed&from=' . today()->subDay()->toDateString() . '&to=' . today()->toDateString();

        $this->actingAs($admin)->get('/reports' . $query)->assertOk()->assertSee('Discount')->assertSee('Net')->assertSee('₱40.00');
        $this->actingAs($admin)->get('/reports/export' . $query)->assertOk();
    }

    /** #5 Hindi na nire-reset ng seeder ang password ng admin */
    public function test_seeding_again_does_not_reset_admin_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@deguzmandrugmart.ph')->firstOrFail();
        $admin->update(['password' => 'Changed#Pass123']);

        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('Changed#Pass123', $admin->fresh()->password));
        $this->assertSame(1, User::where('email', 'admin@deguzmandrugmart.ph')->count());
    }
}
