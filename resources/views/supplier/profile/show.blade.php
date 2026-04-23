@extends('layouts.supplier')

@section('title', __('dashboard.profile'))

@section('header_title', __('dashboard.profile'))

@push('styles')
    <style>
        .profile-shell {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #7c8799;
            border-radius: 14px;
        }
        .dark .profile-shell {
            background: linear-gradient(180deg, #161615 0%, #0f0f0f 100%);
            border-color: #3E3E3A;
        }
        .profile-chip {
            border: 1px solid #7c8799;
            background: #ffffff;
            color: #64748b;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
        }
        .dark .profile-chip {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #A1A09A;
        }
    </style>
@endpush

@section('content')
    @php
        $name = trim((string) ($user->name ?? ''));
        $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
        $initials = $initials !== '' ? $initials : 'U';
    @endphp

    <div class="mb-6 profile-shell p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold text-lg">
                    {{ $initials }}
                </div>
                <div>
                    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('dashboard.profile') }}</h1>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ $name ?: '-' }}</p>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        <span class="profile-chip">{{ __('suppliers.city') }}: {{ $supplier?->city ?: '-' }}</span>
                        <span class="profile-chip">{{ __('suppliers.sphere') }}: {{ $supplier?->sphere ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('supplier.settings.index', ['tab' => 'profile']) }}" class="add-btn">{{ __('settings.profile_settings') }}</a>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('suppliers.main_info') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</div><div>{{ $supplier?->name ?: $user->name ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</div><div>{{ $supplier?->city ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</div><div>{{ $supplier?->address ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</div><div>{!! $supplier?->website ? '<a class="text-[#f59e0b] hover:underline" href="'.e($supplier->website).'" target="_blank">'.e($supplier->website).'</a>' : '-' !!}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere_activity') }}</div><div>{{ $supplier?->sphere ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.work_terms') }}</div><div>{{ $supplier?->work_terms_type === 'percent' ? __('suppliers.work_terms_percent') : ($supplier?->work_terms_type === 'amount' ? __('suppliers.work_terms_amount') : '-') }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.value') }}</div><div>{{ $supplier?->work_terms_value ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.contact_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</div><div>{{ $supplier?->phone ?: $user->phone ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.email') }}</div><div>{{ $supplier?->email ?: $user->email ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Telegram</div><div>{{ $supplier?->telegram ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">WhatsApp</div><div>{{ $supplier?->whatsapp ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('suppliers.requisites') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.inn') }}</div><div>{{ $supplier?->inn ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.kpp') }}</div><div>{{ $supplier?->kpp ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.ogrn') }}</div><div>{{ $supplier?->ogrn ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.okpo') }}</div><div>{{ $supplier?->okpo ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.legal_address') }}</div><div>{{ $supplier?->legal_address ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.actual_address') }}</div><div>{{ $supplier?->actual_address ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.director') }}</div><div>{{ $supplier?->director ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.accountant') }}</div><div>{{ $supplier?->accountant ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('suppliers.bank_details') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.bik') }}</div><div>{{ $supplier?->bik ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.bank') }}</div><div>{{ $supplier?->bank ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.checking_account') }}</div><div>{{ $supplier?->checking_account ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.corr_account') }}</div><div>{{ $supplier?->corr_account ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</div><div class="whitespace-pre-wrap">{{ $supplier?->comment ?: '-' }}</div></div>
            </div>
        </section>
    </div>
@endsection
