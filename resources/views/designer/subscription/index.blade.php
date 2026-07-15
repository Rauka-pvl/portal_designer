@extends('layouts.dashboard')

@section('title', $isOnboarding ? __('subscription.onboarding_title') : __('subscription.title'))
@section('header_title', $isOnboarding ? '' : __('subscription.title'))

@php
    $statusLabel = __('subscription.status_'.$status);
    $planLabel = $currentPlan ? __('subscription.plan_'.$currentPlan) : null;
    $priceLabel = $planPrice !== null
        ? \App\Support\DesignerSubscription::formatMoney($planPrice).' '.__('subscription.per_month')
        : null;
    $comparisonFeatures = $comparisonFeatures ?? \App\Support\DesignerSubscription::comparisonFeatureKeys();
    $showPaymentHistory = $showPaymentHistory ?? false;
    $isOnboarding = $isOnboarding ?? ! ($hasAccess ?? false);
    $trialRequiresCard = $trialRequiresCard ?? false;
    // No pre-selection: both plan CTAs stay equal weight until the user chooses.
    $defaultSelected = null;

    $badgeClass = match ($status) {
        'active' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25',
        'trial' => 'bg-[#f59e0b]/15 text-[#fbbf24] border-[#f59e0b]/30',
        'cancelled' => 'bg-white/5 text-[#A1A09A] border-white/10',
        'payment_pending' => 'bg-amber-500/15 text-amber-300 border-amber-500/25',
        'expired' => 'bg-red-500/10 text-red-300 border-red-500/20',
        default => 'bg-white/5 text-[#A1A09A] border-white/10',
    };
@endphp

