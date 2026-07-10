@extends('layouts.supplier')

@section('title', $designer->name ?? __('designers.designer'))
@section('header_title', $designer->name ?? __('designers.designer'))

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
        $name = trim((string) ($designer->name ?? ''));
        $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
        $initials = $initials !== '' ? $initials : 'D';
    @endphp

    @include('partials.designer-directory-tabs', ['active' => 'profile', 'designerId' => $designer->id])

    <div class="mb-6 profile-shell p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold text-lg">
                    {{ $initials }}
                </div>
                <div>
                    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $name ?: '—' }}</h1>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('designers.designer') }}</p>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        @include('partials.stars', ['value' => $ratingSummary['average'] ?? 0, 'count' => $ratingSummary['count'] ?? 0, 'size' => 'w-4 h-4'])
                        <span class="profile-chip">{{ __('settings.city') }}: {{ $profile?->city ?: '-' }}</span>
                        <span class="profile-chip">{{ __('settings.experience') }}: {{ $profile?->experience ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('supplier.designers.index') }}" class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                {{ __('designers.back') }}
            </a>
        </div>
    </div>

    <div class="space-y-6">
        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.main_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.name') }}</div><div>{{ $designer->name ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.city') }}</div><div>{{ $profile?->city ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.short_description') }}</div><div>{{ $profile?->short_description ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.work_regions') }}</div><div>{{ $profile?->work_regions ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.about_designer') }}</div><div class="whitespace-pre-wrap">{{ $profile?->about_designer ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.professional_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.experience') }}</div><div>{{ $profile?->experience ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.price_per_m2') }}</div><div>{{ $profile?->price_per_m2 !== null ? number_format((float) $profile->price_per_m2, 2, '.', ' ') . ' ₸' : '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.education') }}</div><div class="whitespace-pre-wrap">{{ $profile?->education ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.awards') }}</div><div class="whitespace-pre-wrap">{{ $profile?->awards ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.specialization') }}</div><div>{{ $profile?->specialization ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.styles') }}</div><div>{{ $profile?->styles ?: '-' }}</div></div>
            </div>
        </section>
    </div>
@endsection
