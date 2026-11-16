<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecheckFixesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Secret#12345', 'role' => $role]);
    }

    private function product(string $name, $expires): Product
    {
        return Product::create([
            'sku' => 'R-' . random_int(10000, 99999), 'name' => $name, 'category' => 'otc', 'unit' => 'tablet',
            'price' => 10, 'stock' => 20, 'expiration_date' => $expires,
        ]);
    }

    /** #1 Oras ng Pilipinas */
    public function test_app_uses_manila_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', now()->timezoneName);
        $this->assertSame('+08:00', now()->format('P'));
    }

    /** #2 Email na naka-CAPS ng ibang user: validation error, hindi 500 */
    public function test_changing_email_to_another_users_email_in_caps_is_a_validation_error(): void
    {
        $this->user('admin', 'a@test.ph');
        $b = $this->user('cashier', 'b@test.ph');

        $this->actingAs($b)->put('/settings', ['name' => 'B', 'email' => 'A@TEST.PH'])
            ->assertSessionHasErrors('email');

        $this->assertSame('b@test.ph', $b->fresh()->email);
    }

    public function test_settings_email_is_saved_in_lowercase(): void
    {
        $b = $this->user('cashier', 'b@test.ph');

        $this->actingAs($b)->put('/settings', ['name' => 'B', 'email' => 'NewB@Test.PH'])->assertSessionHasNoErrors();

        $this->assertSame('newb@test.ph', $b->fresh()->email);
    }

    /** #4 Hindi kasama ang expired sa Expiring Soon */
    public function test_expiring_soon_excludes_expired_products(): void
    {
        $this->product('Expired', now()->subDays(3));
        $this->product('Soon', now()->addDays(10));
        $this->product('Fine', now()->addYear());

        $this->assertSame(1, Product::expiringSoon()->count());
        $this->assertSame(1, Product::expired()->count());
        $this->assertSame(2, Product::expiringOrExpired()->count());
    }

    public function test_products_page_has_an_expired_filter(): void
    {
        $this->product('Old Med', now()->subDays(3));
        $this->product('Good Med', now()->addYear());

        $response = $this->actingAs($this->user('cashier', 'c@test.ph'))->get('/products?filter=expired');

        $response->assertOk()->assertSee('Old Med')->assertDontSee('Good Med');
    }

    /** #6 Walang doble sa Unit list */
    public function test_unit_suggestions_have_no_duplicates(): void
    {
        $html = $this->actingAs($this->user('admin', 'a@test.ph'))->get('/products/create')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<option value="sachet">'));
    }
}
