<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BugFixesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'cashier', string $email = 'u@test.ph'): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Secret#12345', 'role' => $role]);
    }

    private function product(array $o = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'T-' . random_int(10000, 99999), 'name' => 'Test Med', 'category' => 'otc', 'unit' => 'tablet',
            'price' => 10, 'stock' => 20, 'expiration_date' => now()->addYear(),
        ], $o));
    }

    /** #1 */
    public function test_selling_a_deleted_product_returns_a_clean_error(): void
    {
        $p = $this->product();
        $p->delete();

        $this->actingAs($this->user())->postJson('/sales', [
            'items' => [['product_id' => $p->id, 'quantity' => 1]], 'cash_received' => 100,
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.product_id');
    }

    /** #2 */
    public function test_cashier_cannot_create_edit_or_change_stock(): void
    {
        $cashier = $this->user();
        $p = $this->product();

        $this->actingAs($cashier)->get('/products/create')->assertForbidden();
        $this->actingAs($cashier)->get("/products/{$p->id}/edit")->assertForbidden();
        $this->actingAs($cashier)->patchJson("/products/{$p->id}/stock", ['delta' => 1])->assertForbidden();
        $this->actingAs($cashier)->put("/products/{$p->id}", ['name' => 'x', 'price' => 0, 'stock' => 999, 'expiration_date' => '2030-01-01', 'category' => 'otc', 'unit' => 'box'])->assertForbidden();

        $this->actingAs($cashier)->get('/products')->assertOk();
        $this->actingAs($cashier)->get("/products/{$p->id}")->assertOk();
        $this->assertSame(20, $p->fresh()->stock);
    }

    public function test_admin_can_adjust_stock(): void
    {
        $p = $this->product();

        $this->actingAs($this->user('admin'))->patchJson("/products/{$p->id}/stock", ['delta' => 1])->assertOk();
        $this->assertSame(21, $p->fresh()->stock);
    }

    /** #3 */
    public function test_register_is_closed_once_a_user_exists(): void
    {
        $this->user('admin');

        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@test.ph', 'password' => 'Secret#12345', 'password_confirmation' => 'Secret#12345', 'agree' => 1])->assertNotFound();
        $this->assertSame(1, User::count());
    }

    public function test_admin_can_create_accounts_but_cashier_cannot(): void
    {
        $payload = ['name' => 'New Cashier', 'email' => 'New@Test.PH', 'role' => 'cashier', 'password' => 'Secret#12345'];

        $this->actingAs($this->user('cashier'))->post('/users', $payload)->assertForbidden();

        $this->actingAs($this->user('admin', 'admin@test.ph'))->post('/users', $payload)->assertRedirect();
        $this->assertSame('cashier', User::where('email', 'new@test.ph')->value('role'));
    }

    /** #4 */
    public function test_product_requires_a_unit_and_saves_subtitle(): void
    {
        $admin = $this->user('admin');
        $base = ['name' => 'Syrup', 'price' => 50, 'stock' => 5, 'expiration_date' => now()->addYear()->toDateString(), 'category' => 'otc'];

        $this->actingAs($admin)->post('/products', $base)->assertSessionHasErrors('unit');

        $this->actingAs($admin)->post('/products', $base + ['unit' => 'bottle', 'subtitle' => 'Cough'])->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Syrup', 'unit' => 'bottle', 'subtitle' => 'Cough']);
    }

    /** #7 */
    public function test_expired_product_status(): void
    {
        $this->assertSame('expired', $this->product(['expiration_date' => now()->subDays(5)])->status);
        $this->assertSame('expiring', $this->product(['expiration_date' => now()->addDays(10)])->status);
        $this->assertSame('in_stock', $this->product(['expiration_date' => now()->addYear()])->status);
    }

    /** #8 */
    public function test_login_ignores_email_capitalization(): void
    {
        DB::table('users')->insert([
            'name' => 'Juan', 'email' => 'Juan@Test.ph', 'password' => Hash::make('Secret#12345'),
            'role' => 'cashier', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post('/login', ['email' => 'juan@test.ph', 'password' => 'Secret#12345'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** #9 */
    public function test_rx_items_need_prescription_verification(): void
    {
        $rx = $this->product(['category' => 'rx']);
        $cashier = $this->user();
        $items = [['product_id' => $rx->id, 'quantity' => 1]];

        $this->actingAs($cashier)->postJson('/sales', ['items' => $items, 'cash_received' => 100])
            ->assertStatus(422)->assertJsonValidationErrors('prescription_verified');
        $this->assertSame(0, Sale::count());

        $this->actingAs($cashier)->postJson('/sales', ['items' => $items, 'cash_received' => 100, 'prescription_verified' => true])->assertOk();
        $this->assertTrue((bool) Sale::first()->prescription_verified);
    }
}
