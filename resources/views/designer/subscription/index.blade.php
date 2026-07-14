@extends('layouts.dashboard')

@section('title', __('subscription.title'))
@section('header_title', __('subscription.title'))

@php
    $statusLabels = [
        'none' => __('subscription.status_none'),
        'trial' => __('subscription.status_trial'),
        'active' => __('subscription.status_active'),
        'expired' => __('subscription.status_expired'),
        'cancelled' => __('subscription.status_cancelled'),
    ];
    $standard = $plans['standard'];
    $pro = $plans['pro'];
@endphp

@push('styles')
<style>
    .sub-hero {
        background:
            radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.25), transparent 55%),
            radial-gradient(ellipse 50% 60% at 0% 100%, rgba(251, 146, 60, 0.12), transparent 50%),
            #0f172a;
    }
    .dark .sub-hero {
        background:
            radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.3), transparent 55%),
            radial-gradient(ellipse 50% 60% at 0% 100%, rgba(251, 146, 60, 0.14), transparent 50%),
            #161615;
    }
    .sub-plan { transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
    .sub-plan:hover { transform: translateY(-2px); }
    .sub-plan-featured {
        border-color: #f59e0b !important;
        box-shadow: 0 0 0 1px #f59e0b, 0 18px 40px -24px rgba(245, 158, 11, .55);
    }
    .sub-feature-dot {
        width: 6px; height: 6px; border-radius: 999px; background: #f59e0b; flex-shrink: 0;
    }
</style>
@endpush

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/30 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="sub-hero relative overflow-hidden rounded-2xl p-6 sm:p-8 mb-6 text-white">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-[#f59e0b]/15 blur-3xl pointer-events-none"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs text-white/80 mb-4">
                    <svg class="w-3.5 h-3.5 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('subscription.trial_badge') }} · {{ __('subscription.trial_period') }}
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">
                    {{ $hasAccess ? __('subscription.manage_title') : __('subscription.blocked_title') }}
                </h1>
                <p class="mt-3 text-sm sm:text-base text-white/65 leading-relaxed">
                    {{ $hasAccess ? __('subscription.manage_subtitle') : __('subscription.blocked_subtitle') }}
                </p>
                @if ($canUseTrial)
                    <p class="mt-3 text-sm text-[#fbbf24]">{{ __('subscription.trial_hint') }}</p>
                @endif
            </div>

            <div class="rounded-xl border border-white/10 bg-white/5 backdrop-blur-sm px-5 py-4 min-w-[220px]">
                <div class="text-xs uppercase tracking-wide text-white/50 mb-1">{{ __('subscription.title') }}</div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                        {{ $status === 'active' ? 'bg-emerald-500/20 text-emerald-300' : ($status === 'trial' ? 'bg-[#f59e0b]/20 text-[#fbbf24]' : 'bg-white/10 text-white/70') }}">
                        {{ $statusLabels[$status] ?? $status }}
                    </span>
                    @if ($currentPlan)
                        <span class="text-sm font-medium text-white">{{ __('subscription.plan_' . $currentPlan) }}</span>
                    @endif
                </div>
                <div class="mt-2 text-sm text-white/60">
                    @if ($cancelledAt && $hasAccess)
                        {{ __('subscription.cancelled_badge') }}
                    @elseif ($isOnTrial)
                        {{ __('subscription.trial_active') }}
                        <div class="text-[#fbbf24] font-medium mt-0.5">{{ __('subscription.trial_days_left', ['days' => $trialDaysLeft]) }}</div>
                    @elseif ($status === 'expired' || $status === 'none')
                        {{ $status === 'none' ? __('subscription.blocked_subtitle') : __('subscription.trial_ended') }}
                    @elseif ($accessEndsAt)
                        {{ __('subscription.active_until', ['date' => $accessEndsAt->format('d.m.Y')]) }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($hasAccess)
        {{-- Управление --}}
        <div class="mb-3">
            <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.tools') }}</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8 max-w-5xl">
            {{-- Change plan --}}
            <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    <h3 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.change_plan') }}</h3>
                </div>
                <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('subscription.change_plan_hint') }}</p>
                <form method="POST" action="{{ route('subscription.change-plan') }}" class="space-y-3">
                    @csrf
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="plan" value="standard" @checked($currentPlan === 'standard') class="text-[#f59e0b] focus:ring-[#f59e0b]">
                        <span class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.plan_standard') }} · {{ number_format($standard['price'], 0, ',', ' ') }} ₸</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="plan" value="pro" @checked($currentPlan === 'pro') class="text-[#f59e0b] focus:ring-[#f59e0b]">
                        <span class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.plan_pro') }} · {{ number_format($pro['price'], 0, ',', ' ') }} ₸</span>
                    </label>
                    <button type="submit" class="w-full mt-2 rounded-xl border border-[#0f172a] dark:border-[#EDEDEC] bg-[#0f172a] dark:bg-[#EDEDEC] px-4 py-2.5 text-sm font-medium text-white dark:text-[#0f172a] hover:opacity-90">
                        {{ __('subscription.save_plan') }}
                    </button>
                </form>
            </div>

            {{-- Payment method --}}
            <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <h3 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.payment_method') }}</h3>
                </div>
                <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-4">Kaspi QR / {{ __('subscription.pay_card') }}</p>
                <form method="POST" action="{{ route('subscription.payment') }}" class="space-y-3">
                    @csrf
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="kaspi" @checked(($paymentMethod ?? 'kaspi') === 'kaspi') class="text-[#f59e0b] focus:ring-[#f59e0b]">
                        <span class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_kaspi') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="payment_method" value="card" @checked($paymentMethod === 'card') class="text-[#f59e0b] focus:ring-[#f59e0b]">
                        <span class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.pay_card') }}</span>
                    </label>
                    <button type="submit" class="w-full mt-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] px-4 py-2.5 text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                        {{ __('subscription.save_payment') }}
                    </button>
                </form>
            </div>

            {{-- Cancel + Renew --}}
            <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <h3 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.cancel_sub') }}</h3>
                </div>
                <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('subscription.cancel_hint') }}</p>
                <div class="mt-auto space-y-2">
                    <a href="{{ route('subscription.checkout', ['plan' => $currentPlan ?: 'standard']) }}"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-95">
                        {{ __('subscription.renew') }}
                    </a>
                    @if (! $cancelledAt)
                        <form method="POST" action="{{ route('subscription.cancel') }}" onsubmit="return confirm(@json(__('subscription.cancel_confirm')))">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-red-300 dark:border-red-900/50 px-4 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors">
                                {{ __('subscription.cancel_sub') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="mb-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-medium text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ __('subscription.back_to_app') }}
            </a>
        </div>
    @else
        {{-- Выбор плана (обязательно) --}}
        <div class="mb-3 flex items-center justify-between gap-3 flex-wrap">
            <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.choose_plan') }}</h2>
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.feature_soon') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8 max-w-4xl">
            @foreach (['standard' => $standard, 'pro' => $pro] as $key => $plan)
                <div class="sub-plan {{ $key === 'pro' ? 'sub-plan-featured' : '' }} relative rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 flex flex-col">
                    @if ($key === 'pro')
                        <div class="absolute -top-3 right-5">
                            <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-3 py-1 text-[11px] font-semibold text-white shadow-sm">
                                {{ __('subscription.recommended') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $key === 'pro' ? 'bg-[#f59e0b]/15 text-[#f59e0b]' : 'bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b]' }} mb-3">
                        @if ($key === 'pro')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        @endif
                    </div>
                    <h3 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.plan_'.$key) }}</h3>
                    <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('subscription.plan_'.$key.'_desc') }}</p>
                    <div class="my-5">
                        <span class="text-4xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ number_format($plan['price'], 0, ',', ' ') }}</span>
                        <span class="text-sm text-[#64748b] dark:text-[#A1A09A]"> {{ __('subscription.currency') }}{{ __('subscription.per_month') }}</span>
                    </div>
                    <ul class="space-y-2.5 mb-6 flex-1">
                        @foreach (array_merge(
                            ['feature_clients','feature_projects','feature_orders','feature_suppliers','feature_cashback'],
                            $key === 'pro' ? ['feature_priority','feature_pro_tools'] : []
                        ) as $feat)
                            <li class="flex items-center gap-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                <span class="sub-feature-dot"></span>
                                {{ __('subscription.'.$feat) }}
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('subscription.checkout', ['plan' => $key]) }}"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-opacity hover:opacity-95
                        {{ $key === 'pro'
                            ? 'bg-gradient-to-r from-[#f59e0b] to-[#fb923c] text-white'
                            : 'border border-[#0f172a] dark:border-[#EDEDEC] bg-[#0f172a] dark:bg-[#EDEDEC] text-white dark:text-[#0f172a]' }}">
                        {{ $canUseTrial ? __('subscription.start_trial') : __('subscription.buy') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- History --}}
    <div class="max-w-4xl rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
            <h2 class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('subscription.history') }}</h2>
        </div>
        @forelse ($payments as $payment)
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-[#7c8799]/20 dark:border-[#3E3E3A]/60 last:border-b-0">
                <div>
                    <div class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                        {{ __('subscription.plan_' . $payment->plan) }}
                        @if (!empty($payment->meta['is_trial']))
                            <span class="text-xs text-[#f59e0b]">· {{ __('subscription.status_trial') }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">
                        {{ $payment->starts_at->format('d.m.Y') }} — {{ $payment->ends_at->format('d.m.Y') }}
                    </div>
                </div>
                <div class="text-sm font-semibold text-[#f59e0b]">
                    {{ number_format($payment->amount, 0, ',', ' ') }} {{ __('subscription.currency') }}
                </div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-[#64748b] dark:text-[#A1A09A]">
                {{ __('subscription.history_empty') }}
            </div>
        @endforelse
    </div>
@endsection
