@extends('layouts.guest')
@section('title', 'Set your password')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <div class="h-1.5 bg-brand"></div>
    <div class="px-8 pb-8 pt-7">
        <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="shield" class="h-3 w-3" /> Admin Setup</span>
        <h1 class="mt-5 font-display text-[28px] font-bold text-ink">Set your password</h1>
        <p class="mt-2 break-words text-[15px] leading-6 text-body">Welcome, <b class="text-ink">{{ $user->name }}</b>. Choose a strong password for your admin account.</p>

        <form method="POST" action="{{ route('access.password', $token) }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="password" class="label">New Password</label>
                <x-password-input id="password" name="password" autocomplete="new-password" />
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
                <p class="hint">Minimum 10 characters, upper/lowercase, number &amp; symbol.</p>
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm Password</label>
                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            </div>
            <button type="submit" class="btn-primary w-full py-3 text-base"><x-icon name="check" class="h-4 w-4" /> Save password &amp; open dashboard</button>
        </form>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
