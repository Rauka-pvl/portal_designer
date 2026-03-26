<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('dashboard.dashboard')) - {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://unpkg.com/imask"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/kz.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script>
        // Применяем тему до загрузки страницы
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

<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] transition-colors duration-200">
    <!-- Выдвижная навигация -->
    <aside id="sidebar"
        class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-[#161615] border-r border-[#e3e3e0] dark:border-[#3E3E3A] transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50">
        <div class="flex flex-col h-full">
            <!-- Логотип -->
            <div class="p-6 border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
                <div class="w-full max-w-[200px]">
                    <svg viewBox="0 0 400 120" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                        <defs>
                            <linearGradient id="sidebarGoldGradient" x1="0%" y1="0%" x2="100%"
                                y2="100%">
                                <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                                <stop offset="50%" style="stop-color:#ef4444;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <rect width="400" height="120" fill="transparent" />
                        <g transform="translate(30, 20)">
                            <rect x="5" y="5" width="70" height="70" rx="8" fill="none"
                                stroke="url(#sidebarGoldGradient)" stroke-width="4" />
                            <rect x="15" y="15" width="50" height="50" rx="6" fill="none"
                                stroke="url(#sidebarGoldGradient)" stroke-width="3" opacity="0.6">
                                <animateTransform attributeName="transform" type="rotate" from="0 40 40" to="360 40 40"
                                    dur="20s" repeatCount="indefinite" />
                            </rect>
                            <polygon points="40,30 50,40 40,50 30,40" fill="url(#sidebarGoldGradient)" opacity="0.8">
                                <animateTransform attributeName="transform" type="rotate" from="0 40 40" to="360 40 40"
                                    dur="10s" repeatCount="indefinite" />
                            </polygon>
                        </g>
                        <text x="120" y="45" font-family="'Arial Black', sans-serif" font-size="32" font-weight="900"
                            fill="currentColor" letter-spacing="1">ПОРТАЛ</text>
                        <text x="120" y="75" font-family="Arial, sans-serif" font-size="18" font-weight="400"
                            fill="currentColor" letter-spacing="4">ДИЗАЙНЕРА</text>
                    </svg>
                </div>
            </div>

            <!-- Навигация -->
            <nav class="flex-1 p-4 overflow-y-auto">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>{{ __('dashboard.dashboard') }}</span>
                </a>
                @if (Route::has('clients.index'))
                    <a href="{{ route('clients.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span>{{ __('clients.my_clients') }}</span>
                    </a>
                @endif

                @if (Route::has('objects.index'))
                    <a href="{{ route('objects.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>{{ __('objects.object_passport') }}</span>
                    </a>
                @endif

                @if (Route::has('projects.index'))
                    <a href="{{ route('projects.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ __('projects.projects') }}</span>
                    </a>
                @endif

                @if (Route::has('supplier-orders.index'))
                    <a href="{{ route('supplier-orders.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span>{{ __('supplier-orders.supplier_orders') }}</span>
                    </a>
                @endif

                @if (Route::has('suppliers.index'))
                    <a href="{{ route('suppliers.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>{{ __('suppliers.suppliers') }}</span>
                    </a>
                @endif

                @if (Route::has('bonus-account.index'))
                    <a href="{{ route('bonus-account.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ __('bonus-account.bonus_account') }}</span>
                    </a>
                @endif

                @if (Route::has('settings.index'))
                    <a href="{{ route('settings.index') }}"
                        class="flex items-center gap-3 px-4 py-2 rounded-lg text-[#1b1b18] dark:text-[#EDEDEC] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ __('settings.settings') }}</span>
                    </a>
                @endif
            </nav>

            <!-- Нижняя панель -->
            <div class="p-4 border-t border-[#e3e3e0] dark:border-[#3E3E3A] space-y-2">
                <!-- Переключатель темы -->
                <button id="theme-toggle" type="button"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors">
                    <svg id="theme-icon-light" class="w-5 h-5 hidden dark:block" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg id="theme-icon-dark" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <span id="theme-text">{{ __('dashboard.dashboard') }}</span>
                </button>

                <!-- Языки -->
                <div class="flex gap-2 px-4">
                    <a href="{{ route('language.switch', 'kk') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">Қаз</a>
                    <a href="{{ route('language.switch', 'ru') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">RU</a>
                    <a href="{{ route('language.switch', 'en') }}"
                        class="flex-1 px-2 py-1.5 text-xs rounded-sm text-center transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">EN</a>
                </div>

                <!-- Профиль -->
                <button type="button"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>{{ __('dashboard.profile') }}</span>
                </button>

                <!-- Выход -->
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

    <!-- Overlay для мобильных -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden" onclick="toggleSidebar()">
    </div>

    <!-- Основной контент -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Верхняя панель -->
        <header class="sticky top-0 z-30 bg-white dark:bg-[#161615] border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
            <div class="flex items-center justify-between px-4 py-4">
                <button id="sidebar-toggle" onclick="toggleSidebar()"
                    class="lg:hidden p-2 rounded-lg text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-xl font-medium">{{ __('dashboard.dashboard') }}</h1>
                <div class="w-10"></div>
            </div>
        </header>

        <!-- Контент -->
        <main class="p-6">
            @yield('content')
        </main>
    </div>

    <!-- Project alerts container -->
    <div id="project-alert-container" class="pointer-events-none fixed top-4 right-4 z-[9999] flex flex-col gap-3"></div>

    <script>
        // Переключение сайдбара
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        /**
         * Красивые toasts вместо стандартного alert()
         * @param {'success'|'error'|'info'} type
         * @param {string} message
         * @param {string} title
         * @param {number} duration
         */
        function projectAlert(type, message, title = '', duration = 2800) {
            const container = document.getElementById('project-alert-container');
            if (!container) return;

            const isDark = document.documentElement.classList.contains('dark');
            const palette = {
                success: isDark
                    ? { bg: 'rgba(16,185,129,0.14)', border: 'rgba(16,185,129,0.45)', color: '#34d399' }
                    : { bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.35)', color: '#059669' },
                error: isDark
                    ? { bg: 'rgba(239,68,68,0.14)', border: 'rgba(239,68,68,0.45)', color: '#f87171' }
                    : { bg: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.35)', color: '#dc2626' },
                info: isDark
                    ? { bg: 'rgba(59,130,246,0.14)', border: 'rgba(59,130,246,0.45)', color: '#60a5fa' }
                    : { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.35)', color: '#2563eb' },
            }[type] || (isDark
                ? { bg: 'rgba(59,130,246,0.14)', border: 'rgba(59,130,246,0.45)', color: '#60a5fa' }
                : { bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.35)', color: '#2563eb' });

            const iconSvg = (() => {
                if (type === 'success') {
                    return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"></path>
                    </svg>`;
                }
                if (type === 'error') {
                    return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6L6 18"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>`;
                }
                return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                    <path d="M12 16v-4"></path>
                    <path d="M12 8h.01"></path>
                </svg>`;
            })();

            const el = document.createElement('div');
            el.setAttribute('role', 'status');
            el.className = 'pointer-events-auto flex items-start gap-3 rounded-xl border shadow-lg backdrop-blur-sm px-4 py-3 transition-all duration-200';
            el.style.background = palette.bg;
            el.style.borderColor = palette.border;
            el.style.color = palette.color;
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';

            const iconWrap = document.createElement('div');
            iconWrap.className = 'mt-0.5 flex items-center';
            iconWrap.innerHTML = iconSvg;

            const textWrap = document.createElement('div');
            textWrap.className = 'flex-1 min-w-0';

            const titleEl = document.createElement('div');
            titleEl.className = 'text-sm font-semibold leading-4 mb-1';
            titleEl.textContent = title || '';
            titleEl.style.display = title ? 'block' : 'none';

            const msgEl = document.createElement('div');
            msgEl.className = 'text-sm leading-snug';
            msgEl.textContent = message || '';

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'ml-2 -mr-1 rounded-lg hover:bg-black/5 dark:hover:bg-white/10 transition-colors';
            closeBtn.style.color = palette.color;
            closeBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6L6 18"></path>
                <path d="M6 6l12 12"></path>
            </svg>`;
            closeBtn.addEventListener('click', () => removeToast());

            textWrap.appendChild(titleEl);
            textWrap.appendChild(msgEl);

            el.appendChild(iconWrap);
            el.appendChild(textWrap);
            el.appendChild(closeBtn);

            container.appendChild(el);

            const removeToast = () => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    try {
                        el.remove();
                    } catch (_) {}
                }, 200);
            };

            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });

            setTimeout(removeToast, duration);
        }

        window.projectAlert = projectAlert;

        /**
         * Кастомные select’ы для всех страниц проекта.
         * - Стиль как у "На странице"
         * - Если options > 5 — добавляем поиск по пунктам
         * - Скрытые/уже кастомные select’ы не трогаем
         */
        (function initCustomSelects() {
            const locale = '{{ app()->getLocale() }}';
            const searchPlaceholder = locale.startsWith('ru') ? 'Поиск...' : (locale.startsWith('kk') ? 'Іздеу...' : 'Search...');

            const selects = Array.from(document.querySelectorAll('select'));
            selects.forEach((select) => {
                if (!select) return;
                if (select.dataset.customSelect === 'true') return;
                if (select.classList.contains('hidden') || select.hidden) return; // например, clients-per-page
                if (select.closest('.custom-select-wrapper')) return;

                const optionEls = Array.from(select.options || []);
                const realOptions = optionEls.filter(o => !o.disabled);

                if (!realOptions.length) return;

                // Строка выбранного значения
                const currentOption = select.selectedOptions && select.selectedOptions[0] ? select.selectedOptions[0] : realOptions[0];
                const currentLabel = (currentOption && currentOption.textContent) ? currentOption.textContent.trim() : '';

                const wrapper = document.createElement('div');
                wrapper.className = 'custom-select-wrapper w-full relative';

                // Прячем нативный select визуально, но НЕ через display:none — иначе браузер
                // не может сфокусировать контроль при проверке required ("not focusable").
                select.style.position = 'absolute';
                select.style.width = '1px';
                select.style.height = '1px';
                select.style.padding = '0';
                select.style.margin = '-1px';
                select.style.overflow = 'hidden';
                select.style.clip = 'rect(0, 0, 0, 0)';
                select.style.whiteSpace = 'nowrap';
                select.style.border = '0';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className =
                    'w-full flex items-center justify-between px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] ' +
                    'bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]';
                btn.innerHTML = `
                    <span class="custom-select-label text-left">${currentLabel}</span>
                    <svg class="w-4 h-4 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                `;

                const menu = document.createElement('div');
                menu.className =
                    'hidden absolute left-0 right-0 mt-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] ' +
                    'bg-white dark:bg-[#161615] shadow-lg overflow-hidden z-[60]';

                const showSearch = realOptions.length > 5;
                menu.dataset.showSearch = showSearch ? '1' : '0';

                let searchHtml = '';
                if (showSearch) {
                    searchHtml = `
                        <div class="p-2 border-b border-[#e2e8f0] dark:border-[#3E3E3A]">
                            <input type="text" class="w-full px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]" placeholder="${searchPlaceholder}">
                        </div>
                    `;
                }

                const itemsHtml = realOptions
                    .map((opt) => {
                        const value = opt.value;
                        const text = opt.textContent.trim();
                        const active = select.value === value;
                        return `
                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left custom-select-option ${active ? 'bg-[#fef3c7] dark:bg-[#1D0002] text-[#f59e0b] dark:text-[#f59e0b]' : ''}"
                                data-value="${value}">
                                ${text}
                            </button>
                        `;
                    })
                    .join('');

                menu.innerHTML = `
                    ${searchHtml}
                    <div class="max-h-60 overflow-auto">
                        ${itemsHtml}
                    </div>
                `;

                wrapper.appendChild(btn);
                wrapper.appendChild(menu);

                // Вставляем wrapper вместо select
                select.parentElement.insertBefore(wrapper, select);
                wrapper.appendChild(select);

                const closeMenu = () => menu.classList.add('hidden');
                const openMenu = () => menu.classList.remove('hidden');

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (menu.classList.contains('hidden')) openMenu(); else closeMenu();
                });

                document.addEventListener('click', () => closeMenu());

                menu.querySelectorAll('.custom-select-option').forEach((optBtn) => {
                    optBtn.addEventListener('click', () => {
                        const value = optBtn.dataset.value;
                        select.value = value;
                        const newLabel = optBtn.textContent.trim();
                        const labelEl = btn.querySelector('.custom-select-label');
                        if (labelEl) labelEl.textContent = newLabel;

                        // Подсветка активного пункта
                        menu.querySelectorAll('.custom-select-option').forEach((b) => {
                            const isActive = b.dataset.value === value;
                            b.classList.toggle('bg-[#fef3c7]', isActive);
                            b.classList.toggle('dark:bg-[#1D0002]', isActive);
                            b.classList.toggle('text-[#f59e0b]', isActive);
                            b.classList.toggle('dark:text-[#f59e0b]', isActive);
                        });

                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        closeMenu();
                    });
                });

                // Поиск внутри dropdown (только если options > 5)
                if (showSearch) {
                    const input = menu.querySelector('input[type="text"]');
                    if (input) {
                        input.addEventListener('input', () => {
                            const q = input.value.trim().toLowerCase();
                            menu.querySelectorAll('.custom-select-option').forEach((b) => {
                                const t = (b.textContent || '').toLowerCase();
                                b.style.display = (!q || t.includes(q)) ? '' : 'none';
                            });
                        });
                    }
                }
            });
        })();

        // Переключение темы
        (function() {
            'use strict';

            function initTheme() {
                const html = document.documentElement;
                const theme = localStorage.getItem('theme') || 'light';

                if (theme === 'dark') {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
            }

            initTheme();

            document.addEventListener('DOMContentLoaded', function() {
                const themeToggle = document.getElementById('theme-toggle');
                const html = document.documentElement;

                function toggleTheme() {
                    const isDark = html.classList.contains('dark');

                    if (isDark) {
                        html.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    } else {
                        html.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    }
                }

                if (themeToggle) {
                    themeToggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        toggleTheme();
                    });
                }
            });
        })();
    </script>

    {{-- Page-specific scripts --}}
    @yield('scripts')
</body>

</html>
