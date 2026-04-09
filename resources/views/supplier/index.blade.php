@extends('layouts.supplier')

@section('title', __('supplier-portal.title'))

@section('content')
    @php
        $showOnboardingModal = !$supplier || (string) ($supplier->profile_status ?? 'draft') === 'draft';
    @endphp

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

    @if ($showOnboardingModal)
        <div class="fixed inset-0 z-50 bg-black/55 backdrop-blur-[1px] flex items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-[#7c8799]/30 dark:border-[#3E3E3A]">
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-portal.fill_profile_title') }}</h2>
                    <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.fill_profile_hint') }}</p>
                </div>
                <form method="POST" action="{{ route('supplier.profile.save') }}" class="p-6 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.name') }}</label>
                        <input name="name" value="{{ old('name', $supplier->name ?? auth()->user()->name) }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div>
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.phone') }}</label>
                        <input name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div>
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.email') }}</label>
                        <input name="email" type="email" value="{{ old('email', $supplier->email ?? auth()->user()->email) }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div>
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.city') }}</label>
                        <input name="city" value="{{ old('city', $supplier->city ?? '') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div>
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.inn') }}</label>
                        <input name="inn" value="{{ old('inn', $supplier->inn ?? '') }}" required class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.address') }}</label>
                        <input name="address" value="{{ old('address', $supplier->address ?? '') }}" class="mt-1 w-full px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                    </div>
                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded-lg border border-[#0f172a] dark:border-[#EDEDEC] text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#0f172a] hover:text-white dark:hover:bg-[#EDEDEC] dark:hover:text-[#0f172a] transition-colors">
                            {{ __('supplier-portal.submit_profile') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
