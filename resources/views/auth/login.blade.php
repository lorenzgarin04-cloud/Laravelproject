@extends('layouts.guest')
@section('title', 'Log In')

@section('content')
<div class="space-y-4">
    <div class="rounded-xl border border-line bg-white p-9 shadow-[0_1px_1px_rgba(0,0,0,.05)]">
        <div class="flex flex-col items-center text-center">
            <x-logo class="h-auto w-56 rounded-xl" />
            <h1 class="mt-3 font-display text-[32px] font-bold leading-10 tracking-[-0.8px] text-ink">Welcome Back</h1>
            <p class="mt-1 max-w-sm text-sm leading-[22px] text-body">Log in to access dispensary management, inventory, and POS terminal.</p>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-lg border border-brand/25 bg-mint-3 px-4 py-3 text-sm font-medium text-brand-dark">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <div class="relative">
                    <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           placeholder="e.g. staff@deguzmandrugmart.ph" class="input input-icon">
                </div>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <x-password-input id="password" name="password" placeholder="Enter your password" />
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pb-1 pt-1 text-sm">
                <label class="flex cursor-pointer items-center gap-2.5 text-body">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-hint text-brand focus:ring-brand/30"> Remember me
                </label>
                <a href="{{ route('password.request') }}" class="font-semibold text-brand-dark hover:underline">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary w-full bg-brand-dark! py-3.5 text-base hover:bg-brand-deep!">
                Log In <x-icon name="arrow-right" class="h-4 w-4" />
            </button>
        </form>

        @if (config('dg.registration_enabled'))
            <div class="relative mt-7 text-center">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-line"></div></div>
                <span class="relative bg-white px-3 text-xs text-muted">Workstation Registration</span>
            </div>
            <p class="mt-5 text-center text-sm text-body">Don't have an account?
                <a href="{{ route('register') }}" class="font-semibold text-brand-dark hover:underline">Create an Account</a>
            </p>
        @endif

        <div class="mt-6 flex items-center justify-center gap-2 border-t border-mint-2 pt-5">
            <span class="h-2 w-2 rounded-full bg-brand-dark"></span>
            <span class="text-center font-mono text-[13px] font-medium tracking-wide text-muted">Verified Medical Terminal • 256-bit Encrypted Connection</span>
        </div>
    </div>

    <div class="flex items-center justify-between px-2 text-xs text-muted">
        <span>Station ID: RX-POS-URDANETA-04</span>
        <span class="font-mono">Secure Port: TLS 1.3</span>
    </div>
</div>
@endsection
