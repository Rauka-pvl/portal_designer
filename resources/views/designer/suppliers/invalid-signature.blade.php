<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('referrals.page_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <style>
        .ref-card {
            background: #ffffff;
            border: 1px solid #7c8799;
            border-radius: 14px;
        }
        .ref-form label {
            color: #64748b;
        }
        .dark .ref-card {
            background: #161615;
            border-color: #3E3E3A;
        }
    </style>
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen">
    <main>
        <div class="max-w-2xl mx-auto py-10 px-4">
            <div class="mb-4 flex items-center justify-end gap-2">
                <button
                    id="theme-toggle"
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a] transition-colors"
                    aria-label="Toggle theme"
                    title="Toggle theme"
                >
                    <svg id="theme-icon-light" class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m12.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg id="theme-icon-dark" class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>

                <a href="{{ route('language.switch', 'kk') }}"
                   class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#7c8799] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]' }}">Қаз</a>
                <a href="{{ route('language.switch', 'ru') }}"
                   class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#7c8799] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]' }}">RU</a>
                <a href="{{ route('language.switch', 'en') }}"
                   class="px-2 py-1 text-xs rounded-sm transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#7c8799] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] dark:hover:bg-[#0a0a0a]' }}">EN</a>
            </div>

            <div class="ref-card p-6">
                <div class="rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-200 p-4">
                    <h1 class="text-xl font-semibold">{{ __('referrals.invalid_signature_title') }}</h1>
                    <p class="text-sm mt-2">{{ __('referrals.invalid_signature') }}</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const themeToggle = document.getElementById('theme-toggle');
            if (!themeToggle) return;
            themeToggle.addEventListener('click', function () {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                if (isDark) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
            });
        })();
    </script>
</body>
</html>
