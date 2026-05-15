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
    @stack('styles')
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

    <aside id="supplier-sidebar"
        class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-[#161615] border-r border-[#7c8799] dark:border-[#3E3E3A] transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">
        <div class="flex flex-col h-full">
            <div class="p-6 border-b border-[#7c8799] dark:border-[#3E3E3A]">
                <a href="{{ route('supplier.index') }}" class="block w-full max-w-[200px]">
                    <svg viewBox="0 0 400 120" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto text-[#0f172a] dark:text-[#EDEDEC]">
                        <defs>
                            <linearGradient id="supplierSidebarGoldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#ef4444;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <rect width="400" height="120" fill="transparent" />
                        <g transform="translate(30, 20)">
                            <rect x="5" y="5" width="70" height="70" rx="8" fill="none"
                                stroke="url(#supplierSidebarGoldGradient)" stroke-width="4" />
                            <rect x="15" y="15" width="50" height="50" rx="6" fill="none"
                                stroke="url(#supplierSidebarGoldGradient)" stroke-width="3" opacity="0.6">
                                <animateTransform attributeName="transform" type="rotate" from="0 40 40" to="360 40 40"
                                    dur="20s" repeatCount="indefinite" />
                            </rect>
                            <polygon points="40,30 50,40 40,50 30,40" fill="url(#supplierSidebarGoldGradient)" opacity="0.8">
                                <animateTransform attributeName="transform" type="rotate" from="0 40 40" to="360 40 40"
                                    dur="10s" repeatCount="indefinite" />
                            </polygon>
                        </g>
                        <text x="120" y="45" font-family="'Arial Black', sans-serif" font-size="32" font-weight="900"
                            fill="currentColor" letter-spacing="1">ПОРТАЛ</text>
                        <text x="120" y="75" font-family="Arial, sans-serif" font-size="18" font-weight="400"
                            fill="currentColor" letter-spacing="4">{{ __('supplier-portal.sidebar_brand') }}</text>
                    </svg>
                </a>
            </div>

            <nav class="flex-1 p-4 overflow-y-auto">
                <a href="{{ route('supplier.index') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1 {{ request()->routeIs('supplier.index') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ __('supplier-portal.nav_dashboard') }}</span>
                </a>
                <a href="{{ route('supplier.orders') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1 {{ request()->routeIs('supplier.orders') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>{{ __('supplier-portal.nav_orders') }}</span>
                </a>
                <a href="{{ route('supplier.company') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1 {{ request()->routeIs('supplier.company') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>{{ __('supplier-portal.nav_company') }}</span>
                </a>
                <a href="{{ route('supplier.settings.index') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1 {{ request()->routeIs('supplier.settings.*') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ __('settings.settings') }}</span>
                </a>
                <a href="{{ route('faq.index') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1 {{ request()->routeIs('faq.index') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9a3.5 3.5 0 116.544 1.5c0 2-3 2.5-3 4M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ __('faq.nav') }}</span>
                </a>
            </nav>

            <div class="p-4 border-t border-[#7c8799] dark:border-[#3E3E3A] space-y-2">
                <button id="supplier-theme-toggle" type="button"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span>{{ __('supplier-portal.nav_theme') }}</span>
                </button>

                <div class="flex gap-2 px-4">
                    <a href="{{ route('language.switch', 'kk') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">Қаз</a>
                    <a href="{{ route('language.switch', 'ru') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">RU</a>
                    <a href="{{ route('language.switch', 'en') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">EN</a>
                </div>

                <a href="{{ route('supplier.profile.show') }}"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors {{ request()->routeIs('supplier.profile.show') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] ring-1 ring-[#f59e0b]/40 text-[#1b1b18] dark:text-[#EDEDEC]' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ __('dashboard.profile') }}</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#f53003] dark:text-[#FF4433] hover:bg-[#fff2f2] dark:hover:bg-[#1D0002] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>{{ __('dashboard.logout') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div id="supplier-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSupplierSidebar()"></div>

    <div class="lg:ml-64 min-h-screen flex flex-col">
        <header class="sticky top-0 z-30 bg-white/90 dark:bg-[#161615]/95 backdrop-blur-sm border-b border-[#7c8799] dark:border-[#3E3E3A]">
            <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                <button type="button" id="supplier-sidebar-toggle" onclick="toggleSupplierSidebar()"
                    class="lg:hidden p-2 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]"
                    aria-label="{{ __('supplier-portal.open_menu') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg sm:text-xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                    @yield('header_title', __('supplier-portal.title'))
                </h1>
                <div class="w-10 shrink-0" aria-hidden="true"></div>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 py-8 w-full mx-auto max-w-[min(100%,90rem)]">
            @yield('content')
        </main>
    </div>

    <script>
        function toggleSupplierSidebar() {
            const sidebar = document.getElementById('supplier-sidebar');
            const overlay = document.getElementById('supplier-sidebar-overlay');
            if (!sidebar || !overlay) return;
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        document.getElementById('supplier-theme-toggle')?.addEventListener('click', function() {
            const html = document.documentElement;
            const dark = html.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
        });
    </script>
    @stack('scripts')
</body>
</html>
