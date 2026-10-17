@extends('layouts.guest')
@section('title', 'Reset Password')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <div class="h-1.5 bg-brand"></div>
    <div class="px-8 pb-8 pt-7">
        <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="shield" class="h-3 w-3" /> Security Recovery</span>
        <h1 class="mt-5 font-display text-[28px] font-bold text-ink">Reset Password</h1>
        <p class="mt-2 text-[15px] leading-6 text-body">Choose a new strong password for your account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4" x-data="{ show: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="label">Email Address</label>
                <div class="relative">
                    <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="input input-icon">
                </div>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">New Password</label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="new-password" class="input input-icon pr-11 font-mono">
                    <button type="button" @click="show = show!" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-body hover:text-ink" aria-label="Toggle password visibility"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
                <p class="hint">Minimum 10 characters, upper/lowercase, number &amp; symbol.</p>
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm New Password</label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required autocomplete="new-password" class="input input-icon font-mono">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base"><x-icon name="check" class="h-4 w-4" /> Reset Password</button>
            <a href="{{ route('login') }}" class="btn-outline w-full py-3 text-base text-body"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Login</a>
        </form>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
