@extends('layouts.dashboard')

@section('title', __('clients.my_clients'))
@section('header_title', __('clients.my_clients'))
@section('main_class', 'crm-main-fill')

@push('styles')
<style>
    #clients-kanban-panel.crm-board {
        display: flex;
        flex-direction: row;
        align-items: stretch;
        gap: 0.875rem;
        flex: 1 1 auto;
        min-height: 0;
        height: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 0.25rem;
        scrollbar-gutter: stable;
        overscroll-behavior-x: contain;
        -webkit-overflow-scrolling: touch;
    }
    #clients-kanban-panel.crm-board.is-hidden {
        display: none !important;
    }
    #clients-list-panel.crm-view-panel {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        background: var(--crm-surface);
        border-radius: 8px;
        box-shadow: var(--crm-shadow);
    }
    .crm-toolbar-select {
        width: auto;
        min-width: 8.5rem;
        min-height: 38px;
        height: 38px;
    }
    .crm-clients-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.65rem 0.75rem;
        border-top: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
        background: var(--crm-surface);
    }
    .crm-clients-pagination-pages {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
    }
    .crm-actions-menu {
        position: relative;
        display: inline-flex;
    }
    .crm-actions-dropdown {
        position: absolute;
        right: 0;
        top: calc(100% + 4px);
        z-index: 20;
        min-width: 10rem;
        background: var(--crm-surface);
        border: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
        border-radius: 8px;
        box-shadow: var(--crm-shadow);
        padding: 0.25rem;
        display: none;
    }
    .crm-actions-dropdown.open { display: block; }
    .crm-actions-dropdown button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.45rem 0.65rem;
        border: none;
        background: transparent;
        color: var(--crm-text);
        font-size: 0.8125rem;
        border-radius: 6px;
        cursor: pointer;
    }
    .crm-actions-dropdown button:hover {
        background: color-mix(in srgb, var(--crm-accent) 8%, var(--crm-surface));
    }
    .crm-actions-dropdown button.is-danger {
        color: var(--crm-danger);
    }
    .crm-type-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.4rem;
        border-radius: 999px;
        font-size: 0.625rem;
        font-weight: 600;
        background: color-mix(in srgb, var(--crm-accent) 14%, transparent);
        color: var(--crm-accent);
        white-space: nowrap;
    }
    .crm-detail-row {
        display: grid;
        grid-template-columns: 8rem 1fr;
        gap: 0.5rem;
        padding: 0.45rem 0;
        border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 14%, transparent);
        font-size: 0.875rem;
    }
    .crm-detail-row dt { color: var(--crm-muted); }
    .crm-detail-row dd { color: var(--crm-text); margin: 0; word-break: break-word; }
    .crm-related-project {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.65rem 0.75rem;
        border: 1px solid color-mix(in srgb, var(--crm-border) 28%, transparent);
        border-radius: 8px;
        background: var(--crm-surface-2);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }
    .crm-related-project:hover {
        border-color: color-mix(in srgb, var(--crm-accent) 40%, var(--crm-border));
    }
    #client-form-modal.crm-modal-root {
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    #client-form-modal .crm-modal {
        width: min(820px, 90vw);
        max-width: 850px;
        height: auto;
        max-height: min(90dvh, 92vh);
        display: flex;
        flex-direction: column;
    }
    #client-form-modal .crm-client-form-body {
        flex: 1 1 auto;
        min-height: 0;
        width: 100%;
        overflow: auto;
        padding: 1rem 1.15rem;
        background: var(--crm-bg);
    }
    #client-form-modal .crm-client-form-body .crm-section {
        max-width: none;
    }
    #client-form-modal .crm-modal-footer {
        justify-content: flex-end;
    }
    #client-detail-modal.crm-modal-root {
        align-items: center;
        justify-content: center;
    }
    #client-detail-modal .crm-modal {
        width: min(1200px, 92vw);
        height: auto;
        max-height: min(92dvh, 94vh);
    }
    @media (max-width: 1024px) {
        #client-form-modal .crm-modal {
            width: min(90vw, 850px);
        }
    }
    @media (max-width: 768px) {
        .crm-toolbar { flex-wrap: wrap; }
        .crm-toolbar-right { width: 100%; flex-wrap: wrap; }
        .crm-toolbar-search { width: 100%; max-width: none; }
        .crm-detail-row { grid-template-columns: 1fr; gap: 0.15rem; }
        #client-form-modal.crm-modal-root { padding: 0; }
        #client-form-modal .crm-modal {
            width: 100vw;
            max-width: 100vw;
            height: 100dvh;
            max-height: 100dvh;
            border-radius: 0;
            border: none;
        }
        #client-form-modal .crm-grid-2 {
            grid-template-columns: 1fr;
        }
        #client-detail-modal.crm-modal-root { padding: 0; }
        #client-detail-modal .crm-modal {
            width: 100vw;
            max-width: 100vw;
            height: 100dvh;
            max-height: 100dvh;
            border-radius: 0;
            border: none;
        }
    }
</style>
@endpush

