@extends('layouts.dashboard')

@section('title', __('cashback.title'))
@section('header_title', __('cashback.title'))

@section('content')
    @include('partials.profile-tabs', ['active' => 'cashback'])

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/30 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-950/30 px-4 py-3 text-sm text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6">
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('cashback.subtitle') }}</p>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-end gap-2">
        @foreach (['24h', '7d', '30d', '90d'] as $p)
            <a href="{{ route('profile.cashback', ['period' => $p]) }}"
                class="px-3 py-1.5 rounded-full text-xs font-medium border transition-colors {{ $period === $p
                    ? 'border-[#f59e0b] bg-[#f59e0b] text-white'
                    : 'border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]' }}">
                {{ $p }}
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 md:col-span-1">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('cashback.available') }}</div>
            <div class="mt-2 text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">
                {{ number_format($available, 0, ',', ' ') }} <span class="text-lg font-semibold text-[#64748b] dark:text-[#A1A09A]">₸</span>
            </div>
            @if ($available > 0)
                <a href="{{ route('profile.cashback.withdraw') }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg border border-[#0f172a] dark:border-[#EDEDEC] bg-[#0f172a] dark:bg-[#EDEDEC] px-4 py-2 text-sm font-medium text-white dark:text-[#0f172a] hover:opacity-90 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    {{ __('cashback.withdraw') }}
                </a>
            @endif
        </div>

        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('cashback.total_earned') }}</div>
            <div class="mt-2 text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ number_format($totalEarned, 0, ',', ' ') }} ₸</div>
            <div class="mt-4 flex items-end gap-0.5 h-10">
                @foreach ($daily as $day)
                    @php $h = $maxDaily > 0 ? max(2, round(($day['amount'] / $maxDaily) * 100)) : 2; @endphp
                    <span class="flex-1 rounded-sm bg-gradient-to-t from-[#f59e0b]/30 to-[#f59e0b]" style="height: {{ $h }}%" title="{{ $day['date'] }}"></span>
                @endforeach
            </div>
            <div class="mt-2 text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('cashback.chart_accruals') }}</div>
        </div>

        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
            <div>
                <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('cashback.period_earned') }}</div>
                <div class="mt-2 text-2xl font-semibold text-[#f59e0b]">{{ number_format($periodEarned, 0, ',', ' ') }} ₸</div>
            </div>
            <div class="pt-3 border-t border-[#7c8799]/40 dark:border-[#3E3E3A]">
                <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('cashback.total_withdrawn') }}</div>
                <div class="mt-2 text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ number_format($totalWithdrawn, 0, ',', ' ') }} ₸</div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
            <h2 class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('cashback.history_title') }}</h2>
        </div>

        @forelse ($transactions as $tx)
            <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-[#7c8799]/20 dark:border-[#3E3E3A]/60 last:border-b-0">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $tx->isCredit() ? 'bg-[#f59e0b]/15 text-[#f59e0b]' : 'bg-[#64748b]/10 text-[#64748b] dark:text-[#A1A09A]' }}">
                        @if ($tx->isCredit())
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                            {{ $tx->isCredit() ? __('cashback.type_accrual') : __('cashback.type_withdrawal') }}
                        </div>
                        <div class="text-sm text-[#64748b] dark:text-[#A1A09A] truncate">{{ $tx->description }}</div>
                        @if ($tx->supplier_order_id)
                            <div class="text-xs text-[#94a3b8] dark:text-[#71717a] mt-0.5">
                                {{ __('cashback.order_label', ['id' => $tx->supplier_order_id]) }}
                            </div>
                        @endif
                        <div class="text-xs text-[#94a3b8] dark:text-[#71717a] mt-1">{{ $tx->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                </div>
                <div class="text-sm font-semibold whitespace-nowrap {{ $tx->isCredit() ? 'text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC]' }}">
                    {{ $tx->isCredit() ? '+' : '−' }}{{ number_format($tx->amount, 0, ',', ' ') }} ₸
                </div>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-sm text-[#64748b] dark:text-[#A1A09A]">
                {{ __('cashback.history_empty') }}
            </div>
        @endforelse

        @if ($transactions->hasPages())
            <div class="px-5 py-4 border-t border-[#7c8799]/40 dark:border-[#3E3E3A]">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
@endsection
