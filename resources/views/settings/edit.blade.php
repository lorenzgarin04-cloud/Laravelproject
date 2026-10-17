@extends('layouts.app')
@section('title', 'Settings')
@section('crumb-parent', 'Settings')
@section('crumb-parent-url', route('settings.edit'))
@section('crumb', 'Store Profile & System Preferences')

@section('content')
@php
    $admin = auth()->user()->isAdmin();
    $lock = $admin ? '' : 'disabled';
    $pwErrors = $errors->has('current_password') || $errors->has('password');
@endphp
<div class="mx-auto max-w-[760px]" x-data="{ pw: {{ $pwErrors ? 'true' : 'false' }} }">
    <span class="pill-green font-mono text-[11px] uppercase tracking-wider"><span class="h-1.5 w-1.5 rounded-full bg-brand-dark"></span> System Configuration</span>
    <h1 class="mt-2 font-display text-[32px] font-bold leading-tight text-ink">Settings</h1>
    <p class="mt-1 text-sm text-body">Manage your pharmacy account, store profile, and inventory notification thresholds.</p>

    <form method="POST" action="{{ route('settings.update') }}" class="mt-7 space-y-6">
        @csrf @method('PUT')

        {{-- Account --}}
        <section class="card p-7">
            <header class="flex items-center gap-3 border-b border-mint-2 pb-4">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="user" class="h-5 w-5" /></span>
                <div><h2 class="font-display text-lg font-bold text-ink">Your Account</h2><p class="text-xs text-muted">Personal access credentials and administrative identity</p></div>
            </header>
            <div class="mt-5 space-y-5">
                <div>
                    <label for="name" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">Your Full Name</label>
                    <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required class="input py-2.5 text-sm">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="input py-2.5 text-sm">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button type="button" @click="pw = !pw; $nextTick(() => $refs.pwcard && $refs.pwcard.scrollIntoView({ behavior: 'smooth', block: 'center' }))" class="btn-outline btn-sm px-4! py-2.5!">
                    <x-icon name="rotate" class="h-4 w-4 text-brand" /> Change Password
                </button>
            </div>
        </section>

        {{-- Store --}}
        <section class="card p-7">
            <header class="flex items-center gap-3 border-b border-mint-2 pb-4">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="store" class="h-5 w-5" /></span>
                <div><h2 class="font-display text-lg font-bold text-ink">Store Information</h2><p class="text-xs text-muted">Official pharmacy outlet registry and contact details</p></div>
            </header>
            @unless ($admin) <p class="mt-4 rounded-lg border border-warn-line bg-warn-bg px-3 py-2 text-xs text-warn">Only an administrator can edit store information and alert thresholds.</p> @endunless
            <div class="mt-5 space-y-5">
                <div>
                    <label for="store_name" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">Pharmacy Store Name</label>
                    <input id="store_name" name="store_name" value="{{ old('store_name', $s['store_name']) }}" {{ $lock }} class="input py-2.5 text-sm disabled:bg-mint-3 disabled:text-muted">
                    @error('store_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="store_address" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">Complete Street Address</label>
                    <input id="store_address" name="store_address" value="{{ old('store_address', $s['store_address']) }}" {{ $lock }} class="input py-2.5 text-sm disabled:bg-mint-3 disabled:text-muted">
                    @error('store_address') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="store_phone" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">Store Phone Number</label>
                        <input id="store_phone" name="store_phone" value="{{ old('store_phone', $s['store_phone']) }}" {{ $lock }} class="input py-2.5 text-sm disabled:bg-mint-3 disabled:text-muted">
                    </div>
                    <div>
                        <label for="store_tin" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-ink">BIR VAT Reg TIN</label>
                        <input id="store_tin" name="store_tin" value="{{ old('store_tin', $s['store_tin']) }}" {{ $lock }} class="input py-2.5 text-sm disabled:bg-mint-3 disabled:text-muted">
                    </div>
                </div>
            </div>
        </section>

        {{-- Alerts --}}
        <section class="card p-7">
            <header class="flex items-center gap-3 border-b border-mint-2 pb-4">
                <span class="grid h-10 w-10 place-items-center rounded-lg bg-mint-2 text-brand-dark"><x-icon name="bell" class="h-5 w-5" /></span>
                <div><h2 class="font-display text-lg font-bold text-ink">Alerts &amp; Inventory Thresholds</h2><p class="text-xs text-muted">Automated dispensatory stock warnings and compliance prompts</p></div>
            </header>
            <div class="mt-5 space-y-3">
                <div class="flex items-center justify-between gap-4 rounded-xl border border-mint-2 bg-mint-3 p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-mint-2 bg-white text-brand-dark"><x-icon name="archive" class="h-4 w-4" /></span>
                        <div><p class="text-sm font-bold text-ink">Warn me when stock is below</p><p class="text-xs text-muted">Triggers low inventory indicator on POS screen</p></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input name="low_stock_below" type="number" min="0" value="{{ old('low_stock_below', $s['low_stock_below']) }}" {{ $lock }} class="input w-20 py-2 text-center font-display text-base font-bold">
                        <span class="text-[11px] font-bold uppercase text-muted">Units</span>
                    </div>
                </div>
                @error('low_stock_below') <p class="field-error">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between gap-4 rounded-xl border border-mint-2 bg-mint-3 p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-mint-2 bg-white text-brand-dark"><x-icon name="calendar-x" class="h-4 w-4" /></span>
                        <div><p class="text-sm font-bold text-ink">Warn me before a product expires</p><p class="text-xs text-muted">Prompts recall or clearance verification</p></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input name="expiry_warn_days" type="number" min="1" value="{{ old('expiry_warn_days', $s['expiry_warn_days']) }}" {{ $lock }} class="input w-20 py-2 text-center font-display text-base font-bold">
                        <span class="text-[11px] font-bold uppercase text-muted">Days</span>
                    </div>
                </div>
                @error('expiry_warn_days') <p class="field-error">{{ $message }}</p> @enderror

                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-mint-2 bg-mint-3 p-4" x-data="{ on: {{ old('email_digest', $s['email_digest']) ? 'true' : 'false' }} }">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-lg border border-mint-2 bg-white text-brand-dark"><x-icon name="mail" class="h-4 w-4" /></span>
                        <div><p class="text-sm font-bold text-ink">Email notification digest for daily sales</p><p class="text-xs text-muted">Delivered every day at 10:00 PM to registered email</p></div>
                    </div>
                    <input type="checkbox" name="email_digest" value="1" x-model="on" {{ $lock }} class="sr-only">
                    <span class="relative h-6 w-11 shrink-0 rounded-full transition" :class="on ? 'bg-brand' : 'bg-line'">
                        <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all" :class="on ? 'left-[22px]' : 'left-0.5'"></span>
                    </span>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-mint-2 pt-5">
            <p class="text-xs text-muted">De Guzman Drugmart POS System • Version 1.0</p>
            <div class="flex gap-3">
                <a href="{{ route('settings.edit') }}" class="btn-outline btn-sm px-5! py-2.5! text-body">Discard</a>
                <button type="submit" class="btn-primary btn-sm px-6! py-2.5! shadow-md"><x-icon name="save" class="h-4 w-4" /> Save Changes</button>
            </div>
        </div>
    </form>

    {{-- Change password (hiwalay na form) --}}
    <form method="POST" action="{{ route('settings.password') }}" x-show="pw" x-cloak x-ref="pwcard" x-transition class="card mt-6 space-y-4 p-7">
        @csrf @method('PUT')
        <h2 class="flex items-center gap-2 font-display text-lg font-bold text-ink"><x-icon name="lock" class="h-5 w-5 text-brand-dark" /> Change Password</h2>
        <div>
            <label for="current_password" class="label">Current Password</label>
            <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="input">
            @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="new_password" class="label">New Password</label>
                <input id="new_password" name="password" type="password" autocomplete="new-password" required class="input">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="new_password_confirmation" class="label">Confirm New Password</label>
                <input id="new_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="input">
            </div>
        </div>
        <p class="hint">Minimum 10 characters, upper/lowercase, number &amp; symbol.</p>
        <div class="flex justify-end gap-3">
            <button type="button" @click="pw = false" class="btn-outline btn-sm px-5!">Cancel</button>
            <button type="submit" class="btn-primary btn-sm px-6!">Update Password</button>
        </div>
    </form>
</div>
@endsection
