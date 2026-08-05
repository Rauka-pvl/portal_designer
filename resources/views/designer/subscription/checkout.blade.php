@extends('layouts.dashboard')

@section('title', __('subscription.checkout_title'))
@section('header_title', ($isOnboarding ?? false) ? '' : __('subscription.checkout_title'))

@php
    $listPrice = (int) $plan['price'];
    $isOnboarding = $isOnboarding ?? false;
    $trialTotalDays = $trialTotalDays ?? \App\Support\DesignerSubscription::trialDays();
    $paymentsEnabled = $paymentsEnabled ?? false;
    $promoWindowActive = $promoWindowActive ?? false;
    $promoPeriodDays = $promoPeriodDays ?? 180;
    $promoMonths = (int) ceil($promoPeriodDays / 30);
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
    .pay-method.is-disabled { cursor: not-allowed; opacity: 0.72; }
    .pay-method.is-disabled .pay-face { background: #f8fafc; }
    .dark .pay-method.is-disabled .pay-face { background: #0f0f0e; }
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
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        @include('partials.back-link', [
            'fallback' => route('subscription.index'),
            'label' => __('subscription.back_plans'),
        ])
        @if ($isOnboarding)
            <nav class="flex flex-wrap items-center gap-2 text-sm text-[#A1A09A]" aria-label="{{ __('subscription.steps_aria') }}">
                <span>{{ __('subscription.step_plan') }}</span>
                <span aria-hidden="true">→</span>
                <span class="text-[#f59e0b] font-semibold">{{ __('subscription.step_payment') }}</span>
                <span aria-hidden="true">→</span>
                <span>{{ __('subscription.step_done') }}</span>
            </nav>
        @endif
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
                        {{ \App\Support\DesignerSubscription::formatMoney($listPrice) }}
                    </div>
                    <p class="mt-2 text-sm text-[#fbbf24] hidden" id="promo-ok-msg">
                        {{ __('subscription.free_with_promo_months', ['months' => $promoMonths]) }}
                    </p>
                    @if (! $paymentsEnabled)
                        <p class="mt-3 text-xs text-white/55">{{ __('subscription.acquiring_offline_hint') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment form --}}
        <div class="xl:col-span-3 rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-7">
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-5">
                {{ $paymentsEnabled ? __('subscription.checkout_subtitle') : __('subscription.checkout_subtitle_promo_only') }}
            </p>

            @if (! $promoWindowActive && ! $paymentsEnabled)
                <div class="mb-5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                    {{ __('subscription.promo_window_closed') }}
                </div>
            @elseif ($promoWindowActive && $promoEndsAtLabel)
                <div class="mb-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    {{ __('subscription.promo_window_hint', ['date' => $promoEndsAtLabel, 'months' => $promoMonths]) }}
                </div>
            @endif

            <form method="POST" action="{{ route('subscription.purchase') }}" id="checkout-form" class="space-y-6">
                @csrf
                <input type="hidden" name="plan" value="{{ $planKey }}">
                @if (! $paymentsEnabled)
                    <input type="hidden" name="payment_method" value="promo">
                @endif

                {{-- Methods --}}
                <div class="space-y-3">
                    <div class="pay-method {{ $paymentsEnabled ? '' : 'is-disabled' }} block">
                        @if ($paymentsEnabled)
                            <label class="cursor-pointer block">
                                <input type="radio" name="payment_method" value="kaspi" class="sr-only" checked>
                                <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        @else
                            <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        @endif
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f59e0b]/15 text-[#f59e0b]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_kaspi') }}</div>
                                        @unless ($paymentsEnabled)
                                            <span class="rounded-full bg-[#64748b]/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#64748b]">{{ __('subscription.pay_unavailable_badge') }}</span>
                                        @endunless
                                    </div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                                        {{ $paymentsEnabled ? __('subscription.pay_kaspi_hint') : __('subscription.pay_kaspi_disabled') }}
                                    </div>
                                </div>
                            </div>
                            @if ($paymentsEnabled)
                                <div id="kaspi-panel" class="mt-4 flex flex-col sm:flex-row items-center gap-4">
                                    <div class="kaspi-qr relative w-40 h-40 rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] overflow-hidden flex items-center justify-center">
                                        <svg viewBox="0 0 120 120" class="w-36 h-36 text-[#0f172a] dark:text-[#EDEDEC]" aria-hidden="true">
                                            <rect width="120" height="120" fill="white"/>
                                            <g fill="currentColor">
                                                <rect x="8" y="8" width="36" height="36"/><rect x="14" y="14" width="24" height="24" fill="white"/><rect x="20" y="20" width="12" height="12"/>
                                                <rect x="76" y="8" width="36" height="36"/><rect x="82" y="14" width="24" height="24" fill="white"/><rect x="88" y="20" width="12" height="12"/>
                                                <rect x="8" y="76" width="36" height="36"/><rect x="14" y="82" width="24" height="24" fill="white"/><rect x="20" y="88" width="12" height="12"/>
                                            </g>
                                        </svg>
                                    </div>
                                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] max-w-[200px]">{{ __('subscription.qr_stub') }}</p>
                                </div>
                            @endif
                        </div>
                        @if ($paymentsEnabled)
                            </label>
                        @endif
                    </div>

                    <div class="pay-method {{ $paymentsEnabled ? '' : 'is-disabled' }} block">
                        @if ($paymentsEnabled)
                            <label class="cursor-pointer block">
                                <input type="radio" name="payment_method" value="card" class="sr-only">
                                <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        @else
                            <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        @endif
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_card') }}</div>
                                        @unless ($paymentsEnabled)
                                            <span class="rounded-full bg-[#64748b]/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-[#64748b]">{{ __('subscription.pay_unavailable_badge') }}</span>
                                        @endunless
                                    </div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                                        {{ $paymentsEnabled ? __('subscription.pay_card_hint') : __('subscription.pay_card_disabled') }}
                                    </div>
                                </div>
                            </div>
                            @if ($paymentsEnabled)
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
                            @endif
                        </div>
                        @if ($paymentsEnabled)
                            </label>
                        @endif
                    </div>
                </div>

                {{-- Promo --}}
                <div>
                    <label for="promo_code" class="block text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1.5">{{ __('subscription.promo_label') }}</label>
                    <div class="flex gap-2">
                        <input type="text" id="promo_code" name="promo_code" value="{{ old('promo_code') }}"
                            placeholder="{{ __('subscription.promo_placeholder') }}"
                            @disabled(! $promoWindowActive && ! $paymentsEnabled)
                            class="flex-1 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0f0f0e] px-4 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:border-[#f59e0b] focus:ring-1 focus:ring-[#f59e0b] disabled:opacity-50">
                        <button type="button" id="promo-apply-btn"
                            @disabled(! $promoWindowActive && ! $paymentsEnabled)
                            class="rounded-xl border border-[#f59e0b] px-4 py-2.5 text-sm font-medium text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors disabled:opacity-50">
                            {{ __('subscription.promo_apply') }}
                        </button>
                    </div>
                    @error('promo_code')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    @error('payment_method')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400 hidden" id="promo-feedback">{{ __('subscription.promo_ok_months', ['months' => $promoMonths]) }}</p>
                </div>

                <button type="submit" id="pay-submit"
                    @disabled(! $paymentsEnabled && ! $promoWindowActive)
                    class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3.5 text-sm font-semibold text-white shadow-sm hover:opacity-95 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span id="pay-label">{{ $paymentsEnabled ? __('subscription.pay_now') : __('subscription.activate_with_promo') }}</span>
                </button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const listPrice = {{ $listPrice }};
    const paymentsEnabled = @json($paymentsEnabled);
    const promoWindowActive = @json($promoWindowActive);
    const amountEl = document.getElementById('amount-display');
    const promoInput = document.getElementById('promo_code');
    const promoFeedback = document.getElementById('promo-feedback');
    const promoOkMsg = document.getElementById('promo-ok-msg');
    const payLabel = document.getElementById('pay-label');
    const paySubmit = document.getElementById('pay-submit');
    const cardPanel = document.getElementById('card-panel');
    const kaspiPanel = document.getElementById('kaspi-panel');
    const freeLabel = @json(__('subscription.free_with_promo_months', ['months' => $promoMonths]));
    const activateLabel = @json(__('subscription.activate_with_promo'));
    const payNowLabel = @json(__('subscription.pay_now'));
    const moneyLabel = new Intl.NumberFormat('ru-RU').format(listPrice) + ' ₸';

    function updateAmount() {
        const hasPromoText = !!(promoInput?.value || '').trim();
        promoFeedback?.classList.toggle('hidden', !hasPromoText);
        promoOkMsg?.classList.toggle('hidden', !hasPromoText);

        if (hasPromoText) {
            if (amountEl) amountEl.textContent = freeLabel;
            if (payLabel) payLabel.textContent = activateLabel;
        } else {
            if (amountEl) amountEl.textContent = moneyLabel;
            if (payLabel) payLabel.textContent = paymentsEnabled ? payNowLabel : activateLabel;
        }

        if (!paymentsEnabled && paySubmit) {
            paySubmit.disabled = !promoWindowActive || !hasPromoText;
        }
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

    document.getElementById('checkout-form')?.addEventListener('submit', (e) => {
        if (!paymentsEnabled) {
            const code = (promoInput?.value || '').trim();
            if (!code) {
                e.preventDefault();
                promoInput?.focus();
            }
        }
    });

    updateAmount();
})();
</script>
@endsection
