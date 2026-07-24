@extends('layouts.dashboard')

@section('title', $client->full_name)
@section('header_title', $client->full_name)

@php
    $statusMap = [
        'new' => __('clients.new'),
        'in_work' => __('clients.in_work'),
        'not_working' => __('clients.not_working'),
    ];
    $statusLabel = $statusMap[$client->status] ?? $client->status;
    $typeLabel = $client->client_type === 'company' ? __('clients.company') : __('clients.person');
    $nameLabel = $client->client_type === 'company' ? __('clients.company_name') : __('clients.fio');
    $filePaths = [];
    if (! empty($client->file_paths)) {
        $decoded = json_decode($client->file_paths, true);
        if (is_array($decoded)) {
            $filePaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
        }
    }
    if (empty($filePaths) && ! empty($client->file_path)) {
        $filePaths = [$client->file_path];
    }
@endphp

@section('content')
<div class="pb-28 max-w-6xl mx-auto">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 dark:border-emerald-700/40 bg-emerald-50 dark:bg-emerald-900/10 px-4 py-3 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex items-start gap-3">
            @include('partials.back-link', [
                'fallback' => route('clients.index'),
                'label' => __('clients.close'),
                'variant' => 'btn',
                'icon' => true,
            ])
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $client->full_name }}</h1>
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.client') }} #{{ $client->id }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <button id="btn-edit" type="button"
                class="inline-flex items-center justify-center min-h-10 px-4 rounded-xl border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 text-sm font-medium transition-colors">
                {{ __('clients.edit') }}
            </button>
            <details class="relative">
                <summary class="list-none cursor-pointer inline-flex items-center justify-center min-h-10 min-w-10 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                    aria-label="{{ __('detail.more_actions') }}">⋯</summary>
                <div class="absolute right-0 mt-2 w-48 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg p-1 z-20">
                    <button type="submit" form="delete-client-form"
                        onclick="return confirm(@json(__('clients.delete_confirm')))"
                        class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                        {{ __('clients.delete') }}
                    </button>
                </div>
            </details>
        </div>
    </div>

    <div data-detail-view class="mb-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.client_type') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $typeLabel }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.status') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $statusLabel }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.phone') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC] break-all">{{ $client->phone ?: '—' }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.email') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC] break-all">{{ $client->email ?: '—' }}</p>
        </div>
    </div>

    <section data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ $nameLabel }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ $client->full_name }}</dd>
            </div>
            <div>
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.link') }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC] break-all">
                    @if ($client->link)
                        <a href="{{ $client->link }}" target="_blank" rel="noopener" class="text-[#f59e0b] hover:underline">{{ $client->link }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.comment') }}</dt>
                <dd class="mt-1 text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap">{{ $client->comment ?: '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('clients.files') }}</dt>
                <dd>
                    @include('partials.file-actions-list', [
                        'filePaths' => $filePaths,
                        'deleteCallback' => 'window.deleteClientFileFromShow',
                        'deleteEntityId' => $client->id,
                    ])
                </dd>
            </div>
        </dl>
    </section>

    <form id="client-details-form" method="POST" action="{{ route('clients.add_client') }}"
        enctype="multipart/form-data" data-ajax="1" class="space-y-4">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->id }}">

        <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="client_type">{{ __('clients.client_type') }}</label>
                    <select id="client_type" name="client_type" required
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                        <option value="person" @selected($client->client_type === 'person')>{{ __('clients.person') }}</option>
                        <option value="company" @selected($client->client_type === 'company')>{{ __('clients.company') }}</option>
                    </select>

                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2 mt-4" id="full_name_label" for="full_name">{{ $nameLabel }}</label>
                    <input id="full_name" name="full_name" type="text" required value="{{ $client->full_name }}"
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="phone">{{ __('clients.phone') }}</label>
                    <input id="phone" name="phone" type="text" required value="{{ $client->phone }}"
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="email">{{ __('clients.email') }}</label>
                    <input id="email" name="email" type="email" required value="{{ $client->email }}"
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div>
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="status">{{ __('clients.status') }}</label>
                    <select id="status" name="status" required
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                        <option value="new" @selected($client->status === 'new')>{{ __('clients.new') }}</option>
                        <option value="in_work" @selected($client->status === 'in_work')>{{ __('clients.in_work') }}</option>
                        <option value="not_working" @selected($client->status === 'not_working')>{{ __('clients.not_working') }}</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="comment">{{ __('clients.comment') }}</label>
                    <textarea id="comment" name="comment" rows="5"
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] resize-none">{{ $client->comment }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="link">{{ __('clients.link') }}</label>
                    <input id="link" name="link" type="url" value="{{ $client->link }}"
                        class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC]">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm text-[#64748b] dark:text-[#A1A09A] mb-2" for="files">{{ __('clients.files') }}</label>
                    <input id="files" name="files[]" type="file"
                        class="w-full text-sm text-[#64748b] dark:text-[#A1A09A]">
                    @include('partials.file-actions-list', [
                        'filePaths' => $filePaths,
                        'deleteCallback' => 'window.deleteClientFileFromShow',
                        'deleteEntityId' => $client->id,
                        'includeExistingHidden' => true,
                    ])
                </div>
            </div>
        </section>
    </form>

    <form id="delete-client-form" method="POST" action="{{ route('clients.delete_client', $client->id) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

@include('partials.detail-sticky-actions', [
    'formId' => 'client-details-form',
    'saveLabel' => __('clients.save'),
    'cancelLabel' => __('clients.cancel'),
])
@include('partials.detail-confirm-modal')
@endsection

@section('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const phoneInput = document.getElementById('phone');
    const clientTypeSelect = document.getElementById('client_type');
    const fullNameLabel = document.getElementById('full_name_label');

    window.deleteClientFileFromShow = async function (clientId, fileIndex) {
        if (!confirm(@json(__('clients.delete_file_confirm')))) return;
        try {
            const r = await fetch(`/clients/${clientId}/files/${fileIndex}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}) },
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) {
                projectAlert?.('error', data.message || @json(__('clients.error')), '', 3200);
                return;
            }
            location.reload();
        } catch (e) {
            projectAlert?.('error', @json(__('clients.error')), '', 3200);
        }
    };

    if (phoneInput && typeof IMask !== 'undefined') {
        IMask(phoneInput, { mask: '+{7} (000) 000-00-00' });
    }

    function updateFullNameLabel() {
        if (!clientTypeSelect || !fullNameLabel) return;
        fullNameLabel.textContent = clientTypeSelect.value === 'company'
            ? @json(__('clients.company_name'))
            : @json(__('clients.fio'));
    }
    clientTypeSelect?.addEventListener('change', updateFullNameLabel);

    (function waitBoot(n) {
        if (typeof window.bootDetailEditPage === 'function') {
            window.bootDetailEditPage({
                form: '#client-details-form',
                successMessage: @json(__('clients.saved')),
                errorMessage: @json(__('clients.error')),
            });
            return;
        }
        if (n > 0) setTimeout(function () { waitBoot(n - 1); }, 40);
    })(50);
})();
</script>
@endsection
