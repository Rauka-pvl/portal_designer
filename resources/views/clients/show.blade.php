@extends('layouts.dashboard')

@section('title', $client->full_name)

@push('styles')
    <style>
        .panel {
            background: #ffffff;
            border: 1px solid #94a3b8;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .dark .panel {
            background: #161615;
            border-color: #3E3E3A;
        }

        .field-label {
            font-size: 0.875rem;
            color: #64748b;
        }

        .dark .field-label {
            color: #A1A09A;
        }

        .btn {
            padding: 0.55rem 1rem;
            border-radius: 10px;
            border: 1px solid #94a3b8;
            background: #ffffff;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn:hover {
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .dark .btn {
            background: #0a0a0a;
            border-color: #3E3E3A;
            color: #A1A09A;
        }

        .btn-primary {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .btn-danger {
            border-color: rgba(239, 68, 68, 0.35);
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
        }

        .dark .btn-danger {
            color: #f87171;
        }

        .form-control:disabled {
            opacity: 0.85;
            cursor: not-allowed;
        }
    </style>
@endpush

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 dark:border-emerald-700/40 bg-emerald-50 dark:bg-emerald-900/10 px-4 py-3 text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                {{ $client->full_name }}
            </h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                {{ __('clients.client') }}
                #{{ $client->id }}
            </p>
        </div>

        <div class="flex gap-3">
            <button id="btn-edit" type="button" class="btn">
                {{ __('clients.edit') }}
            </button>
            <a href="{{ route('clients.index') }}" class="btn">
                {{ __('clients.close') }}
            </a>
        </div>
    </div>

    <div class="panel">
        <form id="client-details-form" method="POST" action="{{ route('clients.add_client') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <div class="field-label mb-2">{{ __('clients.client_type') }}</div>
                    <select id="client_type" name="client_type"
                        class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        disabled required>
                        <option value="person" @selected($client->client_type === 'person')>{{ __('clients.person') }}</option>
                        <option value="company" @selected($client->client_type === 'company')>{{ __('clients.company') }}</option>
                    </select>

                    <div class="field-label mb-2 mt-4" id="full_name_label">
                        {{ $client->client_type === 'person' ? __('clients.fio') : __('clients.company_name') }}
                    </div>
                    <input id="full_name" name="full_name" type="text" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        value="{{ $client->full_name }}" disabled required>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('clients.phone') }}</div>
                    <input id="phone" name="phone" type="text" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        value="{{ $client->phone }}" disabled required>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('clients.email') }}</div>
                    <input id="email" name="email" type="email" class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        value="{{ $client->email }}" disabled required>
                </div>

                <div>
                    <div class="field-label mb-2">{{ __('clients.status') }}</div>
                    <select id="status" name="status"
                        class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        disabled required>
                        <option value="new" @selected($client->status === 'new')>{{ __('clients.new') }}</option>
                        <option value="in_work" @selected($client->status === 'in_work')>{{ __('clients.in_work') }}</option>
                        <option value="not_working" @selected($client->status === 'not_working')>{{ __('clients.not_working') }}</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('clients.comment') }}</div>
                    <textarea id="comment" name="comment" rows="5"
                        class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control resize-none"
                        disabled>{{ $client->comment }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('clients.link') }}</div>
                    <input id="link" name="link" type="url"
                        class="w-full px-4 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] form-control"
                        value="{{ $client->link }}" disabled>
                </div>

                <div class="md:col-span-2">
                    <div class="field-label mb-2">{{ __('clients.files') }}</div>
                    <input id="files" name="files[]" type="file"
                        class="w-full text-sm text-[#64748b] dark:text-[#A1A09A] form-control"
                        disabled>
                    @php
                        $filePaths = [];
                        if (!empty($client->file_paths)) {
                            $decoded = json_decode($client->file_paths, true);
                            if (is_array($decoded)) {
                                $filePaths = array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
                            }
                        }
                        if (empty($filePaths) && !empty($client->file_path)) {
                            $filePaths = [$client->file_path];
                        }
                    @endphp

                    @if (!empty($filePaths))
                        <div class="mt-3 flex flex-col gap-2">
                            @foreach ($filePaths as $path)
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ asset('storage/' . $path) }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] dark:hover:border-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        {{ __('clients.view') }}
                                    </a>

                                    <a href="{{ asset('storage/' . $path) }}" download="{{ basename($path) }}"
                                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#94a3b8] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 10l5 5 5-5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15V3" />
                                        </svg>
                                        {{ __('clients.download') }}
                                    </a>

                                    <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">
                                        {{ basename($path) }}
                                    </span>

                                    <button type="button"
                                        onclick="window.deleteClientFileFromShow({{ $client->id }}, {{ $loop->index }})"
                                        class="edit-only-control hidden p-1.5 rounded border border-[#94a3b8] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors"
                                        title="{{ __('clients.delete_file') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs mt-3 text-[#64748b] dark:text-[#A1A09A]">-</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                <div class="flex gap-3">
                    <button id="btn-save" type="submit" class="btn btn-primary hidden">
                        {{ __('clients.save') }}
                    </button>
                    <button id="btn-cancel" type="button" class="btn hidden">
                        {{ __('clients.cancel') }}
                    </button>
                </div>

                <button type="submit" form="delete-client-form"
                    onclick="return confirm('{{ __('clients.delete_confirm') }}')"
                    class="btn btn-danger">
                    {{ __('clients.delete') }}
                </button>
            </div>
        </form>
    </div>

    <form id="delete-client-form" method="POST" action="{{ route('clients.delete_client', $client->id) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection

