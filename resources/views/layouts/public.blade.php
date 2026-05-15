<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="relative bg-[#f5f7fb] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen transition-colors duration-200 overflow-x-hidden">
    @include('layouts.partials.app-toasts')

    <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-[#f59e0b]/12 blur-3xl"></div>
    <div class="pointer-events-none absolute top-16 right-0 h-72 w-72 rounded-full bg-[#ec4899]/10 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-[#38bdf8]/10 blur-3xl"></div>

    <header class="sticky top-0 z-20 border-b border-[#dbe3ea] dark:border-[#3E3E3A] bg-white/85 dark:bg-[#161615]/95 backdrop-blur-sm">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 py-4 flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="text-sm font-medium text-[#334155] dark:text-[#EDEDEC] hover:text-[#f59e0b] transition-colors">
                {{ __('faq.back_to_login') }}
            </a>

            <div class="flex items-center gap-2">
                <button
                    id="public-theme-toggle"
                    type="button"
                    class="p-2 rounded-md text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors"
                    aria-label="Toggle theme"
                >
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <a href="{{ route('language.switch', 'kk') }}"
                    class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">Қаз</a>
                <a href="{{ route('language.switch', 'ru') }}"
                    class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">RU</a>
                <a href="{{ route('language.switch', 'en') }}"
                    class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">EN</a>
            </div>
        </div>
    </header>

    <main class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    <script>
        document.getElementById('public-theme-toggle')?.addEventListener('click', function () {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
</body>
</html>
