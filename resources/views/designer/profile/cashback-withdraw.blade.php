@extends('layouts.dashboard')

@section('title', __('cashback.withdraw_title'))
@section('header_title', __('cashback.withdraw_title'))

@push('styles')
<style>
    .cb-withdraw-hero {
        background:
            radial-gradient(ellipse 80% 60% at 100% 0%, rgba(245, 158, 11, 0.22), transparent 55%),
            radial-gradient(ellipse 60% 50% at 0% 100%, rgba(251, 146, 60, 0.12), transparent 50%),
            #0f172a;
    }
    .dark .cb-withdraw-hero {
        background:
            radial-gradient(ellipse 80% 60% at 100% 0%, rgba(245, 158, 11, 0.28), transparent 55%),
            radial-gradient(ellipse 60% 50% at 0% 100%, rgba(251, 146, 60, 0.14), transparent 50%),
            #161615;
    }
    .cb-method-card input:checked + .cb-method-face {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.08);
        box-shadow: 0 0 0 1px #f59e0b;
    }
    .cb-method-card input:checked + .cb-method-face .cb-method-icon {
        background: #f59e0b;
        color: #fff;
    }
    .cb-method-card input:checked + .cb-method-face .cb-method-check {
        opacity: 1;
        transform: scale(1);
    }
    .cb-quick-btn.active {
        border-color: #f59e0b;
        background: #f59e0b;
        color: #fff;
    }
    .cb-amount-input::-webkit-outer-spin-button,
    .cb-amount-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .cb-amount-input {
        -moz-appearance: textfield;
        appearance: textfield;
    }
</style>
@endpush

