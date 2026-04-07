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
        // Применяем тему до загрузки страницы, чтобы избежать мерцания
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
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col transition-colors duration-200">
    @include('layouts.partials.app-toasts')

    <div class="w-full lg:max-w-md max-w-[335px]">
        <!-- Логотип и заголовок -->
        <div class="mb-6 flex flex-col items-center">
            <div class="w-full max-w-[300px] mb-4">
                <svg viewBox="0 0 400 120" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                    <defs>
                        <linearGradient id="goldGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#f59e0b;stop-opacity:1" />
                            <stop offset="50%" style="stop-color:#ef4444;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#ec4899;stop-opacity:1" />
                        </linearGradient>
                        
                        <linearGradient id="darkGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#0f172a;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1e293b;stop-opacity:1" />
                        </linearGradient>
                        
                        <filter id="shadow">
                            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.3"/>
                        </filter>
                    </defs>
                    
                    <!-- Фон -->
                    <rect width="400" height="120" fill="#ffffff" id="logo-bg"/>
                    
                    <!-- Геометрический портал -->
                    <g transform="translate(30, 20)">
                        <!-- Главная рамка -->
                        <rect x="5" y="5" width="70" height="70" rx="8" fill="none" stroke="url(#goldGradient)" stroke-width="4" filter="url(#shadow)"/>
                        
                        <!-- Внутренние слои -->
                        <rect x="15" y="15" width="50" height="50" rx="6" fill="none" stroke="url(#goldGradient)" stroke-width="3" opacity="0.6">
                            <animateTransform
                                attributeName="transform"
                                type="rotate"
                                from="0 40 40"
                                to="360 40 40"
                                dur="20s"
                                repeatCount="indefinite"/>
                        </rect>
                        
                        <rect x="25" y="25" width="30" height="30" rx="4" fill="none" stroke="url(#goldGradient)" stroke-width="2" opacity="0.4">
                            <animateTransform
                                attributeName="transform"
                                type="rotate"
                                from="0 40 40"
                                to="-360 40 40"
                                dur="15s"
                                repeatCount="indefinite"/>
                        </rect>
                        
                        <!-- Центральный кристалл -->
                        <polygon points="40,30 50,40 40,50 30,40" fill="url(#goldGradient)" opacity="0.8">
                            <animateTransform
                                attributeName="transform"
                                type="rotate"
                                from="0 40 40"
                                to="360 40 40"
                                dur="10s"
                                repeatCount="indefinite"/>
                        </polygon>
                        
                        <!-- Угловые акценты -->
                        <circle cx="10" cy="10" r="3" fill="#f59e0b"/>
                        <circle cx="70" cy="10" r="3" fill="#ef4444"/>
                        <circle cx="10" cy="70" r="3" fill="#ec4899"/>
                        <circle cx="70" cy="70" r="3" fill="#f59e0b"/>
                    </g>
                    
                    <!-- Текст с геометрическим стилем -->
                    <text x="120" y="45" font-family="'Arial Black', sans-serif" font-size="32" font-weight="900" fill="url(#darkGradient)" letter-spacing="1" id="logo-text-main">
                        ПОРТАЛ
                    </text>
                    
                    <text x="120" y="75" font-family="Arial, sans-serif" font-size="18" font-weight="400" fill="#64748b" letter-spacing="4" id="logo-text-sub">
                        ДИЗАЙНЕРА
                    </text>
                    
                    <!-- Декоративные элементы -->
                    <rect x="120" y="82" width="60" height="3" fill="url(#goldGradient)" rx="1.5"/>
                    <rect x="185" y="82" width="25" height="3" fill="url(#goldGradient)" rx="1.5" opacity="0.5"/>
                    <rect x="215" y="82" width="15" height="3" fill="url(#goldGradient)" rx="1.5" opacity="0.3"/>
                </svg>
            </div>
        </div>
        
        <div class="bg-white dark:bg-[#161615] dark:text-[#EDEDEC] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] rounded-lg p-6 lg:p-8">
            <div class="flex justify-end items-center gap-3 mb-4">
                <button 
                    id="theme-toggle" 
                    type="button"
                    class="p-2 rounded-sm text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#161615] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors"
                    aria-label="Toggle theme"
                >
                    <!-- Солнце (для темной темы) -->
                    <svg id="theme-icon-light" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <!-- Луна (для светлой темы) -->
                    <svg id="theme-icon-dark" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
                <div class="flex gap-2">
                    <a href="{{ route('language.switch', 'kk') }}" class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">Қаз</a>
                    <a href="{{ route('language.switch', 'ru') }}" class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">RU</a>
                    <a href="{{ route('language.switch', 'en') }}" class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]' }}">EN</a>
                </div>
            </div>
            <h1 class="text-2xl font-medium mb-6">@yield('heading')</h1>

            @if (session('status'))
                <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-sm">
                    <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 bg-[#fff2f2] dark:bg-[#1D0002] border border-[#f53003] dark:border-[#F61500] rounded-sm">
                    <ul class="text-sm text-[#f53003] dark:text-[#FF4433]">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')

            @hasSection('footer')
                <div class="mt-6 pt-6 border-t border-[#7c8799] dark:border-[#3E3E3A]">
                    @yield('footer')
                </div>
            @endif
        </div>
    </div>

    <script>
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
            
            // Применяем тему сразу
            initTheme();
            
            // Инициализируем после загрузки DOM
            document.addEventListener('DOMContentLoaded', function() {
                const themeToggle = document.getElementById('theme-toggle');
                const html = document.documentElement;
                
                function updateLogoSVG(isDark) {
                    const logoBg = document.getElementById('logo-bg');
                    const logoTextMain = document.getElementById('logo-text-main');
                    const logoTextSub = document.getElementById('logo-text-sub');
                    
                    if (logoBg) {
                        logoBg.setAttribute('fill', isDark ? '#0a0a0a' : '#ffffff');
                    }
                    if (logoTextMain) {
                        logoTextMain.setAttribute('fill', isDark ? '#EDEDEC' : 'url(#darkGradient)');
                    }
                    if (logoTextSub) {
                        logoTextSub.setAttribute('fill', isDark ? '#A1A09A' : '#64748b');
                    }
                }
                
                function toggleTheme() {
                    const isDark = html.classList.contains('dark');
                    
                    if (isDark) {
                        html.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                        updateLogoSVG(false);
                    } else {
                        html.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                        updateLogoSVG(true);
                    }
                }
                
                // Обновляем SVG при загрузке
                updateLogoSVG(html.classList.contains('dark'));
                
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
</body>
</html>
