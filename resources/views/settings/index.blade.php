@extends('layouts.dashboard')

@section('title', __('settings.settings'))

@push('styles')
    <style>
        .settings-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .settings-tab {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #94a3b8;
            background: #ffffff;
            color: #64748b;
            font-size: 13px;
            line-height: 1;
            transition: all .2s;
        }
        .settings-tab.active {
            color: #f59e0b;
            border-color: #f59e0b;
            background: #f8fafc;
        }
        .settings-shell {
            border: 1px solid #94a3b8;
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
        }
        .settings-section-title {
            color: #0f172a;
            font-size: 31px;
            margin-bottom: 10px;
        }
        .settings-divider {
            border-top: 1px solid #94a3b8;
            margin: 8px 0 14px;
        }
        .settings-grid {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px 16px;
            align-items: center;
        }
        .settings-label {
            color: #64748b;
            font-size: 13px;
        }
        .settings-input, .settings-textarea, .settings-select {
            width: 100%;
            border: 1px solid #94a3b8;
            background: #ffffff;
            color: #0f172a;
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
        }
        .settings-textarea { min-height: 84px; resize: vertical; }
        .settings-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .settings-actions .cancel-btn {
            border: 1px solid #94a3b8;
            background: #ffffff;
            color: #64748b;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
        }
        .settings-actions .save-btn {
            border: 1px solid #f59e0b;
            background: #f59e0b;
            color: #111827;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
        }
        .dark .settings-tab {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #A1A09A;
        }
        .dark .settings-tab.active {
            background: #161615;
        }
        .dark .settings-shell {
            border-color: #3E3E3A;
            background: #0a0a0a;
        }
        .dark .settings-section-title {
            color: #EDEDEC;
        }
        .dark .settings-divider {
            border-top-color: #3E3E3A;
        }
        .dark .settings-label {
            color: #EDEDEC;
        }
        .dark .settings-input,
        .dark .settings-textarea,
        .dark .settings-select {
            border-color: #3E3E3A;
            background: #161615;
            color: #EDEDEC;
        }
        .dark .settings-actions .cancel-btn {
            border-color: #3E3E3A;
            background: #161615;
            color: #EDEDEC;
        }
    </style>
@endpush

@section('content')
    @php
        $activeTab = $activeTab ?? 'profile';
    @endphp

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-700/40 bg-emerald-50 dark:bg-emerald-900/10 px-4 py-3 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 dark:border-red-700/40 bg-red-50 dark:bg-red-900/10 px-4 py-3 text-red-700 dark:text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="settings-tabs">
                <a href="{{ route('settings.index', ['tab' => 'profile']) }}" class="settings-tab {{ $activeTab === 'profile' ? 'active' : '' }}">{{ __('settings.profile_settings') }}</a>
                <a href="{{ route('settings.index', ['tab' => 'security']) }}" class="settings-tab {{ $activeTab === 'security' ? 'active' : '' }}">{{ __('settings.security') }}</a>
                <button type="button" class="settings-tab" disabled>{{ __('settings.notifications') }}</button>
                <button type="button" class="settings-tab" disabled>{{ __('settings.roles_and_access') }}</button>
                <button type="button" class="settings-tab" disabled>{{ __('settings.team') }}</button>
                <button type="button" class="settings-tab" disabled>{{ __('settings.subscriptions') }}</button>
            </div>
            <div class="settings-actions flex items-center gap-2">
                <button type="button" form="{{ $activeTab === 'security' ? 'security-form' : 'profile-form' }}" class="cancel-btn" onclick="document.getElementById('{{ $activeTab === 'security' ? 'security-form' : 'profile-form' }}').reset()">{{ __('settings.cancel') }}</button>
                <button type="submit" form="{{ $activeTab === 'security' ? 'security-form' : 'profile-form' }}" class="save-btn">{{ __('settings.save_changes') }}</button>
            </div>
        </div>

        <div class="settings-shell">
            @if ($activeTab === 'security')
                <form id="security-form" method="POST" action="{{ route('settings.password.update') }}">
                    @csrf
                    @method('PUT')

                    <h2 class="settings-section-title">{{ __('settings.security') }}</h2>
                    <div class="settings-divider"></div>
                    <div class="settings-grid max-w-3xl">
                        <label class="settings-label">{{ __('settings.current_password') }}</label>
                        <input type="password" name="current_password" required class="settings-input">

                        <label class="settings-label">{{ __('settings.new_password') }}</label>
                        <input type="password" name="password" required class="settings-input">

                        <label class="settings-label">{{ __('settings.new_password_confirmation') }}</label>
                        <input type="password" name="password_confirmation" required class="settings-input">
                    </div>
                </form>
            @else
                <form id="profile-form" method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <h2 class="settings-section-title">{{ __('settings.main_information') }}</h2>
                    <div class="settings-divider"></div>
                    <div class="settings-grid mb-5">
                        <label class="settings-label">{{ __('settings.name') }}</label>
                        <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="settings-input">

                        <label class="settings-label">{{ __('settings.short_description') }}</label>
                        <input type="text" name="short_description" value="{{ old('short_description', $user->short_description) }}" class="settings-input">

                        <label class="settings-label">{{ __('settings.about_designer') }}</label>
                        <textarea name="about_designer" class="settings-textarea">{{ old('about_designer', $user->about_designer) }}</textarea>

                        <label class="settings-label">{{ __('settings.city') }}</label>
                        <input type="text" name="city" value="{{ old('city', $user->city) }}" class="settings-input">
                    </div>

                    <div class="settings-divider"></div>
                    <div class="settings-columns">
                        <div>
                            <h3 class="settings-section-title text-xl">{{ __('settings.contact_information') }}</h3>
                            <div class="settings-divider"></div>
                            <div class="settings-grid">
                                @php
                                    $contacts = [
                                        ['phone', 'phone'],
                                        ['email', 'email'],
                                        ['website_portfolio', 'website_portfolio'],
                                        ['telegram', 'telegram'],
                                        ['whatsapp', 'whatsapp'],
                                        ['vk', 'vk'],
                                        ['instagram', 'instagram'],
                                    ];
                                @endphp
                                @foreach ($contacts as [$field, $label])
                                    <label class="settings-label">{{ __('settings.' . $label) }}</label>
                                    <input type="{{ $field === 'email' ? 'email' : 'text' }}" name="{{ $field }}" value="{{ old($field, $user->{$field}) }}" class="settings-input">
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="settings-section-title text-xl">{{ __('settings.professional_information') }}</h3>
                            <div class="settings-divider"></div>
                            <div class="settings-grid">
                                <label class="settings-label">{{ __('settings.experience') }}</label>
                                <input type="text" name="experience" value="{{ old('experience', $user->experience) }}" class="settings-input">

                                <label class="settings-label">{{ __('settings.price_per_m2') }}</label>
                                <input type="number" step="0.01" min="0" name="price_per_m2" value="{{ old('price_per_m2', $user->price_per_m2) }}" class="settings-input">

                                <label class="settings-label">{{ __('settings.education') }}</label>
                                <input type="text" name="education" value="{{ old('education', $user->education) }}" class="settings-input">

                                <label class="settings-label">{{ __('settings.awards') }}</label>
                                <input type="text" name="awards" value="{{ old('awards', $user->awards) }}" class="settings-input">

                                <label class="settings-label">{{ __('settings.specialization') }}</label>
                                <input type="text" name="specialization" value="{{ old('specialization', $user->specialization) }}" class="settings-input">

                                <label class="settings-label">{{ __('settings.styles') }}</label>
                                <input type="text" name="styles" value="{{ old('styles', $user->styles) }}" class="settings-input">
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

