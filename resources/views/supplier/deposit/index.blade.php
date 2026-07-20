@extends('layouts.supplier')

@section('title', __('supplier_deposit.title'))

@php
    $state = $state ?? 'ready';
    $amountLabel = $amountLabel ?? \App\Support\SupplierDeposit::formatMoney((int) ($amount ?? 0));
@endphp

@push('styles')
<style>
    .deposit-hero {
        background:
            radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.22), transparent 55%),
            #0f172a;
    }
    .dark .deposit-hero {
        background: radial-gradient(ellipse 70% 80% at 100% 0%, rgba(245, 158, 11, 0.28), transparent 55%), #161615;
    }
</style>
@endpush

@section('content')
    <div class="max-w-3xl mx-auto">
        <nav class="mb-6 flex flex-wrap items-center gap-2 text-sm text-[#64748b] dark:text-[#A1A09A]" aria-label="{{ __('supplier_deposit.steps_aria') }}">
            <span class="inline-flex items-center gap-1.5">
                <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                {{ __('supplier_deposit.step_register') }}
                <span class="text-xs opacity-70">({{ __('supplier_deposit.step_register_done') }})</span>
            </span>
            <span aria-hidden="true">→</span>
            <span class="text-[#f59e0b] font-semibold">
                {{ __('supplier_deposit.step_deposit') }}
                @if ($state !== 'paid')
                    <span class="text-xs font-normal opacity-80">({{ __('supplier_deposit.step_deposit_current') }})</span>
                @endif
            </span>
            <span aria-hidden="true">→</span>
            <span class="{{ $state === 'paid' ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : '' }}">
                {{ __('supplier_deposit.step_activation') }}
            </span>
        </nav>

        <h1 class="text-2xl sm:text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC] mb-6">
            {{ __('supplier_deposit.complete_activation') }}
        </h1>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first() }}
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($state === 'paid')
            <div class="rounded-2xl border border-emerald-500/30 bg-white dark:bg-[#161615] p-6 sm:p-8 space-y-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-600">✓</div>
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier_deposit.state_paid_title') }}</h2>
                        @if ($isDemo ?? false)
                            <p class="mt-1 text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.demo_badge') }}</p>
                        @endif
                    </div>
                </div>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.balance_label') }}</dt>
                        <dd class="mt-1 font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ $guaranteeBalanceLabel }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.account_status_label') }}</dt>
                        <dd class="mt-1 font-semibold text-emerald-600 dark:text-emerald-400">{{ __('supplier_deposit.account_active') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.paid_at_label') }}</dt>
                        <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ optional($payment?->paid_at)->format('d.m.Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.operation_label') }}</dt>
                        <dd class="mt-1 font-mono text-xs text-[#0f172a] dark:text-[#EDEDEC]">{{ $payment?->uuid ?? '—' }}</dd>
                    </div>
                </dl>
                <a href="{{ route('supplier.index') }}"
                    class="inline-flex w-full sm:w-auto justify-center rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3.5 text-sm font-semibold text-white hover:opacity-95">
                    {{ __('supplier_deposit.go_cabinet') }}
                </a>
            </div>
        @elseif (in_array($state, ['checking', 'pending'], true))
            <div class="rounded-2xl border border-amber-500/30 bg-white dark:bg-[#161615] p-6 sm:p-8 space-y-5" id="deposit-pending-panel"
                data-status-url="{{ $payment ? route('supplier.deposit.status', ['payment' => $payment->uuid]) : '' }}">
                <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">
                    {{ $state === 'checking' ? __('supplier_deposit.state_checking_title') : __('supplier_deposit.state_pending_title') }}
                </h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">
                    {{ $state === 'checking' ? __('supplier_deposit.state_checking_text') : __('supplier_deposit.state_pending_text') }}
                </p>
                @if ($payment)
                    <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $amountLabel }} · <span class="font-mono text-xs">{{ $payment->uuid }}</span></p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('supplier.deposit.checkout', ['payment' => $payment->uuid]) }}"
                            class="inline-flex justify-center rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3 text-sm font-semibold text-white">
                            {{ __('supplier_deposit.go_to_pay') }}
                        </a>
                        <button type="button" id="deposit-check-btn"
                            class="inline-flex justify-center rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] px-5 py-3 text-sm font-medium">
                            {{ __('supplier_deposit.check_status') }}
                        </button>
                        <a href="mailto:{{ $supportEmail }}" class="inline-flex justify-center rounded-xl px-5 py-3 text-sm text-[#f59e0b]">
                            {{ __('supplier_deposit.link_support') }}
                        </a>
                    </div>
                @endif
            </div>
        @elseif ($state === 'failed')
            <div class="rounded-2xl border border-red-500/30 bg-white dark:bg-[#161615] p-6 sm:p-8 space-y-4">
                <h2 class="text-xl font-semibold">{{ __('supplier_deposit.state_failed_title') }}</h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.state_failed_text') }}</p>
                @include('supplier.deposit.partials.create-form')
                <a href="mailto:{{ $supportEmail }}" class="inline-flex text-sm text-[#f59e0b]">{{ __('supplier_deposit.link_support') }}</a>
            </div>
        @elseif ($state === 'cancelled')
            <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 sm:p-8 space-y-4">
                <h2 class="text-xl font-semibold">{{ __('supplier_deposit.state_cancelled_title') }}</h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.state_cancelled_text') }}</p>
                @include('supplier.deposit.partials.create-form')
            </div>
        @elseif ($state === 'expired')
            <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-6 sm:p-8 space-y-4">
                <h2 class="text-xl font-semibold">{{ __('supplier_deposit.state_expired_title') }}</h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.state_expired_text') }}</p>
                @include('supplier.deposit.partials.create-form')
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
                <div class="lg:col-span-2">
                    <div class="deposit-hero relative overflow-hidden rounded-2xl p-6 text-white">
                        <div class="text-xs uppercase tracking-wide text-white/50 mb-2">{{ __('supplier_deposit.card_title') }}</div>
                        <div class="text-3xl font-bold">{{ $amountLabel }}</div>
                        <p class="mt-2 text-sm text-[#fbbf24]">{{ __('supplier_deposit.one_time') }}</p>
                        @if ($isDemo ?? false)
                            <p class="mt-3 text-xs text-white/50">{{ __('supplier_deposit.demo_badge') }}</p>
                        @endif
                    </div>
                </div>
                <div class="lg:col-span-3 rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-7 space-y-5">
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.explanation') }}</p>
                    <ul class="text-sm space-y-1.5 text-[#0f172a] dark:text-[#EDEDEC]">
                        <li>• {{ __('supplier_deposit.not_subscription') }}</li>
                        <li>• {{ __('supplier_deposit.not_monthly') }}</li>
                        <li>• {{ __('supplier_deposit.shows_in_history') }}</li>
                        <li>• {{ __('supplier_deposit.tied_to_orders') }}</li>
                    </ul>
                    @include('supplier.deposit.partials.create-form')
                    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                        <a href="{{ route('faq.index') }}" class="text-[#f59e0b] hover:underline">{{ __('supplier_deposit.link_terms') }}</a>
                        <a href="{{ route('faq.index') }}" class="text-[#f59e0b] hover:underline">{{ __('supplier_deposit.link_payouts') }}</a>
                        <a href="mailto:{{ $supportEmail }}" class="text-[#f59e0b] hover:underline">{{ __('supplier_deposit.link_support') }}</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const panel = document.getElementById('deposit-pending-panel');
    if (!panel) return;
    const url = panel.dataset.statusUrl;
    if (!url) return;
    let timer = null;
    let stopped = false;

    async function check() {
        if (stopped) return;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            if (data.paid || data.status === 'paid' || data.status === 'failed' || data.status === 'cancelled' || data.status === 'expired') {
                stopped = true;
                if (timer) clearInterval(timer);
                window.location.reload();
            }
        } catch (e) {}
    }

    document.getElementById('deposit-check-btn')?.addEventListener('click', check);
    timer = setInterval(check, 4000);
    window.addEventListener('beforeunload', () => { stopped = true; if (timer) clearInterval(timer); });
})();
</script>
@endpush