@push('styles')
<style>
    .sub-page { max-width: 1240px; margin: 0 auto; }
    .sub-onboard { max-width: 1100px; margin: 0 auto; position: relative; }
    .sub-onboard::before {
        content: '';
        position: absolute;
        left: 50%;
        top: -40px;
        transform: translateX(-50%);
        width: min(640px, 90vw);
        height: 280px;
        background: radial-gradient(ellipse at center, rgba(245,158,11,0.12), transparent 70%);
        pointer-events: none;
        z-index: 0;
    }
    .sub-onboard > * { position: relative; z-index: 1; }
    .sub-card {
        border-radius: 16px;
        border: 1px solid rgba(124,135,153,0.35);
        background: #161615;
        padding: 22px 24px;
    }
    html:not(.dark) .sub-card {
        background: #fff;
        border-color: #7c8799;
    }
    .sub-plan-card {
        border-radius: 15px;
        border: 1px solid rgba(124,135,153,0.35);
        background: #161615;
        padding: 24px;
        display: flex;
        flex-direction: column;
        min-height: 100%;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    html:not(.dark) .sub-plan-card { background: #fff; border-color: #7c8799; }
    .sub-plan-card.is-current,
    .sub-plan-card.is-selected {
        border-color: #f59e0b;
        box-shadow: 0 0 0 1px rgba(245,158,11,0.25);
    }
    .sub-plan-card:hover { border-color: rgba(245,158,11,0.55); }
    .sub-btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: .5rem; min-height: 40px; height: 42px; padding: 0 1.1rem; border-radius: 12px;
        font-size: .875rem; font-weight: 600; transition: opacity .15s ease, background .15s ease, border-color .15s ease;
    }
    .sub-btn:disabled { opacity: .55; cursor: not-allowed; }
    .sub-btn-primary { background: #f59e0b; color: #0f172a; }
    .sub-btn-primary:hover:not(:disabled) { opacity: .92; }
    .sub-btn-secondary {
        background: transparent; border: 1px solid rgba(124,135,153,.55);
        color: #EDEDEC;
    }
    html:not(.dark) .sub-btn-secondary { color: #0f172a; border-color: #7c8799; }
    .sub-btn-secondary:hover:not(:disabled) { border-color: #f59e0b; color: #f59e0b; }
    .sub-btn-text {
        background: transparent; border: 0; color: #A1A09A; height: auto; min-height: 40px; padding: 0 .25rem;
        font-weight: 500; text-decoration: underline; text-underline-offset: 3px;
    }
    .sub-btn-text:hover { color: #f59e0b; }
    .sub-btn-danger-text {
        background: transparent; border: 0; color: #f87171; height: auto; padding: 0;
        font-weight: 500; font-size: .875rem;
    }
    .sub-btn-danger-text:hover { color: #ef4444; text-decoration: underline; text-underline-offset: 3px; }
    .sub-progress {
        height: 4px; border-radius: 999px; background: rgba(255,255,255,.08); overflow: hidden;
    }
    html:not(.dark) .sub-progress { background: rgba(15,23,42,.08); }
    .sub-progress > span {
        display: block; height: 100%; border-radius: inherit;
        background: #f59e0b; opacity: .75;
    }
    .sub-modal { display: none; }
    .sub-modal.open { display: flex; }
    .sub-table th { font-weight: 500; color: #A1A09A; font-size: 12px; }
    .sub-muted { color: #A1A09A; }
    .sub-title { color: #EDEDEC; }
    html:not(.dark) .sub-title { color: #0f172a; }
    .sub-text { color: #EDEDEC; }
    html:not(.dark) .sub-text { color: #0f172a; }
    .sub-steps {
        display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem;
        font-size: .8rem; color: #A1A09A;
    }
    .sub-steps .is-active { color: #f59e0b; font-weight: 600; }
    .sub-steps .is-done { color: #EDEDEC; }
    html:not(.dark) .sub-steps .is-done { color: #0f172a; }
    .sub-sticky-cta {
        position: sticky; bottom: 0; z-index: 20;
        margin: 1.25rem -1rem -1.5rem;
        padding: .875rem 1rem calc(.875rem + env(safe-area-inset-bottom));
        border-top: 1px solid rgba(124,135,153,.35);
        background: rgba(10,10,10,.92);
        backdrop-filter: blur(8px);
    }
    html:not(.dark) .sub-sticky-cta {
        background: rgba(255,255,255,.94);
        border-top-color: #7c8799;
    }
    @media (min-width: 768px) {
        .sub-sticky-cta { display: none; }
    }
    .sub-badge-soft {
        display: inline-flex; align-items: center; border-radius: 999px;
        border: 1px solid rgba(245,158,11,.35); background: rgba(245,158,11,.12);
        color: #fbbf24; font-size: 11px; font-weight: 600; padding: .2rem .55rem;
    }
</style>
@endpush

@section('content')
@if ($isOnboarding)
{{-- ===================== ONBOARDING ===================== --}}
<div class="sub-onboard space-y-7 pb-24 md:pb-4" data-sub-onboard
    data-can-trial="{{ $canUseTrial ? '1' : '0' }}"
    data-default-plan="{{ $defaultSelected }}">

    <div class="text-center max-w-2xl mx-auto">
        <h1 class="text-2xl sm:text-[28px] font-semibold tracking-tight sub-title">
            {{ $canUseTrial ? __('subscription.onboarding_title') : __('subscription.onboarding_resume_title') }}
        </h1>
        <p class="mt-2 text-sm sm:text-base sub-muted">
            {{ $canUseTrial ? __('subscription.onboarding_subtitle') : __('subscription.onboarding_resume_subtitle') }}
        </p>
    </div>

    @if (! $canUseTrial)
        <nav class="sub-steps justify-center" aria-label="{{ __('subscription.steps_aria') }}">
            <span class="is-active">{{ __('subscription.step_plan') }}</span>
            <span aria-hidden="true">→</span>
            <span>{{ __('subscription.step_payment') }}</span>
            <span aria-hidden="true">→</span>
            <span>{{ __('subscription.step_done') }}</span>
        </nav>
    @endif

    @if (empty($plans))
        <div class="sub-card text-center py-10">
            <p class="sub-text font-medium">{{ __('subscription.plans_unavailable') }}</p>
            <a href="{{ route('subscription.index') }}" class="sub-btn sub-btn-secondary mt-4 inline-flex">{{ __('subscription.retry') }}</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-stretch" role="listbox" aria-label="{{ __('subscription.choose_plan') }}">
            @foreach ($plans as $key => $plan)
                @php
                    $isRecommended = ! empty($plan['recommended']);
                    $featureKeys = $plan['feature_keys'] ?? [];
                    $priceFormatted = \App\Support\DesignerSubscription::formatMoney((int) $plan['price']);
                @endphp
                <article
                    class="sub-plan-card"
                    role="option"
                    tabindex="0"
                    aria-selected="false"
                    data-plan-card
                    data-plan="{{ $key }}"
                    data-plan-label="{{ __('subscription.plan_'.$key) }}"
                    data-plan-price="{{ $priceFormatted }}"
                    data-checkout-url="{{ route('subscription.checkout', ['plan' => $key]) }}"
                >
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <h2 class="text-xl font-semibold sub-title">{{ __('subscription.plan_'.$key) }}</h2>
                            <p class="mt-1 text-sm sub-muted">{{ __('subscription.'.$plan['desc_key']) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 shrink-0">
                            @if ($isRecommended)
                                <span class="sub-badge-soft">{{ __('subscription.recommended') }}</span>
                            @endif
                            <span class="hidden text-[11px] font-medium text-[#f59e0b]" data-selected-badge>{{ __('subscription.selected_badge') }}</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="text-2xl font-semibold sub-title">{{ $priceFormatted }}</span>
                        <span class="text-sm sub-muted"> {{ __('subscription.per_month') }}</span>
                    </div>

                    <ul class="space-y-2 mb-3 flex-1">
                        @foreach (array_slice($featureKeys, 0, 6) as $feat)
                            <li class="flex gap-2 text-sm sub-text">
                                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
                                {{ __('subscription.'.$feat) }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs sub-muted mb-4">{{ __('subscription.'.$plan['limit_key']) }}</p>

                    <button type="button"
                        class="sub-btn sub-btn-secondary w-full"
                        data-select-plan="{{ $key }}">
                        {{ __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
                    </button>
                </article>
            @endforeach
        </div>

        @if ($canUseTrial)
            <p class="text-center text-sm sub-muted max-w-xl mx-auto" id="trial-terms-line"
                data-template="{{ __('subscription.trial_terms_line', ['days' => $trialTotalDays, 'price' => ':price']) }}">
                {{ __('subscription.trial_terms_line', [
                    'days' => $trialTotalDays,
                    'price' => '—',
                ]) }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs sm:text-sm sub-muted">
                <span>{{ __('subscription.trust_trial_days', ['days' => $trialTotalDays]) }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ __('subscription.trust_cancel_anytime') }}</span>
                <span aria-hidden="true">·</span>
                @if (! $trialRequiresCard)
                    <span>{{ __('subscription.trust_no_card') }}</span>
                @else
                    <span>{{ __('subscription.trust_pay_after_trial') }}</span>
                @endif
            </div>
        @else
            <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs sm:text-sm sub-muted">
                <span>{{ __('subscription.trust_secure_pay') }}</span>
                <span aria-hidden="true">·</span>
                <span>{{ __('subscription.trust_cancel_anytime') }}</span>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6">
            <button type="button" class="sub-btn sub-btn-text" data-open-modal="compare-modal">{{ __('subscription.compare_all') }}</button>
            <button type="button" class="sub-btn sub-btn-text" data-open-modal="trial-faq-modal">{{ __('subscription.trial_faq_link') }}</button>
        </div>

        <form method="POST" action="{{ route('subscription.purchase') }}" id="onboard-activate-form" class="hidden" data-sub-busy-form>
            @csrf
            <input type="hidden" name="plan" id="onboard-plan-input" value="">
            <input type="hidden" name="payment_method" value="kaspi">
        </form>

        <div class="hidden md:flex justify-center pt-1">
            <button type="button" id="onboard-primary-cta" class="sub-btn sub-btn-primary min-w-[280px] px-6" disabled>
                {{ __('subscription.choose_plan') }}
            </button>
        </div>

        <div class="sub-sticky-cta md:hidden hidden" id="onboard-sticky-bar">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs sub-muted truncate" id="sticky-plan-label"></p>
                </div>
                <button type="button" id="onboard-sticky-cta" class="sub-btn sub-btn-primary shrink-0 px-4">
                    {{ __('subscription.continue') }}
                </button>
            </div>
        </div>
    @endif
</div>

{{-- Compare modal --}}
<div id="compare-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="compare-title">
    <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-[#161615] p-5 sm:p-6 shadow-xl max-h-[85vh] overflow-y-auto" role="document">
        <div class="flex items-start justify-between gap-3 mb-4">
            <h3 id="compare-title" class="text-lg font-semibold text-[#EDEDEC]">{{ __('subscription.compare_title') }}</h3>
            <button type="button" class="min-w-10 min-h-10 text-[#A1A09A] hover:text-[#f59e0b]" data-close-modal="compare-modal" aria-label="{{ __('subscription.confirm_plan_cancel') }}">✕</button>
        </div>
        <div class="space-y-3 md:hidden">
            @foreach ($plans as $key => $plan)
                <div class="rounded-xl border border-white/10 p-3">
                    <div class="font-medium text-[#EDEDEC] mb-2">{{ __('subscription.plan_'.$key) }}</div>
                    <ul class="space-y-1.5">
                        @foreach (($plan['feature_keys'] ?? []) as $feat)
                            <li class="text-sm text-[#A1A09A]">{{ __('subscription.'.$feat) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10 text-left">
                        <th class="py-2 pr-3 text-[#A1A09A] font-medium">{{ __('subscription.compare_feature') }}</th>
                        @foreach ($plans as $key => $plan)
                            <th class="py-2 px-2 text-[#EDEDEC] font-medium">{{ __('subscription.plan_'.$key) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparisonFeatures as $feat)
                        <tr class="border-b border-white/5">
                            <td class="py-2.5 pr-3 text-[#A1A09A]">{{ __('subscription.'.$feat) }}</td>
                            @foreach ($plans as $key => $plan)
                                @php $has = in_array($feat, $plan['feature_keys'] ?? [], true); @endphp
                                <td class="py-2.5 px-2 {{ $has ? 'text-[#f59e0b]' : 'text-[#64748b]' }}">
                                    {{ $has ? '✓' : '—' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Trial FAQ modal --}}
<div id="trial-faq-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="trial-faq-title">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#161615] p-5 sm:p-6 shadow-xl max-h-[85vh] overflow-y-auto" role="document">
        <div class="flex items-start justify-between gap-3 mb-3">
            <h3 id="trial-faq-title" class="text-lg font-semibold text-[#EDEDEC]">{{ __('subscription.trial_faq_title') }}</h3>
            <button type="button" class="min-w-10 min-h-10 text-[#A1A09A] hover:text-[#f59e0b]" data-close-modal="trial-faq-modal" aria-label="{{ __('subscription.confirm_plan_cancel') }}">✕</button>
        </div>
        <dl class="space-y-4 text-sm">
            <div>
                <dt class="font-medium text-[#EDEDEC]">{{ __('subscription.trial_faq_q1') }}</dt>
                <dd class="mt-1 text-[#A1A09A]">{{ __('subscription.trial_faq_a1', ['days' => $trialTotalDays]) }}</dd>
            </div>
            <div>
                <dt class="font-medium text-[#EDEDEC]">{{ __('subscription.trial_faq_q2') }}</dt>
                <dd class="mt-1 text-[#A1A09A]">{{ __('subscription.trial_faq_a2') }}</dd>
            </div>
            <div>
                <dt class="font-medium text-[#EDEDEC]">{{ __('subscription.trial_faq_q3') }}</dt>
                <dd class="mt-1 text-[#A1A09A]">{{ __('subscription.trial_faq_a3') }}</dd>
            </div>
            <div>
                <dt class="font-medium text-[#EDEDEC]">{{ __('subscription.trial_faq_q4') }}</dt>
                <dd class="mt-1 text-[#A1A09A]">{{ __('subscription.trial_faq_a4') }}</dd>
            </div>
            <div>
                <dt class="font-medium text-[#EDEDEC]">{{ __('subscription.trial_faq_q5') }}</dt>
                <dd class="mt-1 text-[#A1A09A]">{{ __('subscription.trial_faq_a5') }}</dd>
            </div>
        </dl>
        @if (Route::has('faq.index'))
            <a href="{{ \App\Support\BackNavigation::withFrom(route('faq.index')) }}" class="sub-btn sub-btn-secondary w-full mt-5">{{ __('subscription.need_help') }}</a>
        @endif
    </div>
</div>

@else
{{-- ===================== MANAGEMENT ===================== --}}
<div class="sub-page space-y-7">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-[26px] font-semibold tracking-tight sub-title">{{ __('subscription.title') }}</h1>
            <p class="mt-1.5 text-sm sub-muted">{{ __('subscription.page_subtitle') }}</p>
        </div>
        @if (Route::has('faq.index'))
            <a href="{{ \App\Support\BackNavigation::withFrom(route('faq.index')) }}" class="sub-btn sub-btn-secondary shrink-0 self-start">
                {{ __('subscription.need_help') }}
            </a>
        @endif
    </div>

    <section class="sub-card" aria-labelledby="sub-current-heading">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-5">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 id="sub-current-heading" class="text-[22px] font-semibold sub-title">
                        {{ $planLabel ?? __('subscription.status_none') }}
                    </h2>
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                @if ($status === 'expired')
                    <p class="mt-2 text-sm sub-muted">{{ __('subscription.expired_title') }}</p>
                @elseif ($status === 'payment_pending')
                    <p class="mt-2 text-sm text-amber-300">{{ __('subscription.payment_failed_title') }}</p>
                @elseif ($status === 'cancelled')
                    <p class="mt-2 text-sm sub-muted">{{ __('subscription.cancelled_title') }}</p>
                @endif
            </div>
            @if ($priceLabel)
                <div class="text-left lg:text-right">
                    <div class="text-xl font-semibold sub-title">{{ $priceLabel }}</div>
                    @if ($isOnTrial && $nextChargeAmountLabel)
                        <div class="mt-1 text-xs sub-muted">{{ __('subscription.future_price', ['amount' => $nextChargeAmountLabel]) }}</div>
                    @endif
                </div>
            @endif
        </div>

        @if ($hasAccess || $accessEndsAtLabel)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
                @if ($isOnTrial && $accessEndsAtLabel)
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.trial_until_label') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ $accessEndsAtLabel }}</div>
                    </div>
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.days_left_label') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ $trialDaysLeft }}</div>
                    </div>
                @elseif ($accessEndsAtLabel)
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.access_until_label') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ $accessEndsAtLabel }}</div>
                    </div>
                @endif

                @if ($nextChargeAtLabel)
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.next_charge_label') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ $nextChargeAtLabel }}</div>
                    </div>
                @elseif ($status === 'cancelled')
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.auto_renew_off') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ __('subscription.no_next_charge') }}</div>
                    </div>
                @endif

                @if ($nextChargeAmountLabel && $status !== 'cancelled')
                    <div class="rounded-xl border border-white/5 bg-white/[0.02] px-3.5 py-3">
                        <div class="text-[11px] uppercase tracking-wide sub-muted">{{ __('subscription.next_charge_amount_label') }}</div>
                        <div class="mt-1 text-sm sub-text">{{ $nextChargeAmountLabel }}</div>
                    </div>
                @endif
            </div>
        @endif

        @if ($isOnTrial)
            <div class="mb-5">
                <div class="flex justify-between text-[11px] sub-muted mb-1.5">
                    <span>{{ __('subscription.progress_start') }}</span>
                    <span>{{ __('subscription.progress_left', ['days' => $trialDaysLeft]) }}</span>
                    <span>{{ __('subscription.progress_end') }}</span>
                </div>
                <div class="sub-progress" role="progressbar" aria-valuenow="{{ $trialProgress }}" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: {{ $trialProgress }}%"></span>
                </div>
            </div>
        @endif

        @if ($autoRenew)
            <p class="mb-4 text-xs sub-muted">{{ __('subscription.auto_renew_on') }}</p>
        @elseif ($hasAccess && $cancelledAt)
            <p class="mb-4 text-xs sub-muted">{{ __('subscription.auto_renew_off') }} · {{ __('subscription.access_until', ['date' => $accessEndsAtLabel ?? '—']) }}</p>
        @endif

        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3">
            @if (($primaryAction['key'] ?? '') === 'resume' && $hasAccess)
                <form method="POST" action="{{ route('subscription.resume') }}" class="contents" data-sub-busy-form>
                    @csrf
                    <button type="submit" class="sub-btn sub-btn-primary w-full sm:w-auto">{{ $primaryAction['label'] }}</button>
                </form>
            @elseif (!empty($primaryAction['href']))
                <a href="{{ $primaryAction['href'] }}" class="sub-btn sub-btn-primary w-full sm:w-auto">{{ $primaryAction['label'] }}</a>
            @elseif (($primaryAction['key'] ?? '') === 'update_payment')
                <button type="button" class="sub-btn sub-btn-primary w-full sm:w-auto" data-open-modal="payment-modal">{{ $primaryAction['label'] }}</button>
            @endif

            @if ($hasAccess)
                <button type="button" class="sub-btn sub-btn-secondary w-full sm:w-auto" data-open-modal="plans-anchor">{{ __('subscription.cta_change_plan') }}</button>
            @endif

            @if ($currentPlan)
                <button type="button" class="sub-btn sub-btn-text self-center" data-open-modal="plan-details-modal">{{ __('subscription.cta_plan_details') }}</button>
            @endif
        </div>
    </section>

    <section id="plans" aria-labelledby="plans-heading">
        <div class="mb-4">
            <h2 id="plans-heading" class="text-lg font-semibold sub-title">{{ __('subscription.choose_plan') }}</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($plans as $key => $plan)
                @php
                    $isCurrent = $currentPlan === $key && $hasAccess;
                    $featureKeys = $plan['feature_keys'] ?? [];
                @endphp
                <article class="sub-plan-card {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-xl font-semibold sub-title">{{ __('subscription.plan_'.$key) }}</h3>
                            <p class="mt-1 text-sm sub-muted">{{ __('subscription.'.$plan['desc_key']) }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5">
                            @if (! empty($plan['recommended']))
                                <span class="sub-badge-soft">{{ __('subscription.recommended') }}</span>
                            @endif
                            @if ($isCurrent)
                                <span class="shrink-0 inline-flex items-center rounded-full border border-[#f59e0b]/40 px-2.5 py-0.5 text-[11px] font-medium text-[#f59e0b]">
                                    {{ __('subscription.current_plan_badge') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-4">
                        <span class="text-2xl font-semibold sub-title">{{ \App\Support\DesignerSubscription::formatMoney((int) $plan['price']) }}</span>
                        <span class="text-sm sub-muted"> {{ __('subscription.per_month') }}</span>
                    </div>

                    <ul class="space-y-2 mb-3 flex-1">
                        @foreach ($featureKeys as $feat)
                            <li class="flex gap-2 text-sm sub-text">
                                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
                                {{ __('subscription.'.$feat) }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs sub-muted mb-4">{{ __('subscription.'.$plan['limit_key']) }}</p>

                    @if ($isCurrent)
                        <button type="button" class="sub-btn sub-btn-secondary w-full" disabled>{{ __('subscription.current_plan_badge') }}</button>
                    @else
                        <button type="button"
                            class="sub-btn sub-btn-secondary w-full"
                            data-open-change-plan="{{ $key }}"
                            data-plan-label="{{ __('subscription.plan_'.$key) }}"
                            data-plan-price="{{ \App\Support\DesignerSubscription::formatMoney((int) $plan['price']) }}">
                            {{ __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
                        </button>
                    @endif
                </article>
            @endforeach
        </div>
        <p class="mt-3 text-xs sub-muted">{{ __('subscription.plan_apply_now') }}</p>
    </section>

    @if ($hasAccess || $paymentMethod)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <section class="sub-card" aria-labelledby="pay-heading">
                <h2 id="pay-heading" class="text-lg font-semibold sub-title mb-4">{{ __('subscription.payment_section') }}</h2>
                <div class="flex items-start gap-3 mb-4">
                    <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-[#f59e0b]" aria-hidden="true">
                        @if (($paymentMethod ?? '') === 'card')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-medium sub-text">
                            {{ ($paymentMethod ?? '') === 'card' ? __('subscription.pay_card') : __('subscription.pay_kaspi') }}
                        </div>
                        @if (($paymentMethod ?? '') === 'card' && $cardLast4)
                            <div class="mt-0.5 text-sm sub-muted">{{ __('subscription.payment_card_masked', ['last4' => $cardLast4]) }}</div>
                            @if ($cardExpiry)
                                <div class="text-xs sub-muted">{{ __('subscription.payment_card_expiry', ['expiry' => $cardExpiry]) }}</div>
                            @endif
                        @else
                            <div class="mt-0.5 text-sm sub-muted">{{ __('subscription.payment_current_kaspi') }}</div>
                        @endif
                    </div>
                </div>
                <button type="button" class="sub-btn sub-btn-secondary" data-open-modal="payment-modal">{{ __('subscription.payment_change') }}</button>
                <p class="mt-3 text-xs sub-muted">{{ __('subscription.payment_secure') }}</p>
            </section>

            <section class="sub-card" aria-labelledby="billing-heading">
                <h2 id="billing-heading" class="text-lg font-semibold sub-title mb-4">{{ __('subscription.billing_section') }}</h2>
                <dl class="space-y-3 text-sm mb-4">
                    <div>
                        <dt class="text-xs sub-muted">{{ __('subscription.billing_name') }}</dt>
                        <dd class="mt-0.5 sub-text">{{ $billingName }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs sub-muted">{{ __('subscription.billing_email') }}</dt>
                        <dd class="mt-0.5 sub-text">{{ $billingEmail }}</dd>
                    </div>
                </dl>
                @if (Route::has('settings.index'))
                    <a href="{{ route('settings.index') }}" class="sub-btn sub-btn-text">{{ __('subscription.billing_edit') }}</a>
                    <p class="mt-2 text-xs sub-muted">{{ __('subscription.billing_edit_hint') }}</p>
                @endif
            </section>
        </div>
    @endif

    @if ($showPaymentHistory)
        <section class="sub-card !p-0 overflow-hidden" aria-labelledby="history-heading">
            <div class="px-5 sm:px-6 py-4 border-b border-white/5">
                <h2 id="history-heading" class="text-lg font-semibold sub-title">{{ __('subscription.history') }}</h2>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="sub-table w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/5 text-left">
                            <th class="px-5 py-3">{{ __('subscription.history_date') }}</th>
                            <th class="px-3 py-3">{{ __('subscription.history_plan') }}</th>
                            <th class="px-3 py-3">{{ __('subscription.history_period') }}</th>
                            <th class="px-3 py-3">{{ __('subscription.history_method') }}</th>
                            <th class="px-3 py-3">{{ __('subscription.history_amount') }}</th>
                            <th class="px-3 py-3">{{ __('subscription.history_status') }}</th>
                            <th class="px-5 py-3 text-right">{{ __('subscription.history_document') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $row)
                            <tr class="border-b border-white/5 last:border-0">
                                <td class="px-5 py-3.5 sub-text whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="px-3 py-3.5 sub-text">{{ $row['plan_label'] }}</td>
                                <td class="px-3 py-3.5 sub-muted whitespace-nowrap">{{ $row['period'] }}</td>
                                <td class="px-3 py-3.5 sub-muted">{{ $row['method_label'] }}</td>
                                <td class="px-3 py-3.5 sub-text whitespace-nowrap">
                                    {{ $row['amount_label'] }}
                                    @if ($row['is_trial'] && $row['list_price_label'])
                                        <div class="text-[11px] sub-muted">{{ __('subscription.future_price', ['amount' => $row['list_price_label']]) }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5">
                                    <span class="text-xs sub-muted">{{ $row['status_label'] }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="text-xs sub-muted">{{ __('subscription.history_no_doc') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-white/5">
                @foreach ($payments as $row)
                    <div class="px-5 py-4 space-y-1.5">
                        <div class="flex justify-between gap-3">
                            <span class="text-sm font-medium sub-text">{{ $row['plan_label'] }}</span>
                            <span class="text-sm sub-text">{{ $row['amount_label'] }}</span>
                        </div>
                        <div class="text-xs sub-muted">{{ $row['date'] }} · {{ $row['method_label'] }}</div>
                        <div class="text-xs sub-muted">{{ $row['period'] }}</div>
                        <div class="text-xs sub-muted">{{ $row['status_label'] }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($hasAccess && ! $cancelledAt)
        <section class="pt-2" aria-labelledby="manage-heading">
            <h2 id="manage-heading" class="text-base font-semibold sub-title mb-1">{{ __('subscription.manage_section') }}</h2>
            <p class="text-sm sub-muted max-w-2xl mb-3">{{ __('subscription.cancel_hint') }}</p>
            <button type="button" class="sub-btn-danger-text" data-open-modal="cancel-modal">{{ __('subscription.cancel_sub') }}</button>
        </section>
    @endif
</div>

{{-- Change plan modal --}}
<div id="change-plan-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="change-plan-title">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#161615] p-6 shadow-xl" role="document">
        <h3 id="change-plan-title" class="text-lg font-semibold text-[#EDEDEC]">{{ __('subscription.confirm_plan_title') }}</h3>
        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between gap-3"><dt class="sub-muted">{{ __('subscription.confirm_plan_current') }}</dt><dd class="text-[#EDEDEC]">{{ $planLabel ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="sub-muted">{{ __('subscription.confirm_plan_new') }}</dt><dd class="text-[#EDEDEC]" id="change-plan-new">—</dd></div>
            <div class="flex justify-between gap-3"><dt class="sub-muted">{{ __('subscription.confirm_plan_price') }}</dt><dd class="text-[#EDEDEC]" id="change-plan-price">—</dd></div>
            <div class="flex justify-between gap-3"><dt class="sub-muted">{{ __('subscription.confirm_plan_date') }}</dt><dd class="text-[#EDEDEC]">{{ __('subscription.confirm_plan_date_now') }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('subscription.change-plan') }}" class="mt-6 space-y-3" data-sub-busy-form>
            @csrf
            <input type="hidden" name="plan" id="change-plan-input" value="">
            <button type="submit" class="sub-btn sub-btn-primary w-full">{{ __('subscription.confirm_plan_submit') }}</button>
            <button type="button" class="sub-btn sub-btn-secondary w-full" data-close-modal="change-plan-modal">{{ __('subscription.confirm_plan_cancel') }}</button>
        </form>
    </div>
</div>

{{-- Payment method modal --}}
<div id="payment-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="payment-modal-title">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#161615] p-6 shadow-xl" role="document">
        <h3 id="payment-modal-title" class="text-lg font-semibold text-[#EDEDEC]">{{ __('subscription.payment_modal_title') }}</h3>
        <form method="POST" action="{{ route('subscription.payment') }}" class="mt-5 space-y-3" data-sub-busy-form>
            @csrf
            <label class="flex items-start gap-3 rounded-xl border border-white/10 p-3 cursor-pointer has-[:checked]:border-[#f59e0b]">
                <input type="radio" name="payment_method" value="kaspi" class="mt-1 text-[#f59e0b] focus:ring-[#f59e0b]" @checked(($paymentMethod ?? 'kaspi') !== 'card')>
                <span>
                    <span class="block text-sm font-medium text-[#EDEDEC]">{{ __('subscription.pay_kaspi') }}</span>
                    <span class="block text-xs sub-muted">{{ __('subscription.pay_kaspi_hint') }}</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-xl border border-white/10 p-3 cursor-pointer has-[:checked]:border-[#f59e0b]">
                <input type="radio" name="payment_method" value="card" class="mt-1 text-[#f59e0b] focus:ring-[#f59e0b]" @checked(($paymentMethod ?? '') === 'card')>
                <span>
                    <span class="block text-sm font-medium text-[#EDEDEC]">{{ __('subscription.pay_card') }}</span>
                    <span class="block text-xs sub-muted">{{ __('subscription.pay_card_hint') }}</span>
                </span>
            </label>
            <button type="submit" class="sub-btn sub-btn-primary w-full">{{ __('subscription.payment_save') }}</button>
            <button type="button" class="sub-btn sub-btn-secondary w-full" data-close-modal="payment-modal">{{ __('subscription.confirm_plan_cancel') }}</button>
        </form>
    </div>
</div>

{{-- Plan details modal --}}
<div id="plan-details-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="plan-details-title">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#161615] p-6 shadow-xl" role="document">
        <h3 id="plan-details-title" class="text-lg font-semibold text-[#EDEDEC]">{{ $planLabel ?? __('subscription.title') }}</h3>
        <p class="mt-2 text-sm sub-muted">{{ $currentPlan ? __('subscription.plan_'.$currentPlan.'_desc') : '' }}</p>
        @if ($priceLabel)
            <p class="mt-3 text-base font-semibold text-[#EDEDEC]">{{ $priceLabel }}</p>
        @endif
        <ul class="mt-4 space-y-2">
            @foreach (($plans[$currentPlan]['feature_keys'] ?? []) as $feat)
                <li class="flex gap-2 text-sm text-[#EDEDEC]">
                    <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0"></span>
                    {{ __('subscription.'.$feat) }}
                </li>
            @endforeach
        </ul>
        <button type="button" class="sub-btn sub-btn-secondary w-full mt-6" data-close-modal="plan-details-modal">{{ __('subscription.confirm_plan_cancel') }}</button>
    </div>
</div>

{{-- Cancel modal --}}
<div id="cancel-modal" class="sub-modal fixed inset-0 z-[90] items-center justify-center p-4 bg-black/60" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-[#161615] p-6 shadow-xl" role="document">
        <h3 id="cancel-modal-title" class="text-lg font-semibold text-[#EDEDEC]">{{ __('subscription.cancel_modal_title') }}</h3>
        <p class="mt-2 text-sm sub-muted">
            {{ __('subscription.cancel_modal_body', ['plan' => $planLabel ?? '—', 'date' => $accessEndsAtLabel ?? '—']) }}
        </p>
        <form method="POST" action="{{ route('subscription.cancel') }}" class="mt-5 space-y-4" data-sub-busy-form>
            @csrf
            <div>
                <label class="block text-xs sub-muted mb-2">{{ __('subscription.cancel_reason_label') }}</label>
                <div class="space-y-2">
                    @foreach (['expensive','not_using','missing_features','tech_issues','other'] as $reason)
                        <label class="flex items-center gap-2 text-sm text-[#EDEDEC] cursor-pointer">
                            <input type="radio" name="reason" value="{{ $reason }}" class="text-[#f59e0b] focus:ring-[#f59e0b] border-white/20">
                            {{ __('subscription.cancel_reason_'.$reason) }}
                        </label>
                    @endforeach
                </div>
            </div>
            <button type="button" class="sub-btn sub-btn-primary w-full" data-close-modal="cancel-modal">{{ __('subscription.cancel_keep') }}</button>
            <button type="submit" class="sub-btn sub-btn-secondary w-full !border-red-500/40 !text-red-300 hover:!border-red-400">{{ __('subscription.cancel_confirm') }}</button>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
(function () {
    @if (session('success'))
        if (typeof projectAlert === 'function') {
            projectAlert('success', @json(session('success')));
        }
    @endif
    @if ($errors->any())
        if (typeof projectAlert === 'function') {
            projectAlert('error', @json($errors->first()));
        }
    @endif

    const openers = document.querySelectorAll('[data-open-modal]');
    const closers = document.querySelectorAll('[data-close-modal]');
    let lastFocus = null;

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        lastFocus = document.activeElement;
        modal.classList.add('open');
        const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        focusable?.focus();
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('open');
        if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    }

    openers.forEach((btn) => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-open-modal');
            if (id === 'plans-anchor') {
                document.getElementById('plans')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
            openModal(id);
        });
    });

    closers.forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.getAttribute('data-close-modal')));
    });

    document.querySelectorAll('.sub-modal').forEach((modal) => {
        modal.addEventListener('mousedown', (e) => {
            if (e.target === modal) closeModal(modal.id);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(modal.id);
        });
    });

    document.querySelectorAll('[data-open-change-plan]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('change-plan-input').value = btn.getAttribute('data-open-change-plan');
            document.getElementById('change-plan-new').textContent = btn.getAttribute('data-plan-label') || '—';
            document.getElementById('change-plan-price').textContent = btn.getAttribute('data-plan-price') || '—';
            openModal('change-plan-modal');
        });
    });

    document.querySelectorAll('[data-sub-busy-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('button[type="submit"]');
            if (!btn) return;
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = @json(__('subscription.saving'));
        });
    });

    // Onboarding plan selection
    const onboard = document.querySelector('[data-sub-onboard]');
    if (!onboard) return;

    const canTrial = onboard.dataset.canTrial === '1';
    const i18n = {
        startTrial: @json(__('subscription.start_trial_with')),
        continueWith: @json(__('subscription.continue_with')),
        sticky: @json(__('subscription.sticky_selected')),
        loading: @json(__('subscription.saving')),
        perMonth: @json(__('subscription.per_month')),
    };
    let selected = onboard.dataset.defaultPlan || null;
    let busy = false;

    function planCard(key) {
        return onboard.querySelector(`[data-plan-card][data-plan="${key}"]`);
    }

    function updateSelection(key) {
        selected = key;
        const input = document.getElementById('onboard-plan-input');
        if (input) input.value = key;

        onboard.querySelectorAll('[data-plan-card]').forEach((card) => {
            const isSel = card.dataset.plan === key;
            card.classList.toggle('is-selected', isSel);
            card.setAttribute('aria-selected', isSel ? 'true' : 'false');
            const badge = card.querySelector('[data-selected-badge]');
            if (badge) badge.classList.toggle('hidden', !isSel);
            const btn = card.querySelector('[data-select-plan]');
            if (btn) {
                btn.classList.toggle('sub-btn-primary', isSel);
                btn.classList.toggle('sub-btn-secondary', !isSel);
            }
        });

        const card = planCard(key);
        const label = card?.dataset.planLabel || key;
        const price = card?.dataset.planPrice || '';
        const primary = document.getElementById('onboard-primary-cta');
        const tpl = canTrial ? i18n.startTrial : i18n.continueWith;
        if (primary) {
            primary.disabled = false;
            primary.textContent = tpl.replace(':plan', label);
        }
        const stickyBar = document.getElementById('onboard-sticky-bar');
        const stickyLabel = document.getElementById('sticky-plan-label');
        if (stickyBar) stickyBar.classList.remove('hidden');
        if (stickyLabel) {
            stickyLabel.textContent = i18n.sticky
                .replace(':plan', label)
                .replace(':price', price + ' ' + i18n.perMonth);
        }
        const terms = document.getElementById('trial-terms-line');
        if (terms?.dataset.template) {
            terms.textContent = terms.dataset.template.replace(':price', price);
        }
    }

    function activate() {
        if (busy || !selected) return;
        const card = planCard(selected);
        if (!card) return;

        if (!canTrial) {
            window.location.href = card.dataset.checkoutUrl;
            return;
        }

        const form = document.getElementById('onboard-activate-form');
        if (!form) return;
        busy = true;
        [document.getElementById('onboard-primary-cta'), document.getElementById('onboard-sticky-cta')]
            .forEach((btn) => {
                if (!btn) return;
                btn.disabled = true;
                btn.textContent = i18n.loading;
            });
        form.requestSubmit ? form.requestSubmit() : form.submit();
    }

    onboard.querySelectorAll('[data-select-plan]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            updateSelection(btn.getAttribute('data-select-plan'));
        });
    });
    onboard.querySelectorAll('[data-plan-card]').forEach((card) => {
        card.addEventListener('click', () => updateSelection(card.dataset.plan));
        card.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                updateSelection(card.dataset.plan);
            }
        });
    });
    document.getElementById('onboard-primary-cta')?.addEventListener('click', activate);
    document.getElementById('onboard-sticky-cta')?.addEventListener('click', activate);

})();
</script>
@endsection
