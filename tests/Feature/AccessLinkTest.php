<?php

namespace Tests\Feature;

use App\Mail\AccessLinkMail;
use App\Models\User;
use App\Services\AccessLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccessLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function user(string $role, string $email): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Secret#12345', 'role' => $role]);
    }

    public function test_users_page_has_no_password_field(): void
    {
        $this->actingAs($this->user('admin', 'a@test.ph'))->get('/users')
            ->assertOk()->assertDontSee('Temporary Password')->assertSee('Create &amp; Email Link', false);
    }

    public function test_admin_creating_a_cashier_emails_an_access_link_and_sets_no_known_password(): void
    {
        Mail::fake();

        $this->actingAs($this->user('admin', 'a@test.ph'))
            ->post('/users', ['name' => 'Ana', 'email' => 'Ana@Gmail.com', 'role' => 'cashier'])->assertRedirect();

        $ana = User::where('email', 'ana@gmail.com')->firstOrFail();
        $this->assertSame('cashier', $ana->role);

        Mail::assertSent(AccessLinkMail::class, fn (AccessLinkMail $m) => $m->hasTo('ana@gmail.com') && str_contains($m->url, '/access/'));
        $this->assertDatabaseCount('access_tokens', 1);
    }

    public function test_cashier_cannot_sign_in_with_the_login_form(): void
    {
        $this->user('cashier', 'c@test.ph');

        $this->post('/login', ['email' => 'c@test.ph', 'password' => 'Secret#12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_still_sign_in_with_the_login_form(): void
    {
        $this->user('admin', 'a@test.ph');

        $this->post('/login', ['email' => 'a@test.ph', 'password' => 'Secret#12345'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_cashier_link_logs_in_straight_to_the_dashboard_and_works_only_once(): void
    {
        $cashier = $this->user('cashier', 'c@test.ph');
        $token = AccessLink::issue($cashier);

        $this->get("/access/{$token}")->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($cashier);

        auth()->logout();
        $this->get("/access/{$token}")->assertStatus(410);
        $this->assertGuest();
    }

    public function test_expired_or_fake_links_are_rejected(): void
    {
        $cashier = $this->user('cashier', 'c@test.ph');
        $token = AccessLink::issue($cashier);
        DB::table('access_tokens')->update(['expires_at' => now()->subMinute()]);

        $this->get("/access/{$token}")->assertStatus(410);
        $this->get('/access/' . str_repeat('x', 64))->assertStatus(410);
        $this->assertGuest();
    }

    public function test_a_new_link_cancels_the_old_one(): void
    {
        $cashier = $this->user('cashier', 'c@test.ph');
        $old = AccessLink::issue($cashier);
        $new = AccessLink::issue($cashier);

        $this->get("/access/{$old}")->assertStatus(410);
        $this->get("/access/{$new}")->assertRedirect('/dashboard');
    }

    public function test_new_admin_sets_a_password_from_the_link(): void
    {
        $admin = $this->user('admin', 'new-admin@test.ph');
        $token = AccessLink::issue($admin);

        $this->get("/access/{$token}")->assertOk()->assertSee('Set your password');

        $this->post("/access/{$token}", ['password' => 'Brand#New12345', 'password_confirmation' => 'Brand#New12345'])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(Hash::check('Brand#New12345', $admin->fresh()->password));
        $this->get("/access/{$token}")->assertStatus(410);
    }

    public function test_cashier_can_request_a_new_link_but_admin_or_unknown_emails_get_nothing(): void
    {
        Mail::fake();
        $this->user('cashier', 'c@test.ph');
        $this->user('admin', 'a@test.ph');

        $this->post('/access/request', ['email' => 'c@test.ph'])->assertSessionHas('status');
        Mail::assertSent(AccessLinkMail::class, 1);

        $this->post('/access/request', ['email' => 'a@test.ph'])->assertSessionHas('status');
        $this->post('/access/request', ['email' => 'nobody@test.ph'])->assertSessionHas('status');
        Mail::assertSent(AccessLinkMail::class, 1);
    }

    public function test_admin_can_resend_a_link_and_cashier_cannot(): void
    {
        Mail::fake();
        $cashier = $this->user('cashier', 'c@test.ph');

        $this->actingAs($cashier)->post("/users/{$cashier->id}/link")->assertForbidden();
        $this->actingAs($this->user('admin', 'a@test.ph'))->post("/users/{$cashier->id}/link")->assertRedirect();

        Mail::assertSent(AccessLinkMail::class, fn ($m) => $m->hasTo('c@test.ph'));
    }

    public function test_cashier_has_no_password_screens(): void
    {
        $cashier = $this->user('cashier', 'c@test.ph');

        $this->actingAs($cashier)->get('/settings')->assertOk()->assertDontSee('Change Password');
        $this->actingAs($cashier)->put('/settings/password', ['current_password' => 'x', 'password' => 'Abcdef#12345', 'password_confirmation' => 'Abcdef#12345'])->assertForbidden();
    }

    public function test_forgot_password_does_not_send_codes_to_cashiers(): void
    {
        Mail::fake();
        $this->user('cashier', 'c@test.ph');

        $this->post('/forgot-password', ['email' => 'c@test.ph'])->assertRedirect();

        Mail::assertNothingSent();
    }
}
