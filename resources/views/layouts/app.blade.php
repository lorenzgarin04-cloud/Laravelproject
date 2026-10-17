<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · De Guzman Drugmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-mint font-sans text-body antialiased" x-data="{ nav: false }">

    {{-- ========== SIDEBAR ========== --}}
    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-ink/40 lg:hidden"></div>
    <aside :class="nav ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-mint-2 bg-white transition-transform">
        <a href="{{ route('dashboard') }}" class="flex justify-center px-6 pb-4 pt-8">
            <x-logo class="h-auto w-44" />
        </a>

        <nav class="mt-4 flex-1 space-y-1.5 px-3.5" aria-label="Main">
            @php
                $links = [
                    ['dashboard', 'Dashboard', 'grid',     request()->routeIs('dashboard')],
                    ['products.index', 'Products', 'pharmacy', request()->routeIs('products.*')],
                    ['sales.create', 'Sales', 'receipt',   request()->routeIs('sales.*')],
                    ['reports.index', 'Reports', 'chart',  request()->routeIs('reports.*')],
                    ['settings.edit', 'Settings', 'gear',  request()->routeIs('settings.*')],
                ];
            @endphp
            @foreach ($links as [$route, $label, $icon, $active])
                <a href="{{ route($route) }}" @class(['nav-link', 'nav-link-active' => $active]) @if($active) aria-current="page" @endif>
                    <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- ========== MAIN ========== --}}
    <div class="lg:pl-60">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-mint-2 bg-white/95 px-5 py-3 backdrop-blur sm:px-8">
            <div class="flex items-center gap-3 text-sm">
                <button type="button" class="rounded-lg p-1.5 text-ink hover:bg-mint lg:hidden" @click="nav = true" aria-label="Open menu">
                    <x-icon name="menu" />
                </button>
                <nav class="flex items-center gap-2" aria-label="Breadcrumb">
                    @hasSection('crumb-parent')
                        <a href="{{ trim($__env->yieldContent('crumb-parent-url', '#')) }}" class="text-muted hover:text-ink">@yield('crumb-parent')</a>
                        <span class="text-line">/</span>
                    @endif
                    <span class="font-semibold text-ink">@yield('crumb', 'Overview')</span>
                </nav>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2.5 rounded-full border border-mint-2 bg-mint-3 py-1 pl-1 pr-4">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-brand font-display text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-[13px] font-bold text-ink">{{ auth()->user()->name }}</span>
                        <span class="block text-[10px] text-muted">{{ auth()->user()->title }}</span>
                    </span>
                </div>
                <span class="hidden h-7 w-px bg-line sm:block"></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex items-center gap-2 text-sm font-medium text-ink hover:text-brand-dark">
                        <x-icon name="logout" class="h-4 w-4" /> <span class="hidden sm:inline">Log Out</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="px-5 py-8 sm:px-8">
            @yield('content')
        </main>
    </div>

    {{-- ========== TOAST ========== --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4500)" x-show="show" x-transition x-cloak
             class="fixed right-5 top-5 z-50 flex items-center gap-3 rounded-xl border border-brand/25 bg-white px-4 py-3 shadow-lg" role="status">
            <span class="grid h-7 w-7 place-items-center rounded-full bg-brand text-white"><x-icon name="check" class="h-4 w-4" /></span>
            <span class="text-sm font-semibold text-ink">{{ session('success') }}</span>
            <button @click="show = false" class="ml-2 text-muted hover:text-ink" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4" /></button>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
