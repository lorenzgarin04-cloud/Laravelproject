@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')
@section('title', 'Access Denied')
@section('crumb-parent', 'Access')
@section('crumb-parent-url', url('/dashboard'))
@section('crumb', 'Access Denied')

@section('content')
<div class="mx-auto flex min-h-[60vh] max-w-xl items-center justify-center">
    <div class="card w-full px-8 py-10 text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-danger-bg text-danger"><x-icon name="lock" class="h-7 w-7" /></span>
        <p class="mt-4 font-display text-5xl font-extrabold text-danger">403</p>
        <h1 class="mt-2 font-display text-xl font-bold text-ink">Admins only</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-body">
            @auth
                Your {{ ucfirst(auth()->user()->role) }} account can record sales and view products, but cannot manage products, users, or store settings.
            @else
                You do not have permission to view this page.
            @endauth
        </p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn-primary mt-6"><x-icon name="arrow-left" class="h-4 w-4" /> {{ auth()->check() ? 'Back to Dashboard' : 'Back to Login' }}</a>
    </div>
</div>
@endsection
