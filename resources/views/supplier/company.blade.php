@extends('layouts.supplier')

@section('title', __('supplier-portal.page_company_title'))

@section('header_title', __('supplier-portal.nav_company'))

@section('content')
    @php
        $status = (string) ($supplier->moderation_status ?? $supplier->profile_status ?? 'draft');
        $statusLabel = $status !== '' ? __('moderation.' . $status) : '—';
        $statusClass = match ($status) {
            'approved', 'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
            'rejected' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
            default => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
        };
        $details = [
            __('suppliers.name') => $supplier?->name,
            __('suppliers.email') => $supplier?->email,
            __('suppliers.phone') => $supplier?->phone,
            __('suppliers.city') => $supplier?->city,
            __('suppliers.address') => $supplier?->address,
            __('suppliers.inn') => $supplier?->inn,
            __('suppliers.website') => $supplier?->website,
        ];
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-[#7c8799]/30 dark:border-[#3E3E3A] bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-fuchsia-500/10 dark:from-amber-500/5 dark:via-rose-500/5 dark:to-fuchsia-500/5">
                <h1 class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.welcome', ['name' => auth()->user()->name]) }}</h1>
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.subtitle') }}</p>
            </div>

            <div class="p-6">
                @if ($supplier)
                    <div class="rounded-lg border border-[#7c8799]/30 dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a] px-4 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">
                                    @if ($status === 'pending')
                                        {{ __('supplier-portal.waiting_moderation_title') }}
                                    @else
                                        {{ __('supplier-portal.page_company_title') }}
                                    @endif
                                </h2>
                                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                    @if ($status === 'pending')
                                        {{ __('supplier-portal.waiting_moderation_hint') }}
                                    @else
                                        {{ __('supplier-portal.subtitle') }}
                                    @endif
                                </p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        @if (!empty($supplier->moderation_comment))
                            <div class="mt-4 rounded-lg border border-[#7c8799]/30 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] px-4 py-3">
                                <div class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">
                                    {{ __('supplier-portal.moderation_comment') }}
                                </div>
                                <div class="mt-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap">{{ $supplier->moderation_comment }}</div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-lg border border-dashed border-[#f59e0b]/50 bg-amber-50/50 dark:bg-amber-950/20 px-4 py-5">
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ __('supplier-portal.no_company_yet') }}</p>
                        <p class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A] leading-relaxed">{{ __('supplier-portal.no_company_hint') }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($supplier)
            <div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-[#7c8799]/30 dark:border-[#3E3E3A]">
                    <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.company_data_title') }}</h2>
                </div>
                <div class="p-6 grid gap-4 sm:grid-cols-2">
                    @foreach ($details as $label => $value)
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ $label }}</div>
                            <div class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ filled($value) ? $value : '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
