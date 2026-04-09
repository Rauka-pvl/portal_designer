<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            var t = localStorage.getItem('theme') || 'light';
            if (t === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        })();
    </script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 font-sans antialiased text-[#1b1b18] dark:text-[#EDEDEC]
    bg-[radial-gradient(ellipse_120%_80%_at_50%_-20%,rgba(245,158,11,0.14),transparent_55%),radial-gradient(ellipse_90%_60%_at_100%_50%,rgba(236,72,153,0.08),transparent_50%),radial-gradient(ellipse_80%_50%_at_0%_80%,rgba(99,102,241,0.07),transparent_45%),#f8f7f4]
    dark:bg-[radial-gradient(ellipse_120%_80%_at_50%_-20%,rgba(245,158,11,0.12),transparent_55%),radial-gradient(ellipse_90%_60%_at_100%_40%,rgba(139,92,246,0.1),transparent_50%),#070708]">

    @include('layouts.partials.app-toasts')

    <div class="w-full max-w-[19.5rem] sm:max-w-[20.5rem]">
        <div class="relative rounded-2xl border border-[#19140035] dark:border-[#3E3E3A]
            bg-white/90 dark:bg-[#161615]/95 backdrop-blur-md
            shadow-[0_20px_50px_-18px_rgba(15,23,42,0.2),0_0_0_1px_rgba(255,255,255,0.06)_inset]
            dark:shadow-[0_24px_56px_-20px_rgba(0,0,0,0.65),inset_0_1px_0_0_rgba(255,255,255,0.04)]
            p-4 sm:p-5">

            <div class="flex items-center justify-between gap-2 mb-4">
                <div class="flex gap-1 text-[10px] font-medium">
                    <a href="{{ route('language.switch', 'kk') }}" class="px-1.5 py-0.5 rounded {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">Қаз</a>
                    <a href="{{ route('language.switch', 'ru') }}" class="px-1.5 py-0.5 rounded {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">RU</a>
                    <a href="{{ route('language.switch', 'en') }}" class="px-1.5 py-0.5 rounded {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">EN</a>
                </div>
                <button type="button" id="welcome-theme-toggle" class="p-1.5 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-black/5 dark:hover:bg-white/5 transition-colors" aria-label="Theme">
                    <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>
            </div>

            <div class="text-center mb-4">
                <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-amber-600 dark:text-amber-500/90">{{ config('app.name') }}</p>
                <h1 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC] mt-1.5 leading-tight">{{ __('welcome.portal_entry') }}</h1>
                <p class="text-[11px] leading-snug text-[#64748b] dark:text-[#A1A09A] mt-1.5 px-1">{{ __('welcome.portal_entry_hint') }}</p>
                <div class="mx-auto mt-3 h-px w-12 rounded-full bg-gradient-to-r from-amber-500 via-rose-500 to-indigo-500 opacity-80"></div>
            </div>

            <div class="space-y-2">
                <a href="{{ route('login', ['as' => 'supplier']) }}"
                    class="group flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition-all duration-200
                    bg-gradient-to-br from-amber-500/12 via-rose-500/8 to-fuchsia-500/10 dark:from-amber-500/8 dark:via-rose-500/5 dark:to-fuchsia-500/8
                    shadow-[0_0_0_1px_rgba(245,158,11,0.28),0_2px_8px_-2px_rgba(236,72,153,0.2)]
                    dark:shadow-[0_0_0_1px_rgba(245,158,11,0.22),0_4px_16px_-6px_rgba(0,0,0,0.45)]
                    hover:shadow-[0_0_0_1px_rgba(245,158,11,0.45),0_6px_16px_-4px_rgba(236,72,153,0.28)]
                    hover:-translate-y-px active:translate-y-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-rose-600 text-white shadow-sm">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('auth_labels.im_supplier') }}</span>
                        <span class="block text-[10px] text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('auth_labels.login_supplier') }} →</span>
                    </span>
                    <svg class="w-4 h-4 shrink-0 text-amber-500/80 group-hover:text-amber-500 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('login') }}"
                    class="group flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left transition-all duration-200
                    bg-gradient-to-br from-slate-500/10 via-indigo-500/8 to-violet-500/10 dark:from-slate-500/6 dark:via-indigo-500/5 dark:to-violet-500/7
                    shadow-[0_0_0_1px_rgba(99,102,241,0.28),0_2px_8px_-2px_rgba(139,92,246,0.18)]
                    dark:shadow-[0_0_0_1px_rgba(99,102,241,0.22),0_4px_16px_-6px_rgba(0,0,0,0.45)]
                    hover:shadow-[0_0_0_1px_rgba(99,102,241,0.45),0_6px_16px_-4px_rgba(139,92,246,0.25)]
                    hover:-translate-y-px active:translate-y-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-slate-600 to-indigo-600 text-white shadow-sm">
                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('auth_labels.im_designer') }}</span>
                        <span class="block text-[10px] text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('auth_labels.login_designer') }} →</span>
                    </span>
                    <svg class="w-4 h-4 shrink-0 text-indigo-500/80 group-hover:text-indigo-400 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <p class="mt-4 pt-3 border-t border-[#19140035]/25 dark:border-[#3E3E3A] text-center text-[10px] text-[#706f6c] dark:text-[#A1A09A] leading-relaxed">
                <a href="{{ route('register') }}" class="text-[#f53003] dark:text-[#FF4433] hover:underline font-medium">{{ __('auth_labels.register') }}</a>
                <span class="mx-1.5 opacity-40">·</span>
                <a href="{{ route('register', ['as' => 'supplier']) }}" class="text-[#f53003] dark:text-[#FF4433] hover:underline font-medium">{{ __('auth_labels.register_supplier') }}</a>
            </p>
        </div>
    </div>

    <script>
        document.getElementById('welcome-theme-toggle')?.addEventListener('click', function () {
            var html = document.documentElement;
            var dark = html.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
</body>
</html>
