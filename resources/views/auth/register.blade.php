@extends('layouts.guest')
@section('title', 'Create Account')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white shadow-[0_8px_24px_rgba(0,106,65,.08)]" x-data="registerForm()">
    <div class="h-1.5 bg-brand-dark"></div>

    <div class="border-b border-mint-2 px-8 pb-6 pt-8">
        <x-logo class="h-auto w-24" />
        <h1 class="mt-6 font-display text-2xl font-bold text-ink">Create Account</h1>
        <p class="mt-1 text-sm text-body">Register a new pharmacist, cashier, or dispensary manager profile.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5 px-8 py-7">
        @csrf

        <div>
            <label for="name" class="label">Full Name <span class="text-danger">*</span></label>
            <div class="relative">
                <x-icon name="user" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="e.g. Maria Santos, RPh" class="input input-icon">
            </div>
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">Email <span class="text-danger">*</span></label>
            <div class="relative">
                <x-icon name="mail" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="e.g. msantos@deguzmandrugmart.ph" class="input input-icon">
            </div>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="label">Password <span class="text-danger">*</span></label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="password" name="password" :type="show ? 'text' : 'password'" x-ref="pw" x-model="password" @input="password = $event.target.value" @change="password = $event.target.value" required autocomplete="new-password" placeholder="••••••••••••" class="input input-icon pr-11 font-mono">
                    <button type="button" @click="show = show!" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-body hover:text-ink" aria-label="Toggle password visibility"><x-icon name="eye" class="h-5 w-5" /></button>
                </div>
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm Password <span class="text-danger">*</span></label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-body" />
                    <input id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required autocomplete="new-password" placeholder="••••••••••••" class="input input-icon font-mono">
                </div>
            </div>
        </div>
        @error('password') <p class="field-error -mt-3">{{ $message }}</p> @enderror

        {{-- Password strength --}}
        <div class="rounded-lg border border-brand/20 bg-mint-3 px-4 py-3">
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-1.5 text-muted"><x-icon name="shield" class="h-4 w-4" /> Password Strength</span>
                <span class="font-mono text-[13px] font-semibold" :class="score >= 4 ? 'text-brand-dark' : (score >= 2 ? 'text-warn' : 'text-muted')" x-text="label"></span>
            </div>
            <div class="mt-2 grid grid-cols-4 gap-1.5">
                <template x-for="i in 4"><span class="h-1.5 rounded-full" :class="i <= score ? 'bg-brand-dark' : 'bg-line/60'"></span></template>
            </div>
            <p class="mt-2 flex items-center gap-1.5 text-xs text-muted"><x-icon name="circle-check" class="h-3.5 w-3.5 text-brand" /> Minimum 10 characters, upper/lowercase, number &amp; symbol required.</p>
        </div>

        <label class="flex cursor-pointer items-start gap-3 text-[13px] leading-5 text-ink">
            <input type="checkbox" name="agree" value="1" @checked(old('agree')) class="mt-0.5 h-4 w-4 rounded border-hint text-brand-dark focus:ring-brand/30">
            <span>I confirm that all credentials and clinical dispensary authorizations are valid under current regulatory pharmacy governance.</span>
        </label>
        @error('agree') <p class="field-error -mt-3">{{ $message }}</p> @enderror

        <button type="submit" class="btn-primary w-full py-3 text-base"><x-icon name="check" class="h-4 w-4" /> Create Account</button>

        <p class="text-center text-sm text-body">Already registered? <a href="{{ route('login') }}" class="font-semibold text-brand-dark hover:underline">Log In</a></p>

        <p class="flex items-center justify-center gap-2 border-t border-mint-2 pt-4 font-mono text-xs text-muted">
            <x-icon name="shield-check" class="h-4 w-4 text-brand" /> GxP &amp; HIPAA Compliant Healthcare Protocol
        </p>
    </form>
</div>

@push('scripts')
<script>
function registerForm() {
    return {
        show: false, password: '',
        init() {
            // Sinasalo ang password na ini-autofill ng browser (Chrome Password Manager) na walang input event.
            setInterval(() => {
                const el = this.$refs.pw;
                if (el && el.value !== this.password) this.password = el.value;
            }, 400);
        },
        get score() {
            const p = this.password; if (!p) return 0;
            let s = 0;
            if (p.length >= 10) s++;
            if (/[a-z]/.test(p) && /[A-Z]/.test(p)) s++;
            if (/\d/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            return s;
        },
        get label() { return ['', 'Weak', 'Fair', 'Good', 'Strong Security (Clinical Standard)'][this.score] || 'Enter a password'; },
    };
}
</script>
@endpush
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
