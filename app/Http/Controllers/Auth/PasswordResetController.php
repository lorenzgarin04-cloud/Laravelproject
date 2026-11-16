<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_SECONDS = 60;

    /** Step 1: ilagay ang email */
    public function request()
    {
        return view('auth.forgot-password');
    }

    /** Step 2: gumawa at mag-send ng 6-digit code sa email (Gmail SMTP) */
    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));

        // Cooldown sa lahat ng email (para hindi malaman kung registered o hindi)
        $cooldownKey = 'pwreset:cooldown:' . sha1($email);
        if (! Cache::add($cooldownKey, 1, self::RESEND_SECONDS)) {
            return redirect()->route('password.reset', ['email' => $email])
                ->withErrors(['code' => 'Please wait a minute before requesting another code.']);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_codes')->updateOrInsert(
                ['email' => $user->email],
                [
                    'code_hash'  => Hash::make($code),
                    'attempts'   => 0,
                    'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            try {
                Mail::to($user->email)->send(new PasswordResetCodeMail($user, $code, self::TTL_MINUTES));
            } catch (Throwable $e) {
                report($e);
                DB::table('password_reset_codes')->where('email', $user->email)->delete();
                Cache::forget($cooldownKey);

                return back()->withInput()->withErrors([
                    'email' => 'We could not send the email. Please check the Gmail settings in your .env file (see README) and try again.',
                ]);
            }
        }

        return redirect()->route('password.reset', ['email' => $email])
            ->with('status', 'If that email is registered, a 6-digit code has been sent. It expires in ' . self::TTL_MINUTES . ' minutes. Check your Inbox or Spam.');
    }

    /** Step 3: ilagay ang code at bagong password */
    public function showReset(Request $request)
    {
        $email = (string) $request->query('email', '');
        if ($email === '') {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', ['email' => $email]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()->symbols()],
        ], [
            'code.digits' => 'The code must be exactly 6 digits.',
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::whereRaw('lower(email) = ?', [$email])->first();
        $record = $user ? DB::table('password_reset_codes')->where('email', $user->email)->first() : null;

        $fail = fn (string $message) => back()->withInput($request->only('email'))->withErrors(['code' => $message]);

        if (! $record) {
            return $fail('Invalid or expired code. Please request a new one.');
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('password_reset_codes')->where('email', $user->email)->delete();
            return $fail('This code has expired. Please request a new one.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_codes')->where('email', $user->email)->delete();
            return $fail('Too many wrong attempts. Please request a new code.');
        }

        if (! Hash::check($data['code'], $record->code_hash)) {
            DB::table('password_reset_codes')->where('email', $user->email)->increment('attempts');
            return $fail('Incorrect code. Please check your email and try again.');
        }

        $user->forceFill([
            'password'       => $data['password'], // naka-hash sa model cast
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_codes')->where('email', $user->email)->delete();
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Password changed successfully. You can now log in.');
    }
}
