@extends('layouts.guest')
@section('title', 'Cashier Access Link')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <div class="h-1.5 bg-brand"></div>
    <div class="px-8 pb-7 pt-7">
        <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><x-icon name="mail" class="h-3 w-3" /> Cashier Access</span>
        <h1 class="mt-5 font-display text-[28px] font-bold text-ink">Get an access link</h1>
        <p class="mt-2 text-[15px] leading-6 text-body">Cashiers do not use a password. Enter the email your administrator registered and we will send a one-time link to open your dashboard.</p>

        @if (session('status'))
            <div class="mt-5 rounded-lg border border-brand/25 bg-mint-3 px-4 py-3 text-sm font-medium text-brand-dark">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('access.send') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="label">Your Email <span class="text-danger">*</span></label>
                <div class="relative">
                    <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="e.g. yourname@gmail.com" class="input input-icon">
                </div>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
                <p class="mt-2 flex items-center gap-1.5 text-xs text-muted"><x-icon name="info" class="h-3.5 w-3.5" /> The link works once and expires in {{ \App\Services\AccessLink::TTL_HOURS }} hours. Check Spam if you do not see it.</p>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-base">Email me the link <x-icon name="send" class="h-4 w-4" /></button>
            <a href="{{ route('login') }}" class="btn-outline w-full py-3 text-base text-body"><x-icon name="arrow-left" class="h-4 w-4" /> Back to Login</a>
        </form>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