@section('scripts')
    <script>
        (function() {
            const btnEdit = document.getElementById('btn-edit');
            const btnSave = document.getElementById('btn-save');
            const btnCancel = document.getElementById('btn-cancel');
            const form = document.getElementById('client-details-form');
            const phoneInput = document.getElementById('phone');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Удаление конкретного файла клиента (по индексу в file_paths).
            window.deleteClientFileFromShow = async function(clientId, fileIndex) {
                if (!confirm('{{ __('clients.delete_file_confirm') }}')) return;
                try {
                    const r = await fetch(`/clients/${clientId}/files/${fileIndex}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
                        }
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                        projectAlert('error', data.message || '{{ __('clients.error') }}', '', 3200);
                        return;
                    }
                    location.reload();
                } catch (e) {
                    projectAlert('error', '{{ __('clients.error') }}', '', 3200);
                    console.error(e);
                }
            };

            // Маска телефона для редактирования клиента
            if (phoneInput && typeof IMask !== 'undefined') {
                IMask(phoneInput, {
                    mask: '+{7} (000) 000-00-00'
                });
            }

            // Подпись к полю `full_name` зависит от типа клиента (ФИО / Название компании).
            const clientTypeSelect = document.getElementById('client_type');
            const fullNameLabel = document.getElementById('full_name_label');

            function updateFullNameLabel() {
                if (!clientTypeSelect || !fullNameLabel) return;
                fullNameLabel.textContent = clientTypeSelect.value === 'company'
                    ? '{{ __('clients.company_name') }}'
                    : '{{ __('clients.fio') }}';
            }

            if (clientTypeSelect && fullNameLabel) {
                updateFullNameLabel();
                clientTypeSelect.addEventListener('change', updateFullNameLabel);
            }

            const toggleEdit = (enabled) => {
                const controls = form.querySelectorAll('input, select, textarea');
                controls.forEach(el => {
                    // Hidden input `client_id` не трогаем, у него type hidden.
                    if (el.type === 'hidden') return;
                    el.disabled = !enabled;
                });

                if (btnSave) btnSave.classList.toggle('hidden', !enabled);
                if (btnCancel) btnCancel.classList.toggle('hidden', !enabled);

                if (btnEdit) btnEdit.classList.toggle('hidden', enabled);

                document.querySelectorAll('.edit-only-control').forEach((el) => {
                    el.classList.toggle('hidden', !enabled);
                });
            };

            if (btnEdit) {
                btnEdit.addEventListener('click', () => toggleEdit(true));
            }

            if (btnCancel) {
                btnCancel.addEventListener('click', () => location.reload());
            }
        })();
    </script>
@endsection

