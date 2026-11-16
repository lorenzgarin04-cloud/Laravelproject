<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeUser(): User
    {
        return User::create(['name' => 'Jian', 'email' => 'jian@gmail.com', 'password' => 'OldPass#12345', 'role' => 'cashier']);
    }

    public function test_code_is_emailed_and_can_reset_the_password(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => 'jian@gmail.com'])->assertRedirect();

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->post('/reset-password', [
            'email' => 'jian@gmail.com',
            'code' => $code,
            'password' => 'NewPass#98765',
            'password_confirmation' => 'NewPass#98765',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewPass#98765', $user->fresh()->password));
    }

    public function test_wrong_code_is_rejected_and_password_unchanged(): void
    {
        Mail::fake();
        $user = $this->makeUser();
        $this->post('/forgot-password', ['email' => 'jian@gmail.com']);

        $this->post('/reset-password', [
            'email' => 'jian@gmail.com',
            'code' => '000000',
            'password' => 'NewPass#98765',
            'password_confirmation' => 'NewPass#98765',
        ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('OldPass#12345', $user->fresh()->password));
    }

    public function test_unknown_email_does_not_send_mail_or_reveal_anything(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'nobody@gmail.com'])->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_change_password_in_settings_requires_correct_current_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'wrong',
            'password' => 'NewPass#98765',
            'password_confirmation' => 'NewPass#98765',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'OldPass#12345',
            'password' => 'NewPass#98765',
            'password_confirmation' => 'NewPass#98765',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPass#98765', $user->fresh()->password));
    }
}