@section('content')
    @include('partials.profile-tabs', ['active' => 'cashback'])

    <div class="mb-5">
        <a href="{{ route('profile.cashback') }}"
            class="inline-flex items-center gap-1.5 text-sm text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('cashback.withdraw_back') }}
        </a>
        <p class="mt-3 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('cashback.withdraw_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start max-w-5xl">
        {{-- Balance hero --}}
        <div class="xl:col-span-2">
            <div class="cb-withdraw-hero relative overflow-hidden rounded-2xl p-6 text-white">
                <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-[#f59e0b]/20 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-6 bottom-0 h-24 w-24 rounded-full bg-[#fb923c]/15 blur-xl pointer-events-none"></div>

                <div class="relative">
                    <div class="flex items-center gap-2 text-white/70 text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        {{ __('cashback.withdraw_balance_label') }}
                    </div>
                    <div class="mt-3 text-4xl sm:text-5xl font-bold tracking-tight">
                        {{ number_format($available, 0, ',', ' ') }}
                        <span class="text-xl font-semibold text-white/50">₸</span>
                    </div>
                    <p class="mt-4 text-sm text-white/55 leading-relaxed">
                        {{ __('cashback.withdraw_details_hint') }}
                    </p>
                </div>
            </div>

            <div class="mt-4 hidden xl:grid grid-cols-3 gap-2">
                @foreach ([
                    ['n' => '1', 'label' => __('cashback.withdraw_step_amount')],
                    ['n' => '2', 'label' => __('cashback.withdraw_step_method')],
                    ['n' => '3', 'label' => __('cashback.withdraw_step_details')],
                ] as $step)
                    <div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-3 py-3 text-center">
                        <div class="mx-auto mb-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-[#f59e0b]/15 text-[11px] font-semibold text-[#f59e0b]">{{ $step['n'] }}</div>
                        <div class="text-[11px] text-[#64748b] dark:text-[#A1A09A] leading-tight">{{ $step['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form --}}
        <div class="xl:col-span-3 rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-7">
            <form method="POST" action="{{ route('profile.cashback.withdraw.store') }}" class="space-y-7" id="cb-withdraw-form">
                @csrf

                {{-- Amount --}}
                <div>
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label for="amount" class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                            {{ __('cashback.withdraw_amount') }}
                        </label>
                        <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                            {{ __('cashback.withdraw_available') }}:
                            <span class="font-medium text-[#f59e0b]">{{ number_format($available, 0, ',', ' ') }} ₸</span>
                        </span>
                    </div>

                    <div class="relative">
                        <input type="number"
                            id="amount"
                            name="amount"
                            min="1"
                            max="{{ $available }}"
                            value="{{ old('amount', $available) }}"
                            data-max="{{ $available }}"
                            class="cb-amount-input w-full rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-5 py-4 pr-14 text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-2 focus:ring-[#f59e0b]/30 outline-none transition-shadow"
                            required>
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-lg font-medium text-[#64748b] dark:text-[#A1A09A]">₸</span>
                    </div>
                    @error('amount')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror

                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ([25, 50, 75] as $pct)
                            <button type="button"
                                class="cb-quick-btn px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs font-medium text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                                data-pct="{{ $pct }}">
                                {{ $pct }}%
                            </button>
                        @endforeach
                        <button type="button"
                            class="cb-quick-btn px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs font-medium text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                            data-pct="100">
                            {{ __('cashback.withdraw_quick_all') }}
                        </button>
                    </div>
                </div>

                {{-- Method --}}
                <div>
                    <label class="block text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-3">
                        {{ __('cashback.withdraw_method') }}
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="cb-method-card cursor-pointer block">
                            <input type="radio" name="payment_method" value="card" class="sr-only" @checked(old('payment_method', 'card') === 'card')>
                            <div class="cb-method-face relative flex items-start gap-3 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all hover:border-[#f59e0b]/70">
                                <div class="cb-method-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b] dark:text-[#A1A09A] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 pr-6">
                                    <div class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('cashback.withdraw_method_card') }}</div>
                                    <div class="mt-0.5 text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('cashback.withdraw_method_card_hint') }}</div>
                                </div>
                                <span class="cb-method-check absolute right-3 top-3 opacity-0 scale-75 transition-all text-[#f59e0b]">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </label>

                        <label class="cb-method-card cursor-pointer block">
                            <input type="radio" name="payment_method" value="bank" class="sr-only" @checked(old('payment_method') === 'bank')>
                            <div class="cb-method-face relative flex items-start gap-3 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all hover:border-[#f59e0b]/70">
                                <div class="cb-method-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b] dark:text-[#A1A09A] transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 pr-6">
                                    <div class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('cashback.withdraw_method_bank') }}</div>
                                    <div class="mt-0.5 text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('cashback.withdraw_method_bank_hint') }}</div>
                                </div>
                                <span class="cb-method-check absolute right-3 top-3 opacity-0 scale-75 transition-all text-[#f59e0b]">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </span>
                            </div>
                        </label>
                    </div>
                    @error('payment_method')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Details --}}
                <div>
                    <label for="payment_details" class="block text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">
                        {{ __('cashback.withdraw_details') }}
                    </label>
                    <textarea id="payment_details"
                        name="payment_details"
                        rows="3"
                        maxlength="500"
                        required
                        placeholder="{{ __('cashback.withdraw_details_placeholder') }}"
                        class="w-full rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] placeholder:text-[#94a3b8] dark:placeholder:text-[#71717a] focus:border-[#f59e0b] focus:ring-2 focus:ring-[#f59e0b]/30 outline-none transition-shadow resize-y">{{ old('payment_details') }}</textarea>
                    @error('payment_details')
                        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-1 border-t border-[#7c8799]/30 dark:border-[#3E3E3A] mt-2">
                    <a href="{{ route('profile.cashback') }}"
                        class="inline-flex justify-center items-center gap-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] px-5 py-3 text-sm font-medium text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                        {{ __('cashback.withdraw_back') }}
                    </a>
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:opacity-95 active:scale-[0.99] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        {{ __('cashback.withdraw_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const input = document.getElementById('amount');
    if (!input) return;

    const max = parseInt(input.dataset.max || '0', 10) || 0;
    const buttons = document.querySelectorAll('.cb-quick-btn');

    function setAmount(value) {
        const clamped = Math.max(1, Math.min(max, Math.round(value)));
        input.value = String(clamped);
        buttons.forEach((btn) => {
            const pct = parseInt(btn.dataset.pct || '0', 10);
            const target = Math.max(1, Math.round(max * pct / 100));
            btn.classList.toggle('active', target === clamped && pct > 0);
        });
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const pct = parseInt(btn.dataset.pct || '0', 10);
            setAmount(max * pct / 100);
        });
    });

    input.addEventListener('input', () => {
        buttons.forEach((btn) => btn.classList.remove('active'));
    });

    if (parseInt(input.value || '0', 10) === max) {
        const allBtn = document.querySelector('.cb-quick-btn[data-pct="100"]');
        if (allBtn) allBtn.classList.add('active');
    }
})();
</script>
@endsection
