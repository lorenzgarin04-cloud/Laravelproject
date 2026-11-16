<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'TST-' . random_int(10000, 99999),
            'name' => 'Test Paracetamol',
            'category' => 'otc',
            'unit' => 'tablet',
            'price' => 10.00,
            'stock' => 20,
            'expiration_date' => now()->addYear(),
        ], $overrides));
    }

    private function cashier(): User
    {
        return User::create(['name' => 'Cashier', 'email' => 'c@test.ph', 'password' => 'Secret#12345', 'role' => 'cashier']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_sale_decrements_stock_and_computes_senior_discount(): void
    {
        $product = $this->makeProduct();

        $response = $this->actingAs($this->cashier())->postJson('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'senior_pwd' => true,
            'cash_received' => 100,
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);

        $sale = Sale::first();
        $this->assertEquals('50.00', $sale->subtotal);
        $this->assertEquals('10.00', $sale->discount);   // 20%
        $this->assertEquals('40.00', $sale->total);
        $this->assertEquals('60.00', $sale->change_due);
        $this->assertSame(15, $product->fresh()->stock);
    }

    public function test_sale_is_rejected_when_cash_is_not_enough(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->cashier())->postJson('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'cash_received' => 10,
        ])->assertStatus(422);

        $this->assertSame(0, Sale::count());
        $this->assertSame(20, $product->fresh()->stock);
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct(['stock' => 2]);

        $this->actingAs($this->cashier())->postJson('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'cash_received' => 500,
        ])->assertStatus(422);

        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_expired_products_cannot_be_sold(): void
    {
        $product = $this->makeProduct(['expiration_date' => now()->subDay()]);

        $this->actingAs($this->cashier())->postJson('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'cash_received' => 500,
        ])->assertStatus(422);
    }

    public function test_only_admin_can_delete_products(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->cashier())->delete("/products/{$product->id}")->assertForbidden();

        $admin = User::create(['name' => 'Admin', 'email' => 'a@test.ph', 'password' => 'Secret#12345', 'role' => 'admin']);
        $this->actingAs($admin)->delete("/products/{$product->id}")->assertRedirect('/products');
        $this->assertSoftDeleted($product);
    }
}