@section('content')
@php $canManage = (bool) ($canManagePipeline ?? false); @endphp
<div class="crm-workspace" id="crm-clients-workspace" data-locale="{{ str_replace('_', '-', app()->getLocale()) }}">
    <div class="crm-toolbar" role="toolbar" aria-label="{{ __('clients.my_clients') }}">
        <div class="crm-toolbar-left">
            <button type="button" id="clients-create-btn" class="crm-btn crm-btn-primary crm-btn-sm">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                {{ __('clients.create_client') }}
            </button>
            <div class="crm-view-switch" role="group" aria-label="{{ __('clients.kanban') }}">
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="kanban"
                    aria-pressed="true" title="{{ __('clients.kanban') }}">{{ __('clients.kanban') }}</button>
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="list"
                    aria-pressed="false" title="{{ __('clients.list') }}">{{ __('clients.list') }}</button>
            </div>
            @if ($canManage)
                <button type="button" id="pipeline-settings-btn" class="crm-btn crm-btn-secondary crm-btn-sm crm-pipeline-settings-btn"
                    title="{{ __('projects.pipeline_settings') }}"
                    aria-label="{{ __('projects.pipeline_settings') }}">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            @endif
        </div>
        <div class="crm-toolbar-right">
            <div class="relative" style="display:inline-flex;align-items:center;">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position:absolute;left:0.7rem;pointer-events:none;color:var(--crm-muted)">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="clients-search" class="crm-input crm-toolbar-search" style="padding-left:2rem"
                    placeholder="{{ __('clients.search') }}" autocomplete="off">
            </div>
            <select id="clients-status-filter" class="crm-input crm-toolbar-select" aria-label="{{ __('clients.status') }}">
                <option value="">{{ __('clients.all_statuses') }}</option>
            </select>
            <select id="clients-type-filter" class="crm-input crm-toolbar-select" aria-label="{{ __('clients.client_type') }}">
                <option value="">{{ __('clients.all_types') }}</option>
                <option value="person">{{ __('clients.person') }}</option>
                <option value="company">{{ __('clients.company') }}</option>
            </select>
        </div>
    </div>

    <div class="crm-viewport">
        <div id="clients-kanban-panel" class="crm-board" role="region" aria-label="{{ __('clients.kanban') }}"></div>

        <div id="clients-list-panel" class="crm-view-panel is-hidden" role="region" aria-label="{{ __('clients.list') }}">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th data-sort="full_name">{{ __('clients.client') }}</th>
                        <th data-sort="phone">{{ __('clients.contacts') }}</th>
                        <th data-sort="client_type">{{ __('clients.client_type') }}</th>
                        <th data-sort="status">{{ __('clients.status') }}</th>
                        <th data-sort="projects_count">{{ __('clients.projects') }}</th>
                        <th data-sort="projects_budget">{{ __('clients.budget') }}</th>
                        <th data-sort="updated_at" class="hidden lg:table-cell">{{ __('clients.updated') }}</th>
                        <th>{{ __('clients.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="clients-table-body"></tbody>
            </table>
            <div class="crm-clients-pagination" id="clients-pagination">
                <label class="inline-flex items-center gap-2 text-xs text-[var(--crm-muted)]">
                    {{ __('clients.per_page') }}
                    <select id="clients-per-page" class="crm-input" style="width:auto;min-height:32px;height:32px;padding:0.2rem 0.5rem">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
                <div class="crm-clients-pagination-pages" id="clients-pagination-pages"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Form modal --}}
<div id="client-form-modal" class="crm-modal-root" aria-hidden="true">
    <div class="crm-modal-backdrop" data-close-backdrop></div>
    <div class="crm-modal" role="dialog" aria-modal="true" aria-labelledby="client-form-title">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div>
                    <div id="client-form-title" class="crm-modal-title-input">{{ __('clients.new_client_title') }}</div>
                    <p id="client-form-subtitle" class="text-xs text-[var(--crm-muted)] mt-0.5">{{ __('clients.new_client_subtitle') }}</p>
                </div>
                <button type="button" id="client-form-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('clients.close') }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <form id="client-form" class="crm-client-form-body" method="POST" action="{{ route('clients.add_client') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="client_id" id="cf-client-id" value="">
                <div class="crm-section">
                    <div class="crm-section-title">{{ __('clients.basic_info') }}</div>
                    <div class="crm-grid-2">
                        <div>
                            <label class="crm-label" for="cf-client-type">{{ __('clients.client_type') }}</label>
                            <select id="cf-client-type" name="client_type" class="crm-input crm-select" required>
                                <option value="person">{{ __('clients.person') }}</option>
                                <option value="company">{{ __('clients.company') }}</option>
                            </select>
                            <div class="crm-field-error hidden" data-error="client_type"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="cf-status">{{ __('clients.status') }}</label>
                            <select id="cf-status" name="status" class="crm-input crm-select" required>
                                <option value="new">{{ __('clients.new') }}</option>
                                <option value="in_work">{{ __('clients.in_work') }}</option>
                                <option value="not_working">{{ __('clients.not_working') }}</option>
                            </select>
                            <div class="crm-field-error hidden" data-error="status"></div>
                        </div>
                        <div style="grid-column:1 / -1">
                            <label class="crm-label" for="cf-full-name" id="cf-full-name-label">{{ __('clients.fio') }}</label>
                            <input type="text" id="cf-full-name" name="full_name" class="crm-input" required autocomplete="name">
                            <div class="crm-field-error hidden" data-error="full_name"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="cf-phone">{{ __('clients.phone') }}</label>
                            <input type="tel" id="cf-phone" name="phone" class="crm-input" required autocomplete="tel" placeholder="+7 (700) 000-00-00">
                            <div class="crm-field-error hidden" data-error="phone"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="cf-email">{{ __('clients.email') }}</label>
                            <input type="email" id="cf-email" name="email" class="crm-input" required autocomplete="email">
                            <div class="crm-field-error hidden" data-error="email"></div>
                        </div>
                    </div>
                </div>

                <div class="crm-section">
                    <div class="crm-section-title">{{ __('clients.additional_info') }}</div>
                    <div class="mb-3">
                        <label class="crm-label" for="cf-link">{{ __('clients.link') }}</label>
                        <input type="url" id="cf-link" name="link" class="crm-input" placeholder="https://...">
                        <div class="crm-field-error hidden" data-error="link"></div>
                    </div>
                    <div class="mb-3">
                        <label class="crm-label" for="cf-comment">{{ __('clients.comment') }}</label>
                        <textarea id="cf-comment" name="comment" rows="3" class="crm-input" placeholder="{{ __('clients.comment_placeholder') }}"></textarea>
                        <div class="crm-field-error hidden" data-error="comment"></div>
                    </div>
                    <div>
                        @include('partials.modal-file-picker', [
                            'pickerId' => 'client',
                            'inputName' => 'files[]',
                            'title' => __('clients.files'),
                            'subtitle' => __('projects.files_subtitle'),
                            'notSelectedText' => __('clients.choose_file'),
                            'selectButtonText' => __('clients.upload_files'),
                            'viewLabel' => __('clients.view'),
                            'deleteLabel' => __('clients.delete_file'),
                        ])
                        <div class="crm-field-error hidden" data-error="files"></div>
                    </div>
                </div>
        </form>

        <div class="crm-modal-footer">
            <button type="button" id="client-form-cancel" class="crm-btn crm-btn-secondary">{{ __('clients.cancel') }}</button>
            <button type="submit" form="client-form" id="client-form-save" class="crm-btn crm-btn-primary">{{ __('clients.create_client') }}</button>
        </div>
    </div>
</div>

{{-- Detail modal --}}
<div id="client-detail-modal" class="crm-modal-root" aria-hidden="true">
    <div class="crm-modal-backdrop" data-close-backdrop></div>
    <div class="crm-modal" role="dialog" aria-modal="true" aria-labelledby="client-detail-title">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <div id="client-detail-title" class="crm-modal-title-input truncate">{{ __('clients.client') }}</div>
                    <div id="client-detail-badges" class="flex items-center gap-2 mt-1 flex-wrap"></div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button" id="client-detail-edit" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('clients.edit') }}</button>
                    <button type="button" id="client-detail-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('clients.close') }}">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="crm-modal-tabs">
            <button type="button" class="crm-modal-tab active" data-dtab="general">{{ __('clients.main_tab') }}</button>
            <button type="button" class="crm-modal-tab" data-dtab="projects">{{ __('clients.projects_tab') }}</button>
        </div>

        <div class="crm-modal-work">
            <div class="crm-modal-main" style="padding:1rem 1.15rem;overflow:auto">
                <div data-dpanel="general" class="cd-panel">
                    <dl id="client-detail-general"></dl>
                    <div id="client-detail-files" class="mt-4"></div>
                </div>
                <div data-dpanel="projects" class="cd-panel hidden">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold m-0">{{ __('clients.related_projects') }}</h3>
                        <button type="button" id="client-detail-create-project" class="crm-btn crm-btn-primary crm-btn-sm">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            {{ __('clients.create_project') }}
                        </button>
                    </div>
                    <div id="client-detail-projects" class="space-y-2"></div>
                </div>
            </div>
        </div>

        <div class="crm-modal-footer">
            <button type="button" id="client-detail-delete" class="crm-btn crm-btn-secondary" style="color:var(--crm-danger)">{{ __('clients.delete') }}</button>
            <button type="button" id="client-detail-close-footer" class="crm-btn crm-btn-secondary">{{ __('clients.close') }}</button>
        </div>
    </div>
</div>

{{-- Unsaved confirm --}}
<div id="client-unsaved-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-3">{{ __('clients.unsaved_leave_title') }}</h3>
        <div class="flex gap-2 justify-end">
            <button type="button" id="client-unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('clients.continue_editing') }}</button>
            <button type="button" id="client-unsaved-leave" class="crm-btn crm-btn-primary">{{ __('clients.leave_without_saving') }}</button>
        </div>
    </div>
</div>

{{-- Delete confirm --}}
<div id="client-delete-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('clients.delete') }}</h3>
        <p id="client-delete-message" class="text-sm text-[var(--crm-muted)] mb-4">{{ __('clients.delete_confirm') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="client-delete-cancel" class="crm-btn crm-btn-secondary">{{ __('clients.cancel') }}</button>
            <button type="button" id="client-delete-confirm" class="crm-btn crm-btn-primary" style="background:var(--crm-danger);border-color:var(--crm-danger)">{{ __('clients.delete') }}</button>
        </div>
    </div>
</div>

@include('designer.projects.partials.pipeline-settings-modals')

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const seed = @json($clientsData ?? []);
    const pipelineSeed = @json($pipeline ?? ['stages' => []]);
    const canManagePipeline = @json((bool) ($canManagePipeline ?? false));
    const locale = document.getElementById('crm-clients-workspace')?.dataset.locale || 'ru-RU';
    const VIEW_KEY = 'crm.clients.view';
    const PER_PAGE_KEY = 'crm.clients.perPage';

    const routes = {
        search: @json(route('clients.search')),
        save: @json(route('clients.add_client')),
        show: (id) => @json(url('/clients')) + '/' + id,
        status: (id) => @json(url('/clients')) + '/' + id + '/status',
        destroy: (id) => @json(url('/clients/delete')) + '/' + id,
        deleteFile: (id, idx) => @json(url('/clients')) + '/' + id + '/files/' + idx,
        projectsIndex: @json(route('projects.index')),
        projectShow: (id) => @json(url('/projects')) + '/' + id,
        pipelineSync: @json(route('pipelines.sync')),
    };

    const i18n = {
        person: @json(__('clients.person')),
        company: @json(__('clients.company')),
        legalEntity: @json(__('clients.legal_entity')),
        fio: @json(__('clients.fio')),
        companyName: @json(__('clients.company_name')),
        newClient: @json(__('clients.new_client_title')),
        newSubtitle: @json(__('clients.new_client_subtitle')),
        editClient: @json(__('clients.edit_client_title')),
        statuses: {
            new: @json(__('clients.new')),
            in_work: @json(__('clients.in_work')),
            not_working: @json(__('clients.not_working')),
        },
        statusColors: { new: '#3b82f6', in_work: '#f59e0b', not_working: '#64748b' },
        open: @json(__('clients.open')),
        edit: @json(__('clients.edit')),
        delete: @json(__('clients.delete')),
        createProject: @json(__('clients.create_project')),
        moreActions: @json(__('clients.more_actions')),
        noClients: @json(__('clients.no_clients')),
        noClientsTitle: @json(__('clients.no_clients_title')),
        noClientsBody: @json(__('clients.no_clients_body')),
        emptyFiltered: @json(__('clients.empty_filtered')),
        currency: @json(__('clients.currency')),
        error: @json(__('clients.error')),
        deleteConfirm: @json(__('clients.delete_confirm')),
        deleteFileConfirm: @json(__('clients.delete_file_confirm')),
        noRelatedProjects: @json(__('clients.no_related_projects')),
        phone: @json(__('clients.phone')),
        email: @json(__('clients.email')),
        link: @json(__('clients.link')),
        comment: @json(__('clients.comment')),
        files: @json(__('clients.files')),
        projects: @json(__('clients.projects')),
        budget: @json(__('clients.budget')),
        download: @json(__('clients.download')),
        view: @json(__('clients.view')),
        deleteFile: @json(__('clients.delete_file')),
        prev: @json(__('clients.prev')),
        next: @json(__('clients.next')),
        saved: @json(__('clients.saved')),
        added: @json(__('clients.added')),
        client: @json(__('clients.client')),
        contacts: @json(__('clients.contacts')),
        type: @json(__('clients.client_type')),
        status: @json(__('clients.status')),
        updated: @json(__('clients.updated')),
        allStatuses: @json(__('clients.all_statuses')),
    };

    const formModal = document.getElementById('client-form-modal');
    const detailModal = document.getElementById('client-detail-modal');
    const unsavedModal = document.getElementById('client-unsaved-modal');
    const deleteModal = document.getElementById('client-delete-modal');
    [formModal, detailModal, unsavedModal, deleteModal].forEach((el) => {
        if (el && el.parentElement !== document.body) document.body.appendChild(el);
    });

    function readStoredView() {
        const params = new URLSearchParams(window.location.search);
        const fromQuery = params.get('view');
        if (fromQuery === 'kanban' || fromQuery === 'list') return fromQuery;
        if (fromQuery === 'funnel' || fromQuery === 'table') return fromQuery === 'funnel' ? 'kanban' : 'list';
        if (fromQuery === 'cards') return 'list';
        const fromStore = localStorage.getItem(VIEW_KEY);
        if (fromStore === 'kanban' || fromStore === 'list') return fromStore;
        if (fromStore === 'funnel' || fromStore === 'table') return fromStore === 'funnel' ? 'kanban' : 'list';
        if (fromStore === 'cards') return 'list';
        return 'kanban';
    }

    const state = {
        clients: Array.isArray(seed) ? [...seed] : [],
        pipeline: pipelineSeed && typeof pipelineSeed === 'object'
            ? { ...pipelineSeed, stages: [...(pipelineSeed.stages || [])] }
            : { stages: [] },
        view: readStoredView(),
        search: '',
        status: '',
        type: '',
        sortKey: 'full_name',
        sortDir: 'asc',
        page: 1,
        perPage: parseInt(localStorage.getItem(PER_PAGE_KEY) || '10', 10) || 10,
        dirty: false,
        snapshot: null,
        editingId: null,
        detailId: null,
        detailProjects: [],
        detailTab: 'general',
        kanbanScroll: 0,
        deleteId: null,
        deleteForce: false,
        phoneMask: null,
    };

    const els = {
        search: document.getElementById('clients-search'),
        status: document.getElementById('clients-status-filter'),
        type: document.getElementById('clients-type-filter'),
        listPanel: document.getElementById('clients-list-panel'),
        kanbanPanel: document.getElementById('clients-kanban-panel'),
        tableBody: document.getElementById('clients-table-body'),
        paginationPages: document.getElementById('clients-pagination-pages'),
        perPage: document.getElementById('clients-per-page'),
        form: document.getElementById('client-form'),
        formTitle: document.getElementById('client-form-title'),
        formSubtitle: document.getElementById('client-form-subtitle'),
        fullNameLabel: document.getElementById('cf-full-name-label'),
        deleteMessage: document.getElementById('client-delete-message'),
        formStatus: document.getElementById('cf-status'),
    };

    if (els.perPage) els.perPage.value = String(state.perPage);

    const toast = (msg, type = 'success') => {
        if (typeof window.projectAlert === 'function') {
            window.projectAlert(type === 'error' ? 'error' : (type === 'warning' ? 'warning' : 'success'), msg, '', type === 'error' ? 3500 : 2500);
            return;
        }
        if (window.showAppToast) window.showAppToast(msg, type);
    };

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const money = (n) => {
        const num = Number(n);
        if (n === null || n === undefined || n === '' || Number.isNaN(num)) return '0 ' + i18n.currency;
        return num.toLocaleString(locale, { maximumFractionDigits: 0 }) + ' ' + i18n.currency;
    };

    const formatDate = (iso) => {
        if (!iso) return '—';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '—';
        return d.toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const debounce = (fn, ms = 400) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    function pipelineStages() {
        return (state.pipeline?.stages || []).slice().sort((a, b) => (a.position || 0) - (b.position || 0));
    }

    function statusLabel(key) {
        const stage = pipelineStages().find((s) => s.system_key === key);
        if (stage?.name) return stage.name;
        return i18n.statuses[key] || key || '—';
    }

    function statusColor(key) {
        const stage = pipelineStages().find((s) => s.system_key === key);
        return stage?.color || i18n.statusColors[key] || '#64748b';
    }

    function typeLabel(type) {
        return type === 'company' ? i18n.company : i18n.person;
    }

    function typeChip(client) {
        if ((client.client_type || 'person') !== 'company') return '';
        return `<span class="crm-type-chip">${escapeHtml(i18n.legalEntity)}</span>`;
    }

    function statusBadge(status) {
        const color = statusColor(status);
        return `<span class="crm-status-badge" style="box-shadow: inset 3px 0 0 ${escapeHtml(color)}">${escapeHtml(statusLabel(status))}</span>`;
    }

    function fillStatusSelects(selected) {
        const stages = pipelineStages();
        const options = stages.map((s) =>
            `<option value="${escapeHtml(s.system_key)}">${escapeHtml(s.name)}</option>`
        ).join('');
        if (els.status) {
            const cur = selected?.filter ?? state.status ?? '';
            els.status.innerHTML = `<option value="">${escapeHtml(i18n.allStatuses)}</option>` + options;
            els.status.value = cur;
            if (els.status.value !== cur) els.status.value = '';
        }
        if (els.formStatus) {
            const cur = selected?.form ?? els.formStatus.value;
            els.formStatus.innerHTML = options || `<option value="new">${escapeHtml(i18n.statuses.new)}</option>`;
            if (cur && [...els.formStatus.options].some((o) => o.value === cur)) els.formStatus.value = cur;
            else if (els.formStatus.options.length) els.formStatus.selectedIndex = 0;
        }
    }

    function syncUrl({ openId } = {}) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', state.view);
        if (openId) url.searchParams.set('open', String(openId));
        else url.searchParams.delete('open');
        window.history.replaceState({}, '', url);
    }

    function setView(view, { persist = true } = {}) {
        const allowed = view === 'list' ? 'list' : 'kanban';
        state.view = allowed;
        document.querySelectorAll('#crm-clients-workspace .crm-view-btn').forEach((btn) => {
            const on = btn.dataset.view === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        els.listPanel.classList.toggle('is-hidden', state.view !== 'list');
        els.kanbanPanel.classList.toggle('is-hidden', state.view !== 'kanban');
        if (persist) {
            localStorage.setItem(VIEW_KEY, state.view);
            syncUrl(state.detailId ? { openId: state.detailId } : {});
        }
        render();
    }

    function updateCount() { /* unused */ }

    function sortedClients(list) {
        const key = state.sortKey;
        const dir = state.sortDir === 'desc' ? -1 : 1;
        return [...list].sort((a, b) => {
            let av; let bv;
            if (key === 'projects_count' || key === 'projects_budget') {
                av = Number(a[key] || 0);
                bv = Number(b[key] || 0);
            } else if (key === 'updated_at') {
                av = a.updated_at ? new Date(a.updated_at).getTime() : 0;
                bv = b.updated_at ? new Date(b.updated_at).getTime() : 0;
            } else if (key === 'status') {
                av = statusLabel(a.status);
                bv = statusLabel(b.status);
            } else if (key === 'client_type') {
                av = typeLabel(a.client_type);
                bv = typeLabel(b.client_type);
            } else if (key === 'phone') {
                av = `${a.phone || ''} ${a.email || ''}`;
                bv = `${b.phone || ''} ${b.email || ''}`;
            } else {
                av = a[key] ?? '';
                bv = b[key] ?? '';
            }
            if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
            return String(av).localeCompare(String(bv), locale, { sensitivity: 'base' }) * dir;
        });
    }

    function closeActionMenus(except) {
        document.querySelectorAll('.crm-actions-dropdown.open').forEach((el) => {
            if (el !== except) el.classList.remove('open');
        });
    }

    function actionsMenuHtml(id) {
        return `
            <div class="crm-actions-menu" data-stop>
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-menu-toggle="${id}" aria-label="${escapeHtml(i18n.moreActions)}" title="${escapeHtml(i18n.moreActions)}">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
                    </svg>
                </button>
                <div class="crm-actions-dropdown" data-menu="${id}">
                    <button type="button" data-action="open" data-id="${id}">${escapeHtml(i18n.open)}</button>
                    <button type="button" data-action="edit" data-id="${id}">${escapeHtml(i18n.edit)}</button>
                    <button type="button" data-action="project" data-id="${id}">${escapeHtml(i18n.createProject)}</button>
                    <button type="button" class="is-danger" data-action="delete" data-id="${id}">${escapeHtml(i18n.delete)}</button>
                </div>
            </div>`;
    }

    function bindRowActions(root) {
        root.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.menuToggle;
                const menu = root.querySelector(`[data-menu="${id}"]`);
                const willOpen = menu && !menu.classList.contains('open');
                closeActionMenus();
                if (willOpen) menu.classList.add('open');
            });
        });
        root.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                closeActionMenus();
                const id = Number(btn.dataset.id);
                const action = btn.dataset.action;
                if (action === 'open') openDetail(id);
                else if (action === 'edit') openEdit(id);
                else if (action === 'project') createProject(id);
                else if (action === 'delete') askDelete(id);
            });
        });
    }

    function emptyStateHtml(colspan) {
        const hasFilters = !!(state.search || state.status || state.type);
        const title = hasFilters ? i18n.emptyFiltered : i18n.noClientsTitle;
        const body = hasFilters ? '' : i18n.noClientsBody;
        if (colspan) {
            return `<tr><td colspan="${colspan}" class="px-3 py-8 text-center text-[var(--crm-muted)]">
                <div class="font-medium mb-1">${escapeHtml(title)}</div>
                ${body ? `<div class="text-xs">${escapeHtml(body)}</div>` : ''}
            </td></tr>`;
        }
        return `<div class="crm-empty-inline" style="padding:2.5rem 1rem;text-align:center">
            <div class="font-medium mb-1">${escapeHtml(title)}</div>
            ${body ? `<div class="text-xs">${escapeHtml(body)}</div>` : ''}
        </div>`;
    }

    function renderPagination(total) {
        const pagesWrap = els.paginationPages;
        if (!pagesWrap) return;
        const totalPages = Math.max(1, Math.ceil(total / state.perPage));
        if (state.page > totalPages) state.page = totalPages;
        if (total <= state.perPage) {
            pagesWrap.innerHTML = '';
            return;
        }
        const pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            const start = Math.max(2, state.page - 1);
            const end = Math.min(totalPages - 1, state.page + 1);
            if (start > 2) pages.push('…');
            for (let i = start; i <= end; i++) pages.push(i);
            if (end < totalPages - 1) pages.push('…');
            pages.push(totalPages);
        }
        pagesWrap.innerHTML = `
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-page="${state.page - 1}" ${state.page <= 1 ? 'disabled' : ''}>${escapeHtml(i18n.prev)}</button>
            ${pages.map((p) => {
                if (p === '…') return `<span class="text-xs text-[var(--crm-muted)] px-1">…</span>`;
                const active = p === state.page;
                return `<button type="button" class="crm-btn crm-btn-sm ${active ? 'crm-btn-primary' : 'crm-btn-secondary'}" data-page="${p}" ${active ? 'disabled' : ''}>${p}</button>`;
            }).join('')}
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-page="${state.page + 1}" ${state.page >= totalPages ? 'disabled' : ''}>${escapeHtml(i18n.next)}</button>
        `;
        pagesWrap.querySelectorAll('button[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                state.page = Number(btn.dataset.page);
                renderTable();
            });
        });
    }

    function renderTable() {
        const items = sortedClients(state.clients);
        const start = (state.page - 1) * state.perPage;
        const pageItems = items.slice(start, start + state.perPage);
        if (!items.length) {
            els.tableBody.innerHTML = emptyStateHtml(8);
            renderPagination(0);
            return;
        }
        els.tableBody.innerHTML = pageItems.map((c) => `
            <tr data-id="${c.id}">
                <td data-label="${escapeHtml(i18n.client)}">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="font-medium truncate">${escapeHtml(c.full_name || '')}</span>
                        ${typeChip(c)}
                    </div>
                </td>
                <td data-label="${escapeHtml(i18n.contacts)}">
                    <div class="text-xs leading-5">
                        <div>${escapeHtml(c.phone || '—')}</div>
                        <div class="text-[var(--crm-muted)] truncate">${escapeHtml(c.email || '')}</div>
                    </div>
                </td>
                <td data-label="${escapeHtml(i18n.type)}">${escapeHtml(typeLabel(c.client_type))}</td>
                <td data-label="${escapeHtml(i18n.status)}">${statusBadge(c.status)}</td>
                <td data-label="${escapeHtml(i18n.projects)}">${Number(c.projects_count || 0)}</td>
                <td data-label="${escapeHtml(i18n.budget)}">${escapeHtml(money(c.projects_budget || c.sum_repair_budget_planned || 0))}</td>
                <td class="hidden lg:table-cell" data-label="${escapeHtml(i18n.updated)}">${escapeHtml(formatDate(c.updated_at))}</td>
                <td data-label="">${actionsMenuHtml(c.id)}</td>
            </tr>
        `).join('');

        document.querySelectorAll('#clients-list-panel .crm-table th[data-sort]').forEach((th) => {
            th.classList.toggle('is-asc', th.dataset.sort === state.sortKey && state.sortDir === 'asc');
            th.classList.toggle('is-desc', th.dataset.sort === state.sortKey && state.sortDir === 'desc');
        });

        els.tableBody.querySelectorAll('tr[data-id]').forEach((tr) => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('[data-stop]')) return;
                openDetail(Number(tr.dataset.id));
            });
        });
        bindRowActions(els.tableBody);
        renderPagination(items.length);
    }

    function renderKanban() {
        const scrollLeft = els.kanbanPanel.scrollLeft;
        const items = state.clients;
        const stages = pipelineStages();
        els.kanbanPanel.innerHTML = '';
        stages.forEach((stage) => {
            const cards = items.filter((c) => c.status === stage.system_key);
            const col = document.createElement('div');
            col.className = 'crm-board-col';
            col.style.setProperty('--col-color', stage.color || '#64748b');
            col.innerHTML = `
                <div class="crm-board-col-head">
                    <span class="crm-board-col-title" title="${escapeHtml(stage.name)}">${escapeHtml(stage.name)}</span>
                    <span class="crm-board-col-count">${cards.length}</span>
                </div>
                <div class="crm-board-col-body" data-drop="${escapeHtml(stage.system_key)}"></div>`;
            const body = col.querySelector('[data-drop]');
            const highlight = (on) => col.classList.toggle('is-drop-target', on);
            const onDragOver = (e) => { e.preventDefault(); highlight(true); };
            const onDragLeave = (e) => {
                if (!col.contains(e.relatedTarget)) highlight(false);
            };
            const onDrop = async (e) => {
                e.preventDefault();
                highlight(false);
                const id = Number(e.dataTransfer.getData('text/plain'));
                if (id) await moveClient(id, stage.system_key);
            };
            body.addEventListener('dragover', onDragOver);
            col.addEventListener('dragover', onDragOver);
            body.addEventListener('dragleave', onDragLeave);
            col.addEventListener('dragleave', onDragLeave);
            body.addEventListener('drop', onDrop);
            col.addEventListener('drop', onDrop);

            if (!cards.length) {
                body.innerHTML = `<div class="crm-board-empty">${escapeHtml(i18n.noClients)}</div>`;
            } else {
                cards.forEach((c) => {
                    const el = document.createElement('div');
                    el.className = 'crm-board-card';
                    el.draggable = true;
                    el.innerHTML = `
                        <div class="crm-board-card-title" title="${escapeHtml(c.full_name || '')}">
                            ${escapeHtml(c.full_name || '')} ${typeChip(c)}
                        </div>
                        <div class="crm-board-card-meta">
                            <div>${escapeHtml(c.phone || '—')}</div>
                            <div>${escapeHtml(c.email || '')}</div>
                            <div>${escapeHtml(i18n.projects)}: <strong>${Number(c.projects_count || 0)}</strong></div>
                            <div>${escapeHtml(i18n.budget)}: <strong>${escapeHtml(money(c.projects_budget || 0))}</strong></div>
                        </div>`;
                    el.addEventListener('dragstart', (e) => {
                        el.classList.add('is-dragging');
                        state.kanbanScroll = els.kanbanPanel.scrollLeft;
                        e.dataTransfer.setData('text/plain', String(c.id));
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    el.addEventListener('dragend', () => el.classList.remove('is-dragging'));
                    el.addEventListener('click', () => openDetail(c.id));
                    body.appendChild(el);
                });
            }
            els.kanbanPanel.appendChild(col);
        });
        els.kanbanPanel.scrollLeft = state.kanbanScroll || scrollLeft;
    }

    function render() {
        fillStatusSelects();
        if (state.view === 'list') {
            els.kanbanPanel.innerHTML = '';
            renderTable();
        } else {
            if (els.tableBody) els.tableBody.innerHTML = '';
            renderKanban();
        }
    }

    async function refreshClients() {
        const params = new URLSearchParams();
        if (state.search) params.set('search', state.search);
        if (state.status) params.set('status', state.status);
        if (state.type) params.set('type', state.type);
        const url = routes.search + (params.toString() ? '?' + params.toString() : '');
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            state.clients = data.data || [];
            state.page = 1;
            render();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    const debouncedSearch = debounce(() => refreshClients(), 400);

    async function moveClient(id, status) {
        const client = state.clients.find((c) => c.id === id);
        if (!client || client.status === status) return;
        const prev = client.status;
        client.status = status;
        render();
        try {
            const res = await fetch(routes.status(id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ status }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            if (data.client) {
                const idx = state.clients.findIndex((c) => c.id === id);
                if (idx >= 0) state.clients[idx] = data.client;
            }
            render();
        } catch (e) {
            client.status = prev;
            render();
            toast(e.message || i18n.error, 'error');
        }
    }

    function openRoot(root) {
        root.classList.add('open');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeRoot(root) {
        root.classList.remove('open');
        root.setAttribute('aria-hidden', 'true');
        if (!formModal.classList.contains('open') && !detailModal.classList.contains('open')
            && !unsavedModal.classList.contains('open') && !deleteModal.classList.contains('open')) {
            document.body.style.overflow = '';
        }
    }

    function clearFieldErrors() {
        document.querySelectorAll('#client-form-modal [data-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showFieldErrors(errors) {
        clearFieldErrors();
        Object.entries(errors || {}).forEach(([key, msgs]) => {
            const el = document.querySelector(`#client-form-modal [data-error="${key}"]`);
            if (el) {
                el.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
                el.classList.remove('hidden');
            }
        });
    }

    function updateFullNameLabel() {
        const type = document.getElementById('cf-client-type')?.value;
        if (els.fullNameLabel) {
            els.fullNameLabel.textContent = type === 'company' ? i18n.companyName : i18n.fio;
        }
    }

    function readFormSnapshot() {
        return {
            client_id: document.getElementById('cf-client-id').value || '',
            client_type: document.getElementById('cf-client-type').value,
            status: document.getElementById('cf-status').value,
            full_name: document.getElementById('cf-full-name').value.trim(),
            phone: document.getElementById('cf-phone').value.trim(),
            email: document.getElementById('cf-email').value.trim(),
            link: document.getElementById('cf-link').value.trim(),
            comment: document.getElementById('cf-comment').value.trim(),
        };
    }

    function markDirty() {
        if (!state.snapshot) return;
        state.dirty = JSON.stringify(readFormSnapshot()) !== JSON.stringify(state.snapshot);
    }

    function applyForm(client) {
        document.getElementById('cf-client-id').value = client?.id ? String(client.id) : '';
        document.getElementById('cf-client-type').value = client?.client_type || 'person';
        document.getElementById('cf-status').value = client?.status || 'new';
        document.getElementById('cf-full-name').value = client?.full_name || '';
        document.getElementById('cf-phone').value = client?.phone || '';
        if (state.phoneMask) state.phoneMask.updateValue();
        document.getElementById('cf-email').value = client?.email || '';
        document.getElementById('cf-link').value = client?.link || '';
        document.getElementById('cf-comment').value = client?.comment || '';
        updateFullNameLabel();
        clearFieldErrors();
        window.ModalFilePicker?.initFromDom?.(formModal);
        window.ModalFilePicker?.get('client')?.reset(client || null);
    }

    function openFormModal() {
        openRoot(formModal);
    }

    function closeFormModal(force = false) {
        if (state.dirty && !force) {
            openRoot(unsavedModal);
            return;
        }
        closeRoot(unsavedModal);
        closeRoot(formModal);
        state.dirty = false;
        state.editingId = null;
        state.snapshot = null;
        els.form?.reset();
        window.ModalFilePicker?.get('client')?.reset(null);
    }

    function openCreate() {
        state.editingId = null;
        state.dirty = false;
        els.formTitle.textContent = i18n.newClient;
        els.formSubtitle.textContent = i18n.newSubtitle;
        const saveBtn = document.getElementById('client-form-save');
        if (saveBtn) saveBtn.textContent = @json(__('clients.create_client'));
        applyForm(null);
        state.snapshot = readFormSnapshot();
        openFormModal();
    }

    function openEdit(id) {
        const client = state.clients.find((c) => c.id === id);
        if (!client) return;
        closeRoot(detailModal);
        state.editingId = id;
        state.dirty = false;
        els.formTitle.textContent = i18n.editClient;
        els.formSubtitle.textContent = client.full_name || '';
        const saveBtn = document.getElementById('client-form-save');
        if (saveBtn) saveBtn.textContent = @json(__('clients.save'));
        applyForm(client);
        state.snapshot = readFormSnapshot();
        openFormModal();
    }

    async function saveClient(e) {
        e.preventDefault();
        clearFieldErrors();
        const fd = new FormData(els.form);
        if (!fd.get('client_id')) fd.delete('client_id');
        try {
            const res = await fetch(routes.save, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                if (data.errors) showFieldErrors(data.errors);
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || i18n.error);
            }
            const client = data.client;
            const idx = state.clients.findIndex((c) => c.id === client.id);
            if (idx >= 0) state.clients[idx] = client;
            else state.clients.unshift(client);
            state.dirty = false;
            closeFormModal(true);
            toast(data.message || (state.editingId ? i18n.saved : i18n.added), 'success');
            render();
            if (state.detailId === client.id) openDetail(client.id);
        } catch (err) {
            toast(err.message || i18n.error, 'error');
        }
    }

    function switchDetailTab(name) {
        state.detailTab = name;
        detailModal.querySelectorAll('.crm-modal-tab').forEach((t) => {
            t.classList.toggle('active', t.dataset.dtab === name);
        });
        detailModal.querySelectorAll('.cd-panel').forEach((p) => {
            p.classList.toggle('hidden', p.dataset.dpanel !== name);
        });
    }

    function renderDetail(client, projects) {
        document.getElementById('client-detail-title').textContent = client.full_name || i18n.client;
        document.getElementById('client-detail-badges').innerHTML = `
            ${statusBadge(client.status)}
            ${typeChip(client) || `<span class="crm-status-badge">${escapeHtml(typeLabel(client.client_type))}</span>`}
        `;

        const general = document.getElementById('client-detail-general');
        const rows = [
            [i18n.phone, client.phone],
            [i18n.email, client.email],
            [i18n.link, client.link],
            [i18n.comment, client.comment],
            [i18n.projects, String(client.projects_count || 0)],
            [i18n.budget, money(client.projects_budget || client.sum_repair_budget_planned || 0)],
            [i18n.updated, formatDate(client.updated_at)],
        ];
        general.innerHTML = rows.map(([label, value]) => {
            let content = escapeHtml(value || '—');
            if (label === i18n.link && client.link) {
                content = `<a class="text-[var(--crm-accent)]" href="${escapeHtml(client.link)}" target="_blank" rel="noopener">${escapeHtml(client.link)}</a>`;
            }
            return `<div class="crm-detail-row"><dt>${escapeHtml(label)}</dt><dd>${content}</dd></div>`;
        }).join('');

        const filesBox = document.getElementById('client-detail-files');
        const paths = Array.isArray(client.file_paths) && client.file_paths.length
            ? client.file_paths
            : (client.file_path ? [client.file_path] : []);
        if (!paths.length) {
            filesBox.innerHTML = '';
        } else {
            filesBox.innerHTML = `
                <div class="crm-section-title mb-2">${escapeHtml(i18n.files)}</div>
                <div class="space-y-2">
                    ${paths.map((path, idx) => {
                        const name = String(path).split('/').pop();
                        const url = '/storage/' + String(path).replace(/^\//, '');
                        return `<div class="flex items-center justify-between gap-2 text-sm">
                            <a class="text-[var(--crm-accent)] truncate" href="${escapeHtml(url)}" target="_blank" rel="noopener">${escapeHtml(name)}</a>
                            <div class="flex gap-1 shrink-0">
                                <a class="crm-btn crm-btn-ghost crm-btn-sm" href="${escapeHtml(url)}" download>${escapeHtml(i18n.download)}</a>
                                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-del-file="${idx}" style="color:var(--crm-danger)">${escapeHtml(i18n.deleteFile)}</button>
                            </div>
                        </div>`;
                    }).join('')}
                </div>`;
            filesBox.querySelectorAll('[data-del-file]').forEach((btn) => {
                btn.addEventListener('click', () => deleteClientFile(client.id, Number(btn.dataset.delFile)));
            });
        }

        const projectsBox = document.getElementById('client-detail-projects');
        if (!projects.length) {
            projectsBox.innerHTML = `<div class="crm-empty-inline">${escapeHtml(i18n.noRelatedProjects)}</div>`;
        } else {
            projectsBox.innerHTML = projects.map((p) => {
                const progress = p.checklist_progress || { done: 0, total: 0, percent: 0 };
                return `<a class="crm-related-project" href="${escapeHtml(routes.projectShow(p.id))}">
                    <div class="min-w-0">
                        <div class="font-medium truncate">${escapeHtml(p.name || '')}</div>
                        <div class="text-xs text-[var(--crm-muted)] mt-0.5">
                            ${escapeHtml(p.status || '')}
                            · ${escapeHtml(money(p.planned_cost || 0))}
                            · ${progress.done}/${progress.total}
                        </div>
                    </div>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>`;
            }).join('');
        }
    }

    async function openDetail(id, { tab } = {}) {
        state.detailId = id;
        syncUrl({ openId: id });
        openRoot(detailModal);
        switchDetailTab(tab || state.detailTab || 'general');
        document.getElementById('client-detail-general').innerHTML = '<div class="crm-skeleton h-14"></div>';
        document.getElementById('client-detail-projects').innerHTML = '';
        try {
            const res = await fetch(routes.show(id), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            const client = data.client;
            const projects = data.projects || [];
            const idx = state.clients.findIndex((c) => c.id === id);
            if (idx >= 0) state.clients[idx] = client;
            state.detailProjects = projects;
            renderDetail(client, projects);
            updateCount();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
            closeDetail();
        }
    }

    function closeDetail() {
        closeRoot(detailModal);
        state.detailId = null;
        state.detailProjects = [];
        syncUrl();
    }

    function createProject(clientId) {
        const url = new URL(routes.projectsIndex, window.location.origin);
        url.searchParams.set('create', '1');
        url.searchParams.set('client_id', String(clientId));
        window.location.href = url.toString();
    }

    function askDelete(id, { force = false, message } = {}) {
        state.deleteId = id;
        state.deleteForce = !!force;
        els.deleteMessage.textContent = message || i18n.deleteConfirm;
        openRoot(deleteModal);
    }

    async function confirmDelete() {
        const id = state.deleteId;
        if (!id) return;
        try {
            const body = state.deleteForce ? JSON.stringify({ confirm: true }) : JSON.stringify({});
            const res = await fetch(routes.destroy(id), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 422 && data.needs_confirm) {
                closeRoot(deleteModal);
                askDelete(id, { force: true, message: data.message || i18n.deleteConfirm });
                return;
            }
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            state.clients = state.clients.filter((c) => c.id !== id);
            closeRoot(deleteModal);
            if (state.detailId === id) closeDetail();
            if (state.editingId === id) closeFormModal(true);
            state.deleteId = null;
            state.deleteForce = false;
            render();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    async function deleteClientFile(clientId, fileIndex) {
        if (!window.confirm(i18n.deleteFileConfirm)) return;
        try {
            const res = await fetch(routes.deleteFile(clientId, fileIndex), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            if (data.client) {
                const idx = state.clients.findIndex((c) => c.id === clientId);
                if (idx >= 0) state.clients[idx] = data.client;
                if (state.detailId === clientId) {
                    renderDetail(data.client, state.detailProjects);
                }
            }
            render();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    // Events
    document.getElementById('clients-create-btn')?.addEventListener('click', openCreate);
    document.querySelectorAll('#crm-clients-workspace .crm-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => setView(btn.dataset.view));
    });
    els.search?.addEventListener('input', () => {
        state.search = els.search.value.trim();
        debouncedSearch();
    });
    els.status?.addEventListener('change', () => {
        state.status = els.status.value;
        refreshClients();
    });
    els.type?.addEventListener('change', () => {
        state.type = els.type.value;
        refreshClients();
    });
    els.perPage?.addEventListener('change', () => {
        state.perPage = parseInt(els.perPage.value, 10) || 10;
        localStorage.setItem(PER_PAGE_KEY, String(state.perPage));
        state.page = 1;
        renderTable();
    });
    document.querySelectorAll('#clients-list-panel .crm-table th[data-sort]').forEach((th) => {
        th.addEventListener('click', () => {
            const key = th.dataset.sort;
            if (state.sortKey === key) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            else { state.sortKey = key; state.sortDir = 'asc'; }
            renderTable();
        });
    });

    document.getElementById('cf-client-type')?.addEventListener('change', () => {
        updateFullNameLabel();
        markDirty();
    });
    ['cf-status', 'cf-full-name', 'cf-phone', 'cf-email', 'cf-link', 'cf-comment'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', markDirty);
        document.getElementById(id)?.addEventListener('change', markDirty);
    });
    els.form?.addEventListener('submit', saveClient);
    document.getElementById('client-form-close')?.addEventListener('click', () => closeFormModal(false));
    document.getElementById('client-form-cancel')?.addEventListener('click', () => closeFormModal(false));
    formModal.querySelector('[data-close-backdrop]')?.addEventListener('click', () => closeFormModal(false));

    document.getElementById('client-unsaved-continue')?.addEventListener('click', () => closeRoot(unsavedModal));
    document.getElementById('client-unsaved-leave')?.addEventListener('click', () => closeFormModal(true));

    document.getElementById('client-detail-close')?.addEventListener('click', closeDetail);
    document.getElementById('client-detail-close-footer')?.addEventListener('click', closeDetail);
    detailModal.querySelector('[data-close-backdrop]')?.addEventListener('click', closeDetail);
    document.getElementById('client-detail-edit')?.addEventListener('click', () => {
        if (state.detailId) openEdit(state.detailId);
    });
    document.getElementById('client-detail-delete')?.addEventListener('click', () => {
        if (state.detailId) askDelete(state.detailId);
    });
    document.getElementById('client-detail-create-project')?.addEventListener('click', () => {
        if (state.detailId) createProject(state.detailId);
    });
    detailModal.querySelectorAll('.crm-modal-tab').forEach((tab) => {
        tab.addEventListener('click', () => switchDetailTab(tab.dataset.dtab));
    });

    document.getElementById('client-delete-cancel')?.addEventListener('click', () => {
        closeRoot(deleteModal);
        state.deleteId = null;
        state.deleteForce = false;
    });
    document.getElementById('client-delete-confirm')?.addEventListener('click', confirmDelete);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.crm-actions-menu')) closeActionMenus();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (unsavedModal.classList.contains('open')) { closeRoot(unsavedModal); return; }
        if (deleteModal.classList.contains('open')) {
            closeRoot(deleteModal);
            state.deleteId = null;
            state.deleteForce = false;
            return;
        }
        if (formModal.classList.contains('open')) { closeFormModal(false); return; }
        if (detailModal.classList.contains('open')) closeDetail();
    });

    // Phone mask
    const phoneEl = document.getElementById('cf-phone');
    if (phoneEl && typeof window.IMask !== 'undefined') {
        state.phoneMask = window.IMask(phoneEl, { mask: '+{7} (000) 000-00-00' });
    }

    window.CrmPipelineSettingsConfig = {
        csrf,
        routes,
        toast,
        escapeHtml,
        getProjects: () => state.clients,
        getPipeline: () => state.pipeline,
        setPipeline: (p) => { if (p) state.pipeline = p; },
        renderBoard: () => render(),
        pipelineType: 'client',
        i18n: {
            drag: @json(__('projects.pipeline_drag')),
            more: @json(__('projects.pipeline_more')),
            color: @json(__('projects.pipeline_color')),
            namePlaceholder: @json(__('projects.pipeline_stage_name_placeholder')),
            deleteMarked: @json(__('projects.pipeline_delete_marked')),
            deleteStage: @json(__('projects.pipeline_delete_stage')),
            undoDelete: @json(__('projects.pipeline_undo_delete')),
            moveTitle: @json(__('projects.pipeline_move_title')),
            moveCount: @json(__('projects.pipeline_move_count')),
            nameRequired: @json(__('projects.pipeline_name_required')),
            saved: @json(__('projects.pipeline_saved')),
            saveError: @json(__('projects.pipeline_save_error')),
        },
    };

    // Init
    window.ModalFilePicker?.initFromDom?.(document);
    fillStatusSelects();
    setView(state.view, { persist: true });

    const openParam = new URLSearchParams(window.location.search).get('open');
    if (openParam) {
        const openId = Number(openParam);
        if (openId) openDetail(openId);
    }
})();
</script>
@include('designer.projects.partials.pipeline-settings-scripts')
@endpush
