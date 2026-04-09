@extends('layouts.supplier')

@section('title', __('supplier-portal.title'))

@section('content')
    <div class="rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-[#7c8799]/30 dark:border-[#3E3E3A] bg-gradient-to-r from-amber-500/10 via-rose-500/10 to-fuchsia-500/10 dark:from-amber-500/5 dark:via-rose-500/5 dark:to-fuchsia-500/5">
            <h1 class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.welcome', ['name' => auth()->user()->name]) }}</h1>
            <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.subtitle') }}</p>
        </div>

        <div class="p-6">
            @if ($supplier)
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.name') }}</dt>
                        <dd class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.profile_status') }}</dt>
                        <dd class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->profile_status ?? '—' }}</dd>
                    </div>
                    @if ($supplier->city)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.city') }}</dt>
                            <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->city }}</dd>
                        </div>
                    @endif
                    @if ($supplier->phone)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.phone') }}</dt>
                            <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->phone }}</dd>
                        </div>
                    @endif
                </dl>
            @else
                <div class="rounded-lg border border-dashed border-[#f59e0b]/50 bg-amber-50/50 dark:bg-amber-950/20 px-4 py-5">
                    <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ __('supplier-portal.no_company_yet') }}</p>
                    <p class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A] leading-relaxed">{{ __('supplier-portal.no_company_hint') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
