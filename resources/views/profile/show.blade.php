@extends('layouts.dashboard')

@section('title', __('dashboard.profile'))

@push('styles')
    <style>
        .profile-shell {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #94a3b8;
            border-radius: 14px;
        }
        .dark .profile-shell {
            background: linear-gradient(180deg, #161615 0%, #0f0f0f 100%);
            border-color: #3E3E3A;
        }
        .profile-chip {
            border: 1px solid #94a3b8;
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
                        <span class="profile-chip">{{ __('settings.city') }}: {{ $user->city ?: '-' }}</span>
                        <span class="profile-chip">{{ __('settings.experience') }}: {{ $user->experience ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('settings.index', ['tab' => 'profile']) }}" class="add-btn">{{ __('settings.profile_settings') }}</a>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <section class="bg-white dark:bg-[#161615] border border-[#94a3b8] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.main_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.name') }}</div><div>{{ $user->name ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.city') }}</div><div>{{ $user->city ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.short_description') }}</div><div>{{ $user->short_description ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.work_regions') }}</div><div>{{ $user->work_regions ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.about_designer') }}</div><div class="whitespace-pre-wrap">{{ $user->about_designer ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#94a3b8] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.contact_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.phone') }}</div><div>{{ $user->phone ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.email') }}</div><div>{{ $user->email ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.website_portfolio') }}</div><div>{!! $user->website_portfolio ? '<a class="text-[#f59e0b] hover:underline" href="'.e($user->website_portfolio).'" target="_blank">'.e($user->website_portfolio).'</a>' : '-' !!}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.telegram') }}</div><div>{{ $user->telegram ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.whatsapp') }}</div><div>{{ $user->whatsapp ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.vk') }}</div><div>{{ $user->vk ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.instagram') }}</div><div>{{ $user->instagram ?: '-' }}</div></div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#161615] border border-[#94a3b8] dark:border-[#3E3E3A] rounded-xl p-6">
            <h2 class="text-sm font-semibold text-[#64748b] dark:text-[#A1A09A] mb-4 uppercase">{{ __('settings.professional_information') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.experience') }}</div><div>{{ $user->experience ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.price_per_m2') }}</div><div>{{ $user->price_per_m2 !== null ? number_format((float) $user->price_per_m2, 2, '.', ' ') . ' ₸' : '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.education') }}</div><div class="whitespace-pre-wrap">{{ $user->education ?: '-' }}</div></div>
                <div class="md:col-span-2"><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.awards') }}</div><div class="whitespace-pre-wrap">{{ $user->awards ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.specialization') }}</div><div>{{ $user->specialization ?: '-' }}</div></div>
                <div><div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('settings.styles') }}</div><div>{{ $user->styles ?: '-' }}</div></div>
            </div>
        </section>
    </div>
@endsection
