@extends('layouts.dashboard')

@section('title', $supplierData['name'] ?? __('suppliers.supplier'))
@section('header_title', $supplierData['name'] ?? __('suppliers.supplier'))

@php
    $s = $supplierData;
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $canManage = (bool) ($s['designer_can_manage'] ?? ! $isReadOnly);
    $name = trim((string) ($s['name'] ?? ''));
    $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    $initials = $initials !== '' ? $initials : 'S';
    $modStatus = (string) ($s['moderation_status'] ?? '');
    $sphereLabel = ($sphereOptions[$s['sphere'] ?? ''] ?? null) ?: (($s['sphere'] ?? '') !== '' ? $s['sphere'] : '—');
@endphp

@push('styles')
    <style>
        .profile-shell {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #7c8799;
            border-radius: 14px;
        }
        .profile-chip {
            border: 1px solid #7c8799;
            background: #ffffff;
            color: #64748b;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
        }
        .dark .profile-shell { background: linear-gradient(180deg, #161615 0%, #0f0f0f 100%); border-color: #3E3E3A; }
        .dark .profile-chip { border-color: #3E3E3A; background: #0a0a0a; color: #A1A09A; }
    </style>
@endpush

@section('content')
<div class="pb-28 max-w-6xl mx-auto">
    @include('partials.supplier-detail-tabs', ['active' => 'profile', 'supplierId' => $s['id'] ?? null])

    <div class="mb-6 profile-shell p-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold text-lg shrink-0">
                    {{ $initials }}
                </div>
                <div class="min-w-0">
                    <h1 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $s['name'] ?? __('suppliers.supplier') }}</h1>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.supplier') }} #{{ $s['id'] ?? '-' }}</p>
                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                        @include('partials.stars', ['value' => $ratingSummary['average'] ?? 0, 'count' => $ratingSummary['count'] ?? 0, 'size' => 'w-4 h-4'])
                        <span class="profile-chip">{{ __('suppliers.city') }}: {{ $s['city'] ?: '-' }}</span>
                        @if ($modStatus !== '')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if ($modStatus === 'approved') bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300
                                @elseif ($modStatus === 'rejected') bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300
                                @else bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200
                                @endif">
                                {{ __('moderation.'.$modStatus) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                @if ($canManage)
                    <button id="btn-edit" type="button"
                        class="inline-flex items-center justify-center min-h-10 px-4 rounded-xl border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 text-sm font-medium transition-colors">
                        {{ __('suppliers.edit') }}
                    </button>
                    <details class="relative">
                        <summary class="list-none cursor-pointer inline-flex items-center justify-center min-h-10 min-w-10 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                            aria-label="{{ __('detail.more_actions') }}">⋯</summary>
                        <div class="absolute right-0 mt-2 w-48 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg p-1 z-20">
                            <form method="POST" action="{{ route('suppliers.destroy', $s['id']) }}"
                                onsubmit="return confirm(@json(__('suppliers.delete').'?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    {{ __('suppliers.delete') }}
                                </button>
                            </form>
                        </div>
                    </details>
                @endif
                @include('partials.back-link', [
                    'fallback' => route('suppliers.index'),
                    'label' => __('suppliers.close'),
                    'variant' => 'btn',
                    'icon' => false,
                ])
            </div>
        </div>
    </div>

    <div data-detail-view class="mb-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.phone') }}</p>
            <p class="mt-1 text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] break-all">{{ $s['phone'] ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">Email</p>
            <p class="mt-1 text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] break-all">{{ $s['email'] ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.city') }}</p>
            <p class="mt-1 text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['city'] ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.sphere') }}</p>
            <p class="mt-1 text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $sphereLabel }}</p>
        </div>
    </div>

    <section data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.name') }}</dt>
                <dd class="mt-1 font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['name'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.website') }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC] break-all">{{ $s['website'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">Telegram</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['telegram'] ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">WhatsApp</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['whatsapp'] ?: '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.address') }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC]">{{ $s['address'] ?: '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.comment') }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap">{{ $s['comment'] ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    @if ($canManage)
        <form id="supplier-details-form" method="POST" action="{{ route('suppliers.update', $s['id']) }}" data-ajax="1" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</label>
                        <input name="name" value="{{ $s['name'] ?? '' }}" required
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</label>
                        <input name="phone" value="{{ $s['phone'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Email</label>
                        <input name="email" value="{{ $s['email'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">Telegram</label>
                        <input name="telegram" value="{{ $s['telegram'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">WhatsApp</label>
                        <input name="whatsapp" value="{{ $s['whatsapp'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</label>
                        <input name="website" value="{{ $s['website'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</label>
                        <input name="city" value="{{ $s['city'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div>
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere') }}</label>
                        <select name="sphere" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                            <option value=""></option>
                            @foreach (($sphereOptions ?? []) as $key => $sphereName)
                                <option value="{{ $key }}" @selected(($s['sphere'] ?? '') === $key)>{{ $sphereName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.address') }}</label>
                        <input name="address" value="{{ $s['address'] ?? '' }}"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.comment') }}</label>
                        <textarea name="comment_main" rows="3"
                            class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615]">{{ $s['comment'] ?? '' }}</textarea>
                    </div>

                    @foreach (($s['brands'] ?? []) as $brand)
                        <input type="hidden" name="brands[]" value="{{ $brand }}">
                    @endforeach
                    @foreach (($s['cities_presence'] ?? []) as $cityPresence)
                        <input type="hidden" name="cities_presence[]" value="{{ $cityPresence }}">
                    @endforeach
                    <input type="hidden" name="work_terms_type" value="{{ $s['work_terms_type'] ?? '' }}">
                    <input type="hidden" name="work_terms_value" value="{{ $s['work_terms_value'] ?? '' }}">
                    <input type="hidden" name="org_form" value="{{ $s['org_form'] ?? 'ooo' }}">
                    <input type="hidden" name="inn" value="{{ $s['inn'] ?? '' }}">
                    <input type="hidden" name="kpp" value="{{ $s['kpp'] ?? '' }}">
                    <input type="hidden" name="ogrn" value="{{ $s['ogrn'] ?? '' }}">
                    <input type="hidden" name="okpo" value="{{ $s['okpo'] ?? '' }}">
                    <input type="hidden" name="legal_address" value="{{ $s['legal_address'] ?? '' }}">
                    <input type="hidden" name="actual_address" value="{{ $s['actual_address'] ?? '' }}">
                    <input type="hidden" name="director" value="{{ $s['director'] ?? '' }}">
                    <input type="hidden" name="accountant" value="{{ $s['accountant'] ?? '' }}">
                    <input type="hidden" name="bik" value="{{ $s['bik'] ?? '' }}">
                    <input type="hidden" name="bank" value="{{ $s['bank'] ?? '' }}">
                    <input type="hidden" name="checking_account" value="{{ $s['checking_account'] ?? '' }}">
                    <input type="hidden" name="corr_account" value="{{ $s['corr_account'] ?? '' }}">
                    <input type="hidden" name="comment_bank" value="{{ $s['comment_bank'] ?? '' }}">
                </div>
            </section>
        </form>

        @include('partials.detail-sticky-actions', [
            'formId' => 'supplier-details-form',
            'saveLabel' => __('suppliers.save'),
            'cancelLabel' => __('suppliers.cancel'),
        ])
        @include('partials.detail-confirm-modal')
    @endif
</div>
@endsection

@section('scripts')
@if ($canManage)
<script>
(function waitBoot(n) {
    if (typeof window.bootDetailEditPage === 'function') {
        window.bootDetailEditPage({
            form: '#supplier-details-form',
            successMessage: @json(__('suppliers.updated')),
            errorMessage: @json(__('detail.error')),
        });
        return;
    }
    if (n > 0) setTimeout(function () { waitBoot(n - 1); }, 40);
})(50);
</script>
@endif
@endsection
