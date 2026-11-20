<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductFormV3Test extends TestCase
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

    private function payload(array $o = []): array
    {
        return array_merge([
            'name' => 'Napkin Pack', 'price' => 25, 'stock' => 30, 'unit' => 'pack', 'type' => 'grocery',
            'expiration_date' => now()->addYear()->toDateString(),
        ], $o);
    }

    public function test_grocery_can_be_saved_without_an_expiry_and_stays_sellable(): void
    {
        $admin = $this->admin();
        $data = $this->payload(['no_expiry' => 1]);
        unset($data['expiration_date']);

        $this->actingAs($admin)->post('/products', $data)->assertRedirect();

        $p = Product::where('name', 'Napkin Pack')->firstOrFail();
        $this->assertNull($p->expiration_date);
        $this->assertFalse($p->isExpired());
        $this->assertFalse($p->isExpiringSoon());
        $this->assertTrue($p->isSellable());
        $this->assertSame('in_stock', $p->status);
        $this->assertSame(0, Product::expired()->count() + Product::expiringSoon()->count());

        // Mabebenta sa POS at lumalabas sa listahan
        $this->actingAs($admin)->getJson('/sales/search?q=Napkin')->assertOk()->assertJsonCount(1);
        $this->actingAs($admin)->postJson('/sales', ['items' => [['product_id' => $p->id, 'quantity' => 2]], 'cash_received' => 100])->assertOk();
        $this->assertSame(28, $p->fresh()->stock);

        // Daily Report: "No expiry" at hindi expired
        $daily = $this->actingAs($admin)->get('/reports?type=daily&date=' . today()->toDateString())->assertOk()->viewData('daily');
        $row = collect($daily['columns']['grocery']['rows'])->firstWhere('id', $p->id);
        $this->assertNull($row['expires']);
        $this->assertFalse($row['expired']);

        $this->actingAs($admin)->get("/products/{$p->id}")->assertOk()->assertSee('No expiry');
        $this->actingAs($admin)->get('/reports/export?type=daily')->assertOk();
    }

    public function test_only_grocery_may_skip_the_expiry_date(): void
    {
        $data = $this->payload(['type' => 'branded', 'no_expiry' => 1]);
        unset($data['expiration_date']);

        $this->actingAs($this->admin())->post('/products', $data)->assertSessionHasErrors('expiration_date');
        $this->assertSame(0, Product::count());
    }

    public function test_medicines_need_a_date_and_grocery_without_no_expiry_needs_one_too(): void
    {
        $admin = $this->admin();

        $generic = $this->payload(['type' => 'generic']);
        unset($generic['expiration_date']);
        $this->actingAs($admin)->post('/products', $generic)->assertSessionHasErrors('expiration_date');

        $grocery = $this->payload();
        unset($grocery['expiration_date']);
        $this->actingAs($admin)->post('/products', $grocery)->assertSessionHasErrors('expiration_date');
    }

    public function test_rx_is_for_medicines_only(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/products', $this->payload(['name' => 'Soap', 'type' => 'grocery', 'requires_rx' => 1]))->assertRedirect();
        $this->assertSame('otc', Product::where('name', 'Soap')->value('category'));

        $this->actingAs($admin)->post('/products', $this->payload(['name' => 'Antibiotic', 'type' => 'generic', 'requires_rx' => 1]))->assertRedirect();
        $this->assertSame('rx', Product::where('name', 'Antibiotic')->value('category'));
    }

    public function test_editing_can_switch_between_no_expiry_and_a_date(): void
    {
        $admin = $this->admin();
        $data = $this->payload(['no_expiry' => 1]);
        unset($data['expiration_date']);
        $this->actingAs($admin)->post('/products', $data);
        $p = Product::firstOrFail();

        // Gawing Branded nang walang petsa = error
        $branded = $data;
        unset($branded['no_expiry']);
        $branded['type'] = 'branded';
        $this->actingAs($admin)->put("/products/{$p->id}", $branded)->assertSessionHasErrors('expiration_date');

        // May petsa na = ok
        $branded['expiration_date'] = now()->addYear()->toDateString();
        $this->actingAs($admin)->put("/products/{$p->id}", $branded)->assertRedirect();
        $this->assertNotNull($p->fresh()->expiration_date);
    }

    public function test_product_form_has_unit_select_no_expiry_and_rx(): void
    {
        $this->actingAs($this->admin())->get('/products/create')->assertOk()
            ->assertSee('Per piece')->assertSee('Per bottle')->assertSee('No expiry')->assertSee('Needs a prescription (Rx)');
    }

    public function test_dashboard_stat_cards_are_links_to_the_right_pages(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('products.index') . '" class="card group', $html);
        $this->assertStringContainsString('href="' . route('products.index', ['filter' => 'low']) . '" class="card group', $html);
        $this->assertStringContainsString('href="' . route('products.index', ['filter' => 'expiring']) . '" class="card group', $html);
        $this->assertStringContainsString('href="' . route('reports.index') . '" class="card group', $html);
    }

    public function test_cashier_settings_has_no_password_text(): void
    {
        $cashier = User::create(['name' => 'Ana', 'email' => 'ana@test.ph', 'password' => 'Secret#12345', 'role' => 'cashier']);

        $this->actingAs($cashier)->get('/settings')->assertOk()
            ->assertDontSee('Change Password')->assertDontSee('Password reset codes')->assertSee('Your access link is sent to this email.');

        $this->actingAs($this->admin())->get('/settings')->assertOk()->assertSee('Change Password');
    }
}
