@extends('layouts.guest')
@section('title', 'Link expired')

@section('content')
<div class="overflow-hidden rounded-xl border border-line bg-white px-8 py-10 text-center shadow-[0_8px_24px_rgba(0,106,65,.08)]">
    <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-danger-bg text-danger"><x-icon name="lock" class="h-7 w-7" /></span>
    <h1 class="mt-5 font-display text-2xl font-bold text-ink">This link is no longer valid</h1>
    <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-body">Access links work only once and expire after {{ \App\Services\AccessLink::TTL_HOURS }} hours. Request a new one, or ask your administrator to send it again.</p>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('access.request') }}" class="btn-primary"><x-icon name="mail" class="h-4 w-4" /> Get a new link</a>
        <a href="{{ route('login') }}" class="btn-outline">Back to Login</a>
    </div>
</div>
@endsection

@section('footer')
    @include('partials.auth-footer')
@endsection
