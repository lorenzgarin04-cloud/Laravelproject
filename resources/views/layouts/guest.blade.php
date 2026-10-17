<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · De Guzman Drugmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-mint text-body antialiased">
    <header class="flex items-center justify-between border-b border-line bg-white px-5 py-2 shadow-[0_1px_1px_rgba(0,0,0,.05)]">
        <a href="{{ route('login') }}" class="flex items-center gap-2">
            <x-logo class="h-8 w-8 rounded-lg object-cover object-top" />
            <span class="font-display text-lg font-bold text-ink">DE GUZMAN DRUGMART</span>
        </a>
    </header>

    <main class="relative flex flex-1 items-center justify-center overflow-hidden px-5 py-10">
        <div class="pointer-events-none absolute left-1/2 top-1/2 h-[720px] w-[720px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-br from-mint-2 via-mint-3 to-transparent opacity-40 blur-[32px]"></div>
        <div class="relative w-full max-w-[500px]">
            @yield('content')
        </div>
    </main>

    @hasSection('footer')
        @yield('footer')
    @endif

    @stack('scripts')
</body>
</html>
