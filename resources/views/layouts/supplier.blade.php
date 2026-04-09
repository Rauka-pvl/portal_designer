<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('supplier-portal.title')) - {{ config('app.name', 'Laravel') }}</title>
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
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen transition-colors duration-200">
    @include('layouts.partials.app-toasts')

    <header class="border-b border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white/80 dark:bg-[#161615]/90 backdrop-blur-sm sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('supplier.index') }}" class="text-lg font-semibold tracking-tight text-[#0f172a] dark:text-[#EDEDEC]">
                {{ __('supplier-portal.title') }}
            </a>
            <div class="flex items-center gap-3">
                <div class="flex gap-1 text-xs">
                    <a href="{{ route('language.switch', 'kk') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A]' }}">Қаз</a>
                    <a href="{{ route('language.switch', 'ru') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A]' }}">RU</a>
                    <a href="{{ route('language.switch', 'en') }}" class="px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A]' }}">EN</a>
                </div>
                <button type="button" id="supplier-theme-toggle" class="p-2 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]" aria-label="Toggle theme">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                        {{ __('dashboard.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        @yield('content')
    </main>

    <script>
        document.getElementById('supplier-theme-toggle')?.addEventListener('click', function() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
    @stack('scripts')
</body>
</html>
