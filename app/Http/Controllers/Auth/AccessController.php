<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccessLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AccessController extends Controller
{
    /** Pindot sa link sa Gmail: Cashier = diretso sa Dashboard; Admin = itakda ang password. */
    public function show(Request $request, string $token)
    {
        $record = AccessLink::find($token);
        $user = $record ? User::find($record->user_id) : null;

        if (! $record || ! $user) {
            return response()->view('auth.access-invalid', [], 410);
        }

        // Kung may ibang naka-login sa browser na ito, i-logout muna
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($user->isAdmin()) {
            return view('auth.set-password', ['token' => $token, 'user' => $user]);
        }

        AccessLink::markUsed($record->id);
        Auth::login($user, true); // "remember" para hindi agad mag-expire ang session
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome, ' . $user->name . '!');
    }

    /** Admin: itakda ang sariling password gamit ang link. */
    public function setPassword(Request $request, string $token): RedirectResponse
    {
        $record = AccessLink::find($token);
        $user = $record ? User::find($record->user_id) : null;

        if (! $record || ! $user || ! $user->isAdmin()) {
            return redirect()->route('login')->withErrors(['email' => 'This link is invalid or has expired. Ask an administrator to send a new one.']);
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        AccessLink::markUsed($record->id);

        if (Auth::check()) {
            Auth::logout();
        }
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Password saved. Welcome, ' . $user->name . '!');
    }

    /** Cashier: humingi ng bagong link sa sarili nilang email. */
    public function requestForm()
    {
        return view('auth.access-request');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = Str::lower(trim($data['email']));

        // Cooldown sa lahat ng email para hindi malaman kung registered o hindi
        if (! Cache::add('access:cooldown:' . sha1($email), 1, 60)) {
            return back()->withInput()->withErrors(['email' => 'Please wait a minute before requesting another link.']);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if ($user && ! $user->isAdmin()) {
            try {
                AccessLink::send($user);
            } catch (Throwable $e) {
                report($e);
                Cache::forget('access:cooldown:' . sha1($email));

                return back()->withInput()->withErrors(['email' => 'We could not send the email right now. Please tell your administrator.']);
            }
        }

        return back()->with('status', 'If that email belongs to a cashier account, an access link has been sent. Check your Inbox or Spam.');
    }
}
