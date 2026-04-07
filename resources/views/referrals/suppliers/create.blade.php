<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('referrals.page_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .ref-card {
            background: #ffffff;
            border: 1px solid #7c8799;
        }
        .ref-form label {
            color: #64748b;
        }
        .ref-form input,
        .ref-form select,
        .ref-form textarea {
            background: #ffffff;
            border: 1px solid #7c8799;
            color: #1b1b18;
        }
        .dark .ref-card {
            background: #161615;
            border-color: #3E3E3A;
        }
        .dark .ref-form label {
            color: #A1A09A;
        }
        .dark .ref-form input,
        .dark .ref-form select,
        .dark .ref-form textarea {
            background: #0f0f0f;
            border-color: #3E3E3A;
            color: #EDEDEC;
        }
    </style>
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
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] min-h-screen">
    <main>
    <div class="max-w-3xl mx-auto py-8 px-4">
        <div class="mb-4 flex items-center justify-end gap-2">
            <button
                id="theme-toggle"
                type="button"
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
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
            <a href="{{ route('language.switch', 'kk') }}" class="px-2 py-1 text-xs rounded transition-colors {{ app()->getLocale() === 'kk' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#94a3b8] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] hover:border-[#f59e0b]' }}">Қаз</a>
            <a href="{{ route('language.switch', 'ru') }}" class="px-2 py-1 text-xs rounded transition-colors {{ app()->getLocale() === 'ru' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#94a3b8] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] hover:border-[#f59e0b]' }}">RU</a>
            <a href="{{ route('language.switch', 'en') }}" class="px-2 py-1 text-xs rounded transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A]' : 'border border-[#94a3b8] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] hover:border-[#f59e0b]' }}">EN</a>
        </div>
        <div class="ref-card rounded-xl p-6">
            <h1 class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('referrals.page_title') }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('referrals.page_subtitle', ['designer' => $designer->name]) }}</p>

            @if (session('status'))
                <div class="mt-4 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-500/30">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ $submitUrl }}" class="ref-form mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.name') }}</label>
                        <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.phone') }}</label>
                        <input name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.email') }}</label>
                        <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.website') }}</label>
                        <input name="website" value="{{ old('website') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.telegram') }}</label>
                        <input name="telegram" value="{{ old('telegram') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.whatsapp') }}</label>
                        <input name="whatsapp" value="{{ old('whatsapp') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.city') }}</label>
                        <input name="city" value="{{ old('city') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.address') }}</label>
                        <input name="address" value="{{ old('address') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.sphere') }}</label>
                        <select name="sphere" class="modal-input">
                                        <option value="">{{ __('suppliers.sphere_placeholder') }}</option>
                                        @foreach (($sphereOptions ?? []) as $key => $name)
                                        
                                        <option value="{{ $key }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.work_terms') }}</label>
                        <select name="work_terms_type" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                            <option value=""></option>
                            <option value="percent" @selected(old('work_terms_type') === 'percent')>%</option>
                            <option value="amount" @selected(old('work_terms_type') === 'amount')>{{ __('suppliers.work_terms_amount') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.value') }}</label>
                        <input name="work_terms_value" value="{{ old('work_terms_value') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.brands') }}</label>
                        <input name="brands[]" value="{{ old('brands.0') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.cities_presence') }}</label>
                        <input name="cities_presence[]" value="{{ old('cities_presence.0') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm mb-1">{{ __('suppliers.comment') }}</label>
                    <textarea name="comment_main" rows="3" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">{{ old('comment_main') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.org_form') }}</label>
                        <select name="org_form" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                            <option value="ooo" @selected(old('org_form', 'ooo') === 'ooo')>OOO</option>
                            <option value="ip" @selected(old('org_form') === 'ip')>IP</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">{{ __('suppliers.inn') }}</label>
                        <input name="inn" value="{{ old('inn') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">
                    </div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.kpp') }}</label><input name="kpp" value="{{ old('kpp') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.ogrn') }}</label><input name="ogrn" value="{{ old('ogrn') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.okpo') }}</label><input name="okpo" value="{{ old('okpo') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.bik') }}</label><input name="bik" value="{{ old('bik') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.bank') }}</label><input name="bank" value="{{ old('bank') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.checking_account') }}</label><input name="checking_account" value="{{ old('checking_account') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.corr_account') }}</label><input name="corr_account" value="{{ old('corr_account') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.director') }}</label><input name="director" value="{{ old('director') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                    <div><label class="block text-sm mb-1">{{ __('suppliers.accountant') }}</label><input name="accountant" value="{{ old('accountant') }}" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2"></div>
                </div>
                <div>
                    <label class="block text-sm mb-1">{{ __('suppliers.legal_address') }}</label>
                    <textarea name="legal_address" rows="2" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">{{ old('legal_address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm mb-1">{{ __('suppliers.actual_address') }}</label>
                    <textarea name="actual_address" rows="2" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">{{ old('actual_address') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm mb-1">{{ __('suppliers.comment_bank') }}</label>
                    <textarea name="comment_bank" rows="3" class="w-full rounded-lg border border-[#94a3b8] px-3 py-2">{{ old('comment_bank') }}</textarea>
                </div>
                <div class="flex items-center gap-6 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="recommend" value="1" @checked(old('recommend'))>
                        <span>{{ __('suppliers.recommend_supplier') }}</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="address_match" value="1" @checked(old('address_match'))>
                        <span>{{ __('suppliers.match_legal') }}</span>
                    </label>
                </div>

                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-200 px-4 py-3 text-sm">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button type="submit" class="px-4 py-2 rounded-lg bg-[#f59e0b] text-white hover:bg-[#d97706] transition-colors">
                    {{ __('referrals.submit_supplier') }}
                </button>
            </form>
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
