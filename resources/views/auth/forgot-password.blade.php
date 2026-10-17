@extends('layouts.guest')
@section('title', 'Forgot Password')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <div class="h-1.5 bg-brand"></div>
    <div class="px-8 pb-6 pt-7">
        <div class="flex items-center justify-between">
            <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="shield" class="h-3 w-3" /> Security Recovery</span>
            <span class="font-mono text-xs text-muted">PROTO-RX #409-B</span>
        </div>

        <h1 class="mt-6 font-display text-[28px] font-bold text-ink">Forgot Password</h1>
        <p class="mt-2 text-[15px] leading-6 text-body">Enter your registered dispensary email and we will send you a secure verification link to reset your password.</p>

        @if (session('status'))
            <div class="mt-5 rounded-lg border border-brand/25 bg-mint-3 px-4 py-3 text-sm font-medium text-brand-dark">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="email" class="text-sm font-semibold text-ink">Dispensary Email Address <span class="text-danger">*</span></label>
                    <span class="font-mono text-[11px] text-muted">Encrypted (TLS 1.3)</span>
                </div>
                <div class="relative">
                    <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="e.g. staff@deguzmandrugmart.ph" class="input input-icon">
                </div>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
                <p class="mt-2 flex items-center gap-1.5 text-xs text-muted"><x-icon name="info" class="h-3.5 w-3.5" /> A recovery token will be dispatched to your pharmacy admin email.</p>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base">Send Reset Link <x-icon name="send" class="h-4 w-4" /></button>
            <a href="{{ route('login') }}" class="btn-outline w-full py-3 text-base text-body"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Login</a>
        </form>

        <div class="mt-6 flex gap-3 rounded-lg border border-line bg-mint-3 p-4">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-mint-2 text-brand-dark"><x-icon name="help" class="h-5 w-5" /></span>
            <div class="text-[13px] leading-5 text-body">
                <p class="font-bold text-ink">Urgent Dispensary Roster Issue?</p>
                <p>Need urgent dispensary access? Contact the dispensary supervisor or IT help desk at local <span class="font-mono font-bold text-brand-dark">ext. #102</span>.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between border-t border-mint-2 pt-4 font-mono text-[11px] uppercase text-muted">
            <span class="flex items-center gap-1.5"><x-icon name="shield" class="h-3.5 w-3.5" /> 256-bit dispensary encryption</span>
            <span>Status: zero exposure</span>
        </div>
    </div>
</div>
<p class="mt-5 text-center text-xs text-muted">Need device workstation provisioning? <span class="font-semibold text-brand-dark">Contact Pharmacy Systems Operations</span></p>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
