@extends('layouts.dashboard')

@section('title', __('subscription.title'))
@section('header_title', __('subscription.title'))

@php
    $statusLabel = __('subscription.status_'.$status);
    $planLabel = $currentPlan ? __('subscription.plan_'.$currentPlan) : null;
    $priceLabel = $planPrice !== null
        ? \App\Support\DesignerSubscription::formatMoney($planPrice).' '.__('subscription.per_month')
        : null;

    $standardFeatures = ['feature_clients','feature_projects','feature_orders','feature_reports','feature_support'];
    $proFeatures = ['feature_unlimited','feature_analytics','feature_priority','feature_pro_tools','feature_cashback','feature_suppliers'];

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
        border-radius: 16px;
        border: 1px solid rgba(124,135,153,0.35);
        background: #161615;
        padding: 22px 24px;
        display: flex;
        flex-direction: column;
        min-height: 100%;
        transition: border-color .15s ease;
    }
    html:not(.dark) .sub-plan-card { background: #fff; border-color: #7c8799; }
    .sub-plan-card.is-current { border-color: #f59e0b; }
    .sub-btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: .5rem; height: 42px; padding: 0 1.1rem; border-radius: 12px;
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
        background: transparent; border: 0; color: #A1A09A; height: auto; padding: 0;
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
</style>
@endpush

@section('content')
<div class="sub-page space-y-7">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-[26px] font-semibold tracking-tight sub-title">{{ __('subscription.title') }}</h1>
            <p class="mt-1.5 text-sm sub-muted">{{ __('subscription.page_subtitle') }}</p>
        </div>
        @if (Route::has('faq.index'))
            <a href="{{ route('faq.index') }}" class="sub-btn sub-btn-secondary shrink-0 self-start">
                {{ __('subscription.need_help') }}
            </a>
        @endif
    </div>

    {{-- Current subscription card --}}
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
                @elseif ($status === 'none')
                    <p class="mt-2 text-sm sub-muted">{{ __('subscription.blocked_subtitle') }}</p>
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
            @else
                <a href="#plans" class="sub-btn sub-btn-primary w-full sm:w-auto">{{ $primaryAction['label'] }}</a>
            @endif

            @if ($hasAccess)
                <button type="button" class="sub-btn sub-btn-secondary w-full sm:w-auto" data-open-modal="plans-anchor">{{ __('subscription.cta_change_plan') }}</button>
            @endif

            @if ($currentPlan)
                <button type="button" class="sub-btn sub-btn-text self-center" data-open-modal="plan-details-modal">{{ __('subscription.cta_plan_details') }}</button>
            @endif
        </div>
    </section>

    {{-- Plans --}}
    <section id="plans" aria-labelledby="plans-heading">
        <div class="mb-4">
            <h2 id="plans-heading" class="text-lg font-semibold sub-title">{{ __('subscription.choose_plan') }}</h2>
            @if ($canUseTrial)
                <p class="mt-1 text-sm sub-muted">{{ __('subscription.trial_hint') }}</p>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($plans as $key => $plan)
                @php $isCurrent = $currentPlan === $key && $hasAccess; @endphp
                <article class="sub-plan-card {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-xl font-semibold sub-title">{{ __('subscription.plan_'.$key) }}</h3>
                            <p class="mt-1 text-sm sub-muted">{{ __('subscription.plan_'.$key.'_desc') }}</p>
                        </div>
                        @if ($isCurrent)
                            <span class="shrink-0 inline-flex items-center rounded-full border border-[#f59e0b]/40 px-2.5 py-0.5 text-[11px] font-medium text-[#f59e0b]">
                                {{ __('subscription.current_plan_badge') }}
                            </span>
                        @endif
                    </div>

                    <div class="mb-4">
                        <span class="text-2xl font-semibold sub-title">{{ \App\Support\DesignerSubscription::formatMoney((int) $plan['price']) }}</span>
                        <span class="text-sm sub-muted"> {{ __('subscription.per_month') }}</span>
                    </div>

                    <ul class="space-y-2 mb-3 flex-1">
                        @foreach (($key === 'pro' ? $proFeatures : $standardFeatures) as $feat)
                            <li class="flex gap-2 text-sm sub-text">
                                <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#f59e0b] shrink-0" aria-hidden="true"></span>
                                {{ __('subscription.'.$feat) }}
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs sub-muted mb-4">{{ __('subscription.plan_'.$key.'_limit') }}</p>

                    @if ($isCurrent)
                        <button type="button" class="sub-btn sub-btn-secondary w-full" disabled>{{ __('subscription.current_plan_badge') }}</button>
                    @elseif ($hasAccess)
                        <button type="button"
                            class="sub-btn {{ $key === 'pro' ? 'sub-btn-primary' : 'sub-btn-secondary' }} w-full"
                            data-open-change-plan="{{ $key }}"
                            data-plan-label="{{ __('subscription.plan_'.$key) }}"
                            data-plan-price="{{ \App\Support\DesignerSubscription::formatMoney((int) $plan['price']) }}">
                            {{ __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
                        </button>
                    @else
                        <a href="{{ route('subscription.checkout', ['plan' => $key]) }}"
                            class="sub-btn {{ $key === 'pro' ? 'sub-btn-primary' : 'sub-btn-secondary' }} w-full">
                            {{ $canUseTrial ? __('subscription.start_trial') : __('subscription.select_plan', ['plan' => __('subscription.plan_'.$key)]) }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
        @if ($hasAccess)
            <p class="mt-3 text-xs sub-muted">{{ __('subscription.plan_apply_now') }}</p>
        @endif
    </section>

    {{-- Payment + billing --}}
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

    {{-- History --}}
    <section class="sub-card !p-0 overflow-hidden" aria-labelledby="history-heading">
        <div class="px-5 sm:px-6 py-4 border-b border-white/5">
            <h2 id="history-heading" class="text-lg font-semibold sub-title">{{ __('subscription.history') }}</h2>
        </div>

        @if ($payments->isEmpty())
            <div class="px-5 sm:px-6 py-12 text-center">
                <p class="text-sm font-medium sub-text">{{ __('subscription.history_empty') }}</p>
                <p class="mt-1 text-sm sub-muted">{{ __('subscription.history_empty_hint') }}</p>
            </div>
        @else
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
                                    @if ($row['has_receipt'])
                                        <span class="text-xs sub-muted">{{ __('subscription.history_no_doc') }}</span>
                                    @else
                                        <span class="text-xs sub-muted">{{ __('subscription.history_no_doc') }}</span>
                                    @endif
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
        @endif
    </section>

    {{-- Danger zone --}}
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
            @foreach (($currentPlan === 'pro' ? $proFeatures : $standardFeatures) as $feat)
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
        modal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeModal(modal.id);
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
            const original = btn.textContent;
            btn.dataset.originalText = original;
            btn.textContent = @json(__('subscription.saving'));
        });
    });
})();
</script>
@endsection
