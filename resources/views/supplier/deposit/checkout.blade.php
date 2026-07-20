@extends('layouts.supplier')

@section('title', __('supplier_deposit.checkout_title'))

@php
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
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        @include('partials.back-link', [
            'fallback' => route('supplier.deposit.index'),
            'label' => __('supplier_deposit.title'),
        ])
        <nav class="flex flex-wrap items-center gap-2 text-sm text-[#A1A09A]" aria-label="{{ __('supplier_deposit.steps_aria') }}">
            <span>{{ __('supplier_deposit.step_register') }}</span>
            <span aria-hidden="true">→</span>
            <span class="text-[#f59e0b] font-semibold">{{ __('supplier_deposit.step_deposit') }}</span>
            <span aria-hidden="true">→</span>
            <span>{{ __('supplier_deposit.step_activation') }}</span>
        </nav>
    </div>

    @if ($errors->any())
        <div class="mb-4 max-w-5xl rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 items-start max-w-5xl">
        <div class="xl:col-span-2">
            <div class="deposit-hero relative overflow-hidden rounded-2xl p-6 text-white">
                <div class="text-xs uppercase tracking-wide text-white/50 mb-2">{{ __('supplier_deposit.checkout_title') }}</div>
                <h1 class="text-2xl font-bold">{{ __('supplier_deposit.card_title') }}</h1>
                <p class="mt-2 text-sm text-white/60">{{ __('supplier_deposit.one_time') }}</p>
                <div class="mt-6 pt-5 border-t border-white/10">
                    <div class="text-xs text-white/50 mb-1">{{ __('supplier_deposit.amount_due') }}</div>
                    <div class="text-3xl font-bold">{{ $amountLabel }}</div>
                    @if ($isDemo ?? false)
                        <p class="mt-2 text-sm text-[#fbbf24]">{{ __('supplier_deposit.demo_badge') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="xl:col-span-3 rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-7">
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-5">{{ __('supplier_deposit.checkout_subtitle') }}</p>

            <div class="space-y-3 mb-6">
                <label class="pay-method cursor-pointer block">
                    <input type="radio" name="payment_method_ui" value="kaspi" class="sr-only" checked>
                    <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f59e0b]/15 text-[#f59e0b]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier_deposit.pay_kaspi') }}</div>
                                <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.pay_kaspi_hint') }}</div>
                            </div>
                        </div>
                        <div id="kaspi-panel" class="mt-4 flex flex-col sm:flex-row items-center gap-4">
                            <div class="kaspi-qr relative w-40 h-40 rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] overflow-hidden flex items-center justify-center">
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
                            <p class="text-xs text-[#64748b] dark:text-[#A1A09A] max-w-[200px]">{{ __('supplier_deposit.qr_stub') }}</p>
                        </div>
                    </div>
                </label>

                <label class="pay-method cursor-pointer block">
                    <input type="radio" name="payment_method_ui" value="card" class="sr-only">
                    <div class="pay-face rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] p-4 transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f8fafc] dark:bg-[#0f0f0e] text-[#64748b]">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier_deposit.pay_card') }}</div>
                                <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier_deposit.pay_card_hint') }}</div>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            @if ($isDemo ?? false)
                <form method="POST" action="{{ route('supplier.deposit.confirm', ['payment' => $payment->uuid]) }}" id="deposit-confirm-form" class="space-y-3">
                    @csrf
                    <button type="submit" id="deposit-confirm-btn"
                        class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3.5 text-sm font-semibold text-white shadow-sm hover:opacity-95 disabled:opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span id="deposit-confirm-label">{{ __('supplier_deposit.pay_demo') }}</span>
                    </button>
                </form>
            @endif

            <div class="mt-4 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('supplier.deposit.return', ['payment' => $payment->uuid]) }}" class="text-[#f59e0b] hover:underline">
                    {{ __('supplier_deposit.check_status') }}
                </a>
                <form method="POST" action="{{ route('supplier.deposit.cancel', ['payment' => $payment->uuid]) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-[#64748b] dark:text-[#A1A09A] hover:underline">{{ __('supplier_deposit.cancel_payment') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('deposit-confirm-form');
    form?.addEventListener('submit', function () {
        const btn = document.getElementById('deposit-confirm-btn');
        const label = document.getElementById('deposit-confirm-label');
        if (btn) btn.disabled = true;
        if (label) label.textContent = @json(__('supplier_deposit.paying'));
    });
})();
</script>
@endpush
