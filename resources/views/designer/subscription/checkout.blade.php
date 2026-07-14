@extends('layouts.dashboard')

@section('title', __('subscription.checkout_title'))
@section('header_title', __('subscription.checkout_title'))

@php
    $listPrice = (int) $plan['price'];
@endphp

@push('styles')
<style>
    .sub-checkout-hero {
        background:
            radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.22), transparent 55%),
            #0f172a;
    }
    .dark .sub-checkout-hero { background: radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.28), transparent 55%), #161615; }
    .pay-method input:checked + .pay-face {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.08);
        box-shadow: 0 0 0 1px #f59e0b;
    }
    .kaspi-qr {
        background:
            linear-gradient(90deg, #0f172a 10px, transparent 10px) 0 0 / 20px 20px,
            linear-gradient(#0f172a 10px, transparent 10px) 0 0 / 20px 20px,
            #fff;
        image-rendering: pixelated;
    }
</style>
@endpush

@section('content')
    <div class="mb-5">
        <a href="{{ route('subscription.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('subscription.back_plans') }}
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start max-w-5xl">
        {{-- Summary --}}
        <div class="xl:col-span-2">
            <div class="sub-checkout-hero relative overflow-hidden rounded-2xl p-6 text-white">
                <div class="text-xs uppercase tracking-wide text-white/50 mb-2">{{ __('subscription.checkout_title') }}</div>
                <h1 class="text-2xl font-bold">{{ __('subscription.plan_'.$planKey) }}</h1>
                <p class="mt-2 text-sm text-white/60">{{ __('subscription.plan_'.$planKey.'_desc') }}</p>
                <div class="mt-6 pt-5 border-t border-white/10">
                    <div class="text-xs text-white/50 mb-1">{{ __('subscription.amount_due') }}</div>
                    <div class="text-3xl font-bold" id="amount-display">
                        @if ($canUseTrial)
                            {{ __('subscription.free_trial_amount') }}
                        @else
                            {{ number_format($listPrice, 0, ',', ' ') }} ₸
                        @endif
                    </div>
                    @if ($canUseTrial)
                        <p class="mt-2 text-sm text-[#fbbf24]">{{ __('subscription.trial_hint') }}</p>
                    @endif
                    <p class="mt-2 text-sm text-[#fbbf24] hidden" id="promo-ok-msg">{{ __('subscription.free_with_promo') }}</p>
                </div>
            </div>
        </div>

        {{-- Payment form --}}
        <div class="xl:col-span-3 rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-7">
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-5">{{ __('subscription.checkout_subtitle') }}</p>

            <form method="POST" action="{{ route('subscription.purchase') }}" id="checkout-form" class="space-y-6">
                @csrf
                <input type="hidden" name="plan" value="{{ $planKey }}">

                {{-- Methods --}}
                <div class="space-y-3">
                    <label class="pay-method cursor-pointer block">
                        <input type="radio" name="payment_method" value="kaspi" class="sr-only" checked>
                        <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f59e0b]/15 text-[#f59e0b]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_kaspi') }}</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.pay_kaspi_hint') }}</div>
                                </div>
                            </div>
                            <div id="kaspi-panel" class="mt-4 flex flex-col sm:flex-row items-center gap-4">
                                <div class="kaspi-qr relative w-40 h-40 rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] overflow-hidden flex items-center justify-center">
                                    {{-- Demo QR placeholder --}}
                                    <svg viewBox="0 0 120 120" class="w-36 h-36 text-[#0f172a] dark:text-[#EDEDEC]" aria-hidden="true">
                                        <rect width="120" height="120" fill="white"/>
                                        <g fill="currentColor">
                                            <rect x="8" y="8" width="36" height="36"/><rect x="14" y="14" width="24" height="24" fill="white"/><rect x="20" y="20" width="12" height="12"/>
                                            <rect x="76" y="8" width="36" height="36"/><rect x="82" y="14" width="24" height="24" fill="white"/><rect x="88" y="20" width="12" height="12"/>
                                            <rect x="8" y="76" width="36" height="36"/><rect x="14" y="82" width="24" height="24" fill="white"/><rect x="20" y="88" width="12" height="12"/>
                                            <rect x="52" y="8" width="8" height="8"/><rect x="64" y="8" width="8" height="8"/><rect x="52" y="20" width="8" height="8"/>
                                            <rect x="52" y="52" width="8" height="8"/><rect x="64" y="52" width="8" height="8"/><rect x="76" y="52" width="8" height="8"/>
                                            <rect x="52" y="64" width="8" height="8"/><rect x="88" y="64" width="8" height="8"/><rect x="100" y="64" width="8" height="8"/>
                                            <rect x="52" y="76" width="8" height="8"/><rect x="64" y="88" width="8" height="8"/><rect x="76" y="100" width="8" height="8"/>
                                            <rect x="88" y="76" width="8" height="8"/><rect x="100" y="88" width="8" height="8"/><rect x="100" y="100" width="12" height="12"/>
                                        </g>
                                    </svg>
                                </div>
                                <p class="text-xs text-[#64748b] dark:text-[#A1A09A] max-w-[200px]">{{ __('subscription.qr_stub') }}</p>
                            </div>
                        </div>
                    </label>

                    <label class="pay-method cursor-pointer block">
                        <input type="radio" name="payment_method" value="card" class="sr-only">
                        <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_card') }}</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.pay_card_hint') }}</div>
                                </div>
                            </div>
                            <div id="card-panel" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 hidden">
                                <div class="sm:col-span-3">
                                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.card_number') }}</label>
                                    <input type="text" name="card_number" maxlength="19" placeholder="0000 0000 0000 0000"
                                        class="mt-1 w-full rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-3 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b]">
                                </div>
                                <div>
                                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.card_expiry') }}</label>
                                    <input type="text" name="card_expiry" maxlength="5" placeholder="MM/YY"
                                        class="mt-1 w-full rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-3 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b]">
                                </div>
                                <div>
                                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.card_cvc') }}</label>
                                    <input type="text" name="card_cvc" maxlength="4" placeholder="•••"
                                        class="mt-1 w-full rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-3 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b]">
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                {{-- Promo --}}
                <div>
                    <label for="promo_code" class="block text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1.5">{{ __('subscription.promo_label') }}</label>
                    <div class="flex gap-2">
                        <input type="text" id="promo_code" name="promo_code" value="{{ old('promo_code') }}"
                            placeholder="{{ __('subscription.promo_placeholder') }}"
                            class="flex-1 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-4 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b]">
                        <button type="button" id="promo-apply-btn"
                            class="rounded-xl border border-[#f59e0b] px-4 py-2.5 text-sm font-medium text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors">
                            {{ __('subscription.promo_apply') }}
                        </button>
                    </div>
                    @error('promo_code')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400 hidden" id="promo-feedback">{{ __('subscription.promo_ok') }}</p>
                </div>

                <button type="submit" id="pay-submit"
                    class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3.5 text-sm font-semibold text-white shadow-sm hover:opacity-95 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span id="pay-label">{{ $canUseTrial ? __('subscription.start_trial') : __('subscription.pay_now') }}</span>
                </button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const promoCode = 'DesignPortal-2026!';
    const listPrice = {{ $listPrice }};
    const canUseTrial = @json($canUseTrial);
    const amountEl = document.getElementById('amount-display');
    const promoInput = document.getElementById('promo_code');
    const promoFeedback = document.getElementById('promo-feedback');
    const promoOkMsg = document.getElementById('promo-ok-msg');
    const payLabel = document.getElementById('pay-label');
    const cardPanel = document.getElementById('card-panel');
    const kaspiPanel = document.getElementById('kaspi-panel');

    function formatMoney(n) {
        return new Intl.NumberFormat('ru-RU').format(n) + ' ₸';
    }

    function updateAmount() {
        const code = (promoInput.value || '').trim();
        const isPromo = code === promoCode;
        promoFeedback.classList.toggle('hidden', !isPromo);
        promoOkMsg.classList.toggle('hidden', !isPromo);

        if (isPromo) {
            amountEl.textContent = @json(__('subscription.free_with_promo'));
            payLabel.textContent = @json(__('subscription.pay_now'));
            return;
        }
        if (canUseTrial) {
            amountEl.textContent = @json(__('subscription.free_trial_amount'));
            payLabel.textContent = @json(__('subscription.start_trial'));
            return;
        }
        amountEl.textContent = formatMoney(listPrice);
        payLabel.textContent = @json(__('subscription.pay_now'));
    }

    document.getElementById('promo-apply-btn')?.addEventListener('click', updateAmount);
    promoInput?.addEventListener('input', updateAmount);

    document.querySelectorAll('input[name="payment_method"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const method = document.querySelector('input[name="payment_method"]:checked')?.value;
            cardPanel?.classList.toggle('hidden', method !== 'card');
            kaspiPanel?.classList.toggle('hidden', method !== 'kaspi');
        });
    });

    updateAmount();
})();
</script>
@endsection
