<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    public function edit()
    {
        $keys = array_keys(Setting::DEFAULTS);
        $values = collect($keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])->all();

        return view('settings.edit', ['s' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $account = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        $user->update($account);

        // Store info at thresholds: Admin lang.
        if ($user->isAdmin()) {
            $store = $request->validate([
                'store_name'       => ['required', 'string', 'max:120'],
                'store_address'    => ['required', 'string', 'max:255'],
                'store_phone'      => ['nullable', 'string', 'max:40'],
                'store_tin'        => ['nullable', 'string', 'max:40'],
                'low_stock_below'  => ['required', 'integer', 'min:0', 'max:100000'],
                'expiry_warn_days' => ['required', 'integer', 'min:1', 'max:3650'],
            ]);
            foreach ($store as $key => $value) {
                Setting::put($key, (string) $value);
            }
            Setting::put('email_digest', $request->boolean('email_digest') ? '1' : '0');
        }

        return back()->with('success', 'Settings saved.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password updated.');
    }
}
