<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        abort_unless(config('dg.registration_enabled'), 404);

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('dg.registration_enabled'), 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
            'agree'    => ['accepted'],
        ], [
            'agree.accepted' => 'Please confirm the authorization statement to continue.',
        ]);

        // Ang pinakaunang account ay Admin; ang iba ay Cashier muna.
        $isFirst = ! User::query()->exists();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $isFirst ? 'admin' : 'cashier',
            'title'    => $isFirst ? 'Dispensary Lead' : 'Cashier',
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Account created. Welcome to De Guzman Drugmart!');
    }
}
