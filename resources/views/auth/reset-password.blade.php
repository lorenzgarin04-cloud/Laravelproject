@extends('layouts.guest')
@section('title', 'Enter Code')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <div class="h-1.5 bg-brand"></div>
    <div class="px-8 pb-8 pt-7">
        <div class="flex items-center justify-between">
            <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="shield" class="h-3 w-3" /> Security Recovery</span>
            <span class="font-mono text-xs text-muted">Step 2 of 2</span>
        </div>

        <h1 class="mt-5 font-display text-[28px] font-bold text-ink">Enter Your Code</h1>
        <p class="mt-2 break-words text-[15px] leading-6 text-body">We sent a 6-digit code to <b class="text-ink">{{ $email }}</b>. Enter it below and choose a new password.</p>

        @if (session('status'))
            <div class="mt-5 rounded-lg border border-brand/25 bg-mint-3 px-4 py-3 text-sm font-medium text-brand-dark">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="email" value="{{ old('email', $email) }}">

            <div>
                <label for="code" class="label">6-digit Code <span class="text-danger">*</span></label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus
                       value="{{ old('code') }}" placeholder="••••••" class="input text-center font-mono text-2xl font-bold tracking-[0.6em]"
                       oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)">
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">New Password <span class="text-danger">*</span></label>
                <x-password-input id="password" name="password" autocomplete="new-password" />
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
                <p class="hint">Minimum 10 characters, upper/lowercase, number &amp; symbol.</p>
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm New Password <span class="text-danger">*</span></label>
                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base"><x-icon name="check" class="h-4 w-4" /> Change Password</button>
        </form>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-mint-2 pt-4 text-sm">
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button class="font-semibold text-brand-dark hover:underline">Resend code</button>
            </form>
            <a href="{{ route('login') }}" class="flex items-center gap-1.5 text-body hover:text-ink"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Login</a>
        </div>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
