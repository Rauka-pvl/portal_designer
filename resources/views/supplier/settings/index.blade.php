@extends('layouts.supplier')

@section('title', __('settings.settings'))

@section('header_title', __('settings.settings'))

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
            border: 1px solid #7c8799;
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
            border: 1px solid #7c8799;
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
            border-top: 1px solid #7c8799;
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
            border: 1px solid #7c8799;
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
            border: 1px solid #7c8799;
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
                <a href="{{ route('supplier.settings.index', ['tab' => 'profile']) }}" class="settings-tab {{ $activeTab === 'profile' ? 'active' : '' }}">{{ __('settings.profile_settings') }}</a>
                <a href="{{ route('supplier.settings.index', ['tab' => 'security']) }}" class="settings-tab {{ $activeTab === 'security' ? 'active' : '' }}">{{ __('settings.security') }}</a>
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
                <form id="security-form" method="POST" action="{{ route('supplier.settings.password.update') }}">
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
                <form id="profile-form" method="POST" action="{{ route('supplier.settings.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <h2 class="settings-section-title">{{ __('suppliers.main_info') }}</h2>
                    <div class="settings-divider"></div>
                    <div class="settings-grid mb-5">
                        <label class="settings-label">{{ __('suppliers.name') }}</label>
                        <input type="text" name="name" required value="{{ old('name', $supplier?->name ?? $user->name) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier?->phone ?? $user->phone) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.email') }}</label>
                        <input type="email" name="email" required value="{{ old('email', $supplier?->email ?? $user->email) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.city') }}</label>
                        <input type="text" name="city" value="{{ old('city', $supplier?->city ?? $user->city) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.address') }}</label>
                        <input type="text" name="address" value="{{ old('address', $supplier?->address) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.website') }}</label>
                        <input type="url" name="website" value="{{ old('website', $supplier?->website) }}" class="settings-input">

                        <label class="settings-label">Telegram</label>
                        <input type="text" name="telegram" value="{{ old('telegram', $supplier?->telegram) }}" class="settings-input">

                        <label class="settings-label">WhatsApp</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $supplier?->whatsapp) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.sphere_activity') }}</label>
                        <input type="text" name="sphere" value="{{ old('sphere', $supplier?->sphere) }}" class="settings-input">

                        <label class="settings-label">{{ __('suppliers.work_terms') }}</label>
                        <select name="work_terms_type" class="settings-select">
                            <option value="">—</option>
                            <option value="percent" @selected(old('work_terms_type', $supplier?->work_terms_type) === 'percent')>{{ __('suppliers.work_terms_percent') }}</option>
                            <option value="amount" @selected(old('work_terms_type', $supplier?->work_terms_type) === 'amount')>{{ __('suppliers.work_terms_amount') }}</option>
                        </select>

                        <label class="settings-label">{{ __('suppliers.value') }}</label>
                        <input type="text" name="work_terms_value" value="{{ old('work_terms_value', $supplier?->work_terms_value) }}" class="settings-input">
                    </div>

                    <div class="settings-divider"></div>
                    <div class="settings-columns">
                        <div>
                            <h3 class="settings-section-title text-xl">{{ __('suppliers.requisites') }}</h3>
                            <div class="settings-divider"></div>
                            <div class="settings-grid">
                                <label class="settings-label">{{ __('suppliers.inn') }}</label>
                                <input type="text" name="inn" value="{{ old('inn', $supplier?->inn) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.kpp') }}</label>
                                <input type="text" name="kpp" value="{{ old('kpp', $supplier?->kpp) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.ogrn') }}</label>
                                <input type="text" name="ogrn" value="{{ old('ogrn', $supplier?->ogrn) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.okpo') }}</label>
                                <input type="text" name="okpo" value="{{ old('okpo', $supplier?->okpo) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.legal_address') }}</label>
                                <input type="text" name="legal_address" value="{{ old('legal_address', $supplier?->legal_address) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.actual_address') }}</label>
                                <input type="text" name="actual_address" value="{{ old('actual_address', $supplier?->actual_address) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.director') }}</label>
                                <input type="text" name="director" value="{{ old('director', $supplier?->director) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.accountant') }}</label>
                                <input type="text" name="accountant" value="{{ old('accountant', $supplier?->accountant) }}" class="settings-input">
                            </div>
                        </div>

                        <div>
                            <h3 class="settings-section-title text-xl">{{ __('suppliers.bank_details') }}</h3>
                            <div class="settings-divider"></div>
                            <div class="settings-grid">
                                <label class="settings-label">{{ __('suppliers.bik') }}</label>
                                <input type="text" name="bik" value="{{ old('bik', $supplier?->bik) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.bank') }}</label>
                                <input type="text" name="bank" value="{{ old('bank', $supplier?->bank) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.checking_account') }}</label>
                                <input type="text" name="checking_account" value="{{ old('checking_account', $supplier?->checking_account) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.corr_account') }}</label>
                                <input type="text" name="corr_account" value="{{ old('corr_account', $supplier?->corr_account) }}" class="settings-input">

                                <label class="settings-label">{{ __('suppliers.comment') }}</label>
                                <textarea name="comment_main" class="settings-textarea">{{ old('comment_main', $supplier?->comment) }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
