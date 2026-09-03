@extends('layouts.dashboard')

@section('title', __('projects.projects'))
@section('header_title', __('projects.projects'))
@section('main_class', 'crm-main-fill')

@section('content')
@php $canManage = (bool) ($canManagePipeline ?? false); @endphp

<div class="crm-workspace" id="crm-workspace" data-locale="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-project-limit="{{ $projectLimit['limit'] ?? '' }}"
    data-project-current="{{ $projectLimit['current'] ?? 0 }}"
    data-project-can-create="{{ ($projectLimit['can_create'] ?? true) ? '1' : '0' }}"
    data-upgrade-url="{{ route('subscription.index') }}">
    <div class="crm-toolbar" role="toolbar" aria-label="{{ __('projects.projects') }}">
        <div class="crm-toolbar-left">
            <button type="button" id="crm-create-btn" class="crm-btn crm-btn-primary crm-btn-sm {{ ($projectLimit['can_create'] ?? true) ? '' : 'opacity-60 cursor-not-allowed' }}">+ {{ __('projects.create_project') }}</button>
            <div class="crm-view-switch" role="group" aria-label="{{ __('projects.view_kanban') }}">
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="kanban"
                    aria-pressed="true" title="{{ __('projects.view_kanban') }}">{{ __('projects.kanban') }}</button>
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="list"
                    aria-pressed="false" title="{{ __('projects.view_list') }}">{{ __('projects.list') }}</button>
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
            <input type="search" id="crm-search" class="crm-input crm-toolbar-search" placeholder="{{ __('projects.search') }}" autocomplete="off">
            <label class="inline-flex items-center gap-1.5 text-xs text-[var(--crm-muted)] whitespace-nowrap">
                <input type="checkbox" id="crm-filter-overdue" class="rounded border-[color-mix(in_srgb,var(--crm-border)_55%,transparent)]">
                {{ __('projects.filter_overdue') }}
            </label>
        </div>
    </div>

    <div class="crm-viewport">
        <div id="crm-kanban" class="crm-board" role="region" aria-label="{{ __('projects.kanban') }}"></div>
        <div id="crm-list" class="crm-list-panel is-hidden" role="region" aria-label="{{ __('projects.list') }}">
            <table class="crm-table">
                <thead>
                    <tr>
                        <th data-sort="name">{{ __('projects.name') }}</th>
                        <th data-sort="client_name">{{ __('projects.client') }}</th>
                        <th data-sort="owner_name" class="hidden md:table-cell">{{ __('projects.responsible') }}</th>
                        <th data-sort="status">{{ __('projects.status') }}</th>
                        <th data-sort="planned_end_date">{{ __('projects.deadline') }}</th>
                        <th data-sort="budget">{{ __('projects.budget_plan') }}</th>
                        <th data-sort="progress" class="hidden lg:table-cell">{{ __('projects.progress') }}</th>
                        <th>{{ __('projects.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="crm-list-body"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('designer.projects.partials.project-address-scripts')
{{-- Modal portal: rendered at end of body via JS move --}}
<div id="project-modal-root" class="crm-modal-root" aria-hidden="true">
    <div class="crm-modal-backdrop" data-close-backdrop></div>
    <div class="crm-modal" role="dialog" aria-modal="true" aria-labelledby="ov-name">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div id="ov-title-display" class="crm-modal-title-input truncate text-[var(--crm-muted)]">{{ __('projects.new_project') }}</div>
                <input type="hidden" id="ov-status" value="">
                <button type="button" id="ov-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('projects.close') }}">✕</button>
            </div>
            <div id="ov-stage-bar" class="crm-stage-strip"></div>
        </div>

        <div class="crm-modal-tabs">
            <button type="button" class="crm-modal-tab active" data-tab="general">{{ __('projects.tab_general') }}</button>
            <button type="button" class="crm-modal-tab" data-tab="supplies">{{ __('projects.tab_supplies') }}</button>
            <button type="button" class="crm-modal-tab" data-tab="checklists">{{ __('projects.tab_checklists') }}</button>
            <button type="button" class="crm-modal-tab" data-tab="wazzup">{{ __('projects.tab_wazzup') }}</button>
        </div>

        <div id="ov-work" class="crm-modal-work is-split">
            <div class="crm-modal-main">
                <div data-panel="general" class="ov-panel">
                    <div class="crm-section">
                        <div class="crm-section-title">{{ __('projects.section_main') }}</div>
                        <div class="mb-3">
                            <label class="crm-label" for="ov-name">{{ __('projects.project_name') }}</label>
                            <input id="ov-name" class="crm-input" placeholder="{{ __('projects.project_name_placeholder') }}" required>
                            <div class="crm-field-error hidden" data-error="name"></div>
                        </div>
                        <div class="mb-3">
                            <label class="crm-label" for="ov-client">{{ __('projects.client') }}</label>
                            <select id="ov-client" class="crm-input crm-select"></select>
                            <div class="crm-field-error hidden" data-error="client_id"></div>
                        </div>
                        <div class="crm-grid-3">
                            <div>
                                <label class="crm-label" for="ov-start">{{ __('projects.start_date') }}</label>
                                <input type="date" id="ov-start" class="crm-input">
                            </div>
                            <div>
                                <label class="crm-label" for="ov-planned-end">{{ __('projects.planned_end_date') }}</label>
                                <input type="date" id="ov-planned-end" class="crm-input">
                            </div>
                            <div>
                                <label class="crm-label" for="ov-actual-end">{{ __('projects.actual_end_date') }}</label>
                                <input type="date" id="ov-actual-end" class="crm-input">
                            </div>
                        </div>
                    </div>

                    <div class="crm-section">
                        <div class="crm-section-title">{{ __('projects.section_finance') }}</div>
                        <div class="crm-grid-2">
                            <div>
                                <label class="crm-label" for="ov-budget-plan">{{ __('projects.budget_plan') }}</label>
                                <input type="number" step="0.01" min="0" id="ov-budget-plan" class="crm-input">
                            </div>
                            <div>
                                <label class="crm-label" for="ov-budget-fact">{{ __('projects.budget_fact') }}</label>
                                <input type="number" step="0.01" min="0" id="ov-budget-fact" class="crm-input">
                            </div>
                        </div>
                    </div>

                    <div class="crm-section">
                        <div class="crm-section-title">{{ __('projects.section_location') }}</div>
                        <div class="crm-grid-2 mb-3">
                            <div>
                                <label class="crm-label" for="ov-city">{{ __('projects.city') }}</label>
                                <select id="ov-city" class="crm-input crm-select">
                                    <option value="">{{ __('projects.select_city') }}</option>
                                    @foreach (($cities ?? []) as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                    <option value="other">{{ __('objects.other') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="crm-label" for="ov-object-type">{{ __('projects.object_type') }}</label>
                                <select id="ov-object-type" class="crm-input crm-select">
                                    <option value="">{{ __('projects.select_object_type') }}</option>
                                    @foreach (($objectTypes ?? []) as $type)
                                        <option value="{{ $type['id'] }}">{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 address-suggest relative">
                            <label class="crm-label" for="ov-address">{{ __('projects.object_address') }}</label>
                            <input id="ov-address" class="crm-input" type="text" autocomplete="off" autocorrect="off" spellcheck="false"
                                placeholder="{{ __('objects.address_placeholder') }}">
                            <div id="ov-address-suggest" class="address-suggest-list hidden"></div>
                            <div class="crm-field-error hidden" data-error="object_address"></div>
                            <div class="crm-field-error hidden" data-error="latitude"></div>
                        </div>
                        <div class="crm-grid-3 mb-3" id="ov-apartment-fields">
                            <div>
                                <label class="crm-label" for="ov-apartment-floor">{{ __('objects.apartment_floor') }}</label>
                                <input id="ov-apartment-floor" class="crm-input" type="text" autocomplete="off">
                            </div>
                            <div>
                                <label class="crm-label" for="ov-apartment-entrance">{{ __('objects.apartment_entrance') }}</label>
                                <input id="ov-apartment-entrance" class="crm-input" type="text" autocomplete="off">
                            </div>
                            <div>
                                <label class="crm-label" for="ov-apartment">{{ __('objects.apartment_number') }}</label>
                                <input id="ov-apartment" class="crm-input" type="text" autocomplete="off">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="crm-label" for="ov-area">{{ __('objects.area') }} ({{ __('objects.area_m2') }})</label>
                            <input id="ov-area" class="crm-input" type="number" step="0.01" min="0">
                        </div>
                        <div class="mb-1">
                            <div class="crm-label">{{ __('objects.map_point') }}</div>
                            <p class="text-xs text-[var(--crm-muted)] mb-2">{{ __('objects.map_hint') }}</p>
                            <div id="ov-map" class="project-address-map"></div>
                            <input type="hidden" id="ov-latitude">
                            <input type="hidden" id="ov-longitude">
                        </div>
                    </div>

                    <div class="crm-section">
                        <div class="crm-section-title">{{ __('projects.section_extra') }}</div>
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="crm-label mb-0">{{ __('projects.links') }}</label>
                                <button type="button" id="ov-add-link" class="crm-btn crm-btn-ghost crm-btn-sm">+ {{ __('projects.add_link') }}</button>
                            </div>
                            <div id="ov-links"></div>
                        </div>
                        <div class="mb-3">
                            <label class="crm-label" for="ov-files">{{ __('projects.files') }}</label>
                            <input type="file" id="ov-files" class="crm-input" multiple>
                            <div id="ov-files-list" class="mt-2 space-y-1 text-xs"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="ov-comment">{{ __('projects.comment') }}</label>
                            <textarea id="ov-comment" rows="3" class="crm-input" placeholder="{{ __('projects.comment_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>

                <div data-panel="supplies" class="ov-panel hidden">
                    <div class="crm-supply-toolbar">
                        <div class="crm-supply-toolbar-left">
                            <button type="button" id="ov-add-supply" class="crm-btn crm-btn-primary crm-btn-sm">+ {{ __('projects.add_supplier_order') }}</button>
                            <div class="crm-view-switch" role="group">
                                <button type="button" class="crm-btn crm-btn-sm crm-supply-view-btn active" data-sview="kanban" aria-pressed="true">{{ __('projects.supply_view_kanban') }}</button>
                                <button type="button" class="crm-btn crm-btn-sm crm-supply-view-btn" data-sview="list" aria-pressed="false">{{ __('projects.supply_view_list') }}</button>
                            </div>
                            <span id="ov-supplies-count" class="sr-only" aria-hidden="true">0</span>
                        </div>
                        <div class="crm-supply-toolbar-right">
                            <input type="search" id="ov-supply-search" class="crm-input crm-toolbar-search" placeholder="{{ __('projects.supply_search') }}" autocomplete="off">
                            <select id="ov-supply-status-filter" class="crm-input" style="width:auto;min-width:9rem">
                                <option value="">{{ __('projects.supply_filter_status') }}</option>
                            </select>
                        </div>
                    </div>
                    <div id="ov-supplies-kanban" class="crm-supply-board"></div>
                    <div id="ov-supplies-list" class="crm-supply-list is-hidden"></div>
                </div>

                <div data-panel="checklists" class="ov-panel hidden">
                    <div class="crm-checklist-toolbar">
                        <div class="crm-checklist-toolbar-left">
                            <h3 class="text-sm font-semibold">{{ __('projects.tab_checklists') }}</h3>
                            <span id="ov-checklists-count" class="crm-board-col-count">0</span>
                            <button type="button" id="ov-add-checklist" class="crm-btn crm-btn-primary crm-btn-sm">+ {{ __('projects.create_checklist') }}</button>
                        </div>
                        <div class="crm-checklist-toolbar-right">
                            <input type="search" id="ov-checklist-search" class="crm-input crm-toolbar-search" placeholder="{{ __('projects.checklist_search') }}" autocomplete="off">
                            <select id="ov-checklist-stage-filter" class="crm-input" style="width:auto;min-width:8.5rem">
                                <option value="">{{ __('projects.checklist_filter_stage') }}</option>
                            </select>
                            <select id="ov-checklist-state-filter" class="crm-input" style="width:auto;min-width:8.5rem">
                                <option value="">{{ __('projects.checklist_filter_state') }}</option>
                            </select>
                        </div>
                    </div>
                    <div id="ov-checklists-list" class="crm-checklist-list"></div>
                </div>

                <div data-panel="wazzup" class="ov-panel ov-panel--wazzup hidden">
                    @include('designer.projects.partials.wazzup-chat')
                </div>
            </div>

            <aside class="crm-modal-feed" id="ov-feed-panel">
                <div class="flex items-center justify-between mb-2 gap-2">
                    <h3 class="text-sm font-semibold">{{ __('projects.activity_feed') }}</h3>
                    <div class="flex gap-1">
                        <button type="button" class="crm-btn crm-btn-sm crm-feed-filter active" data-feed="all">{{ __('projects.feed_all') }}</button>
                        <button type="button" class="crm-btn crm-btn-sm crm-feed-filter" data-feed="comments">{{ __('projects.feed_comments') }}</button>
                        <button type="button" class="crm-btn crm-btn-sm crm-feed-filter" data-feed="changes">{{ __('projects.feed_changes') }}</button>
                    </div>
                </div>
                <form id="ov-comment-form" class="mb-3 flex gap-2">
                    <input id="ov-comment-input" class="crm-input" placeholder="{{ __('projects.comment_placeholder') }}" autocomplete="off">
                    <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm" aria-label="{{ __('projects.send_comment') }}" title="{{ __('projects.send_comment') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
                <div id="ov-feed" class="crm-timeline"></div>
            </aside>
        </div>

        <div class="crm-modal-footer" id="ov-project-footer">
            <button type="button" id="ov-delete" class="crm-btn crm-btn-secondary hidden" style="color:var(--crm-danger)">{{ __('projects.delete') }}</button>
            <button type="button" id="ov-save" class="crm-btn crm-btn-primary ml-auto">{{ __('projects.save') }}</button>
            <button type="button" id="ov-cancel" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
        </div>
    </div>
</div>

<div id="unsaved-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('projects.unsaved_leave_title') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('projects.unsaved_leave_body') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('projects.continue_editing') }}</button>
            <button type="button" id="unsaved-leave" class="crm-btn crm-btn-primary">{{ __('projects.leave_without_saving') }}</button>
        </div>
    </div>
</div>

<div id="project-limit-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-6 w-[min(440px,92vw)] relative z-10 text-center">
        <div class="mx-auto mb-3 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <h3 class="font-semibold text-lg mb-1">{{ __('subscription.project_limit_modal_title') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-1" id="project-limit-text"></p>
        <p class="text-sm text-[var(--crm-muted)] mb-5">{{ __('subscription.project_limit_modal_body', ['limit' => $projectLimit['limit'] ?? 0, 'current' => $projectLimit['current'] ?? 0]) }}</p>
        <div class="flex gap-2 justify-center">
            <button type="button" id="project-limit-close" class="crm-btn crm-btn-secondary">{{ __('projects.close') }}</button>
            <a href="{{ route('subscription.index') }}" class="crm-btn crm-btn-primary">{{ __('subscription.project_limit_modal_cta') }}</a>
        </div>
    </div>
</div>

@include('designer.projects.partials.pipeline-settings-modals')

@include('designer.projects.partials.supply-modals')
@include('designer.projects.partials.checklist-modals')

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const projects = @json($projectsData ?? []);
    const clients = @json($clientsData ?? []);
    const pipeline = @json($pipeline ?? ['stages' => []]);
    const supplyPipeline = @json($supplyPipeline ?? ['stages' => []]);
    const citiesList = @json($cities ?? []);
    const canManage = @json($canManage);
    // One-shot payload from QR scan → project picker (session pulled so it is not reused).
    const qrScanProduct = @json(session()->pull('qr_scan_product'));
    const qrSupplyPrefill = qrScanProduct ? {
        supplier_id: qrScanProduct.supplier_id,
        product_items: [{
            product_id: qrScanProduct.id,
            name: qrScanProduct.name,
            qty: 1,
            price: qrScanProduct.price,
            unit: qrScanProduct.unit || '',
        }],
    } : null;
    const routes = {
        store: @json(route('projects.store')),
        update: (id) => @json(url('/projects')) + '/' + id,
        destroy: (id) => @json(url('/projects')) + '/' + id,
        status: (id) => @json(url('/projects')) + '/' + id + '/status',
        show: (id) => @json(url('/projects')) + '/' + id,
        activity: (id) => @json(url('/projects')) + '/' + id + '/activity',
        comment: (id) => @json(url('/projects')) + '/' + id + '/comments',
        pipelineStage: @json(route('pipelines.stages.store')),
        pipelineUpdate: (id) => @json(url('/pipelines/stages')) + '/' + id,
        pipelineDelete: (id) => @json(url('/pipelines/stages')) + '/' + id,
        pipelineSync: @json(route('pipelines.sync')),
        supplyIndex: @json(route('supplier-orders.index')),
        clientsIndex: @json(route('clients.index')),
    };

    // Portal to body so sidebar/overflow cannot affect positioning
    const modalRoot = document.getElementById('project-modal-root');
    const unsaved = document.getElementById('unsaved-modal');
    const limitModal = document.getElementById('project-limit-modal');
    document.body.appendChild(modalRoot);
    document.body.appendChild(unsaved);
    document.body.appendChild(limitModal);

    const workspaceEl = document.getElementById('crm-workspace');
    const projectLimit = {
        limit: workspaceEl.dataset.projectLimit === '' ? null : Number(workspaceEl.dataset.projectLimit),
        current: Number(workspaceEl.dataset.projectCurrent || 0),
        canCreate: workspaceEl.dataset.projectCanCreate === '1',
    };

    function openLimitModal(message) {
        const textEl = document.getElementById('project-limit-text');
        textEl.textContent = message || '';
        textEl.classList.toggle('hidden', !message);
        limitModal.classList.add('open');
        limitModal.setAttribute('aria-hidden', 'false');
    }
    function closeLimitModal() {
        limitModal.classList.remove('open');
        limitModal.setAttribute('aria-hidden', 'true');
    }
    document.getElementById('project-limit-close').addEventListener('click', closeLimitModal);
    limitModal.addEventListener('click', (e) => { if (e.target === limitModal) closeLimitModal(); });

    const i18n = {
        noProjectsYet: @json(__('projects.no_projects_yet')),
        notSpecified: @json(__('projects.not_specified')),
        metaClient: @json(__('projects.meta_client')),
        metaResponsible: @json(__('projects.meta_responsible')),
        metaDeadline: @json(__('projects.meta_deadline')),
        metaBudget: @json(__('projects.meta_budget')),
        metaChecklists: @json(__('projects.meta_checklists')),
        overdue: @json(__('projects.overdue_tooltip')),
        dueToday: @json(__('projects.due_today_tooltip')),
        delayedSupply: @json(__('projects.delayed_supply_tooltip')),
        checklistsDone: @json(__('projects.checklists_done_tooltip')),
        currency: @json(__('projects.currency_symbol')),
        open: @json(__('projects.open_project')),
        delete: @json(__('projects.delete')),
        deleteConfirm: @json(__('projects.delete_confirm')),
        deleted: @json(__('projects.deleted')),
        deleteError: @json(__('projects.delete_error_generic')),
    };
    const locale = document.getElementById('crm-workspace')?.dataset.locale || 'ru-RU';
    const VIEW_KEY = 'crm.projects.view';
    const SEARCH_KEY = 'crm.projects.search';
    const OVERDUE_KEY = 'crm.projects.filterOverdue';

    function readStoredView() {
        const params = new URLSearchParams(window.location.search);
        const fromQuery = params.get('view');
        if (fromQuery === 'kanban' || fromQuery === 'list') return fromQuery;
        const fromStore = localStorage.getItem(VIEW_KEY);
        if (fromStore === 'kanban' || fromStore === 'list') return fromStore;
        return 'kanban';
    }

    let state = {
        projects: [...projects], pipeline, view: readStoredView(),
        search: localStorage.getItem(SEARCH_KEY) || '',
        filterOverdue: localStorage.getItem(OVERDUE_KEY) === '1',
        sortKey: 'name', sortDir: 'asc',
        dirty: false, snapshot: null, currentId: null, feedFilter: 'all',
        existingFiles: [], tab: 'general', kanbanScroll: 0,
        pendingQrProduct: null,
    };
    const toast = (msg, type='success') => {
        // Silent on routine success — only surface errors/warnings.
        if (type === 'success') return;
        if (typeof window.projectAlert === 'function') {
            window.projectAlert(type === 'error' ? 'error' : type, msg);
            return;
        }
        if (window.showAppToast) window.showAppToast(msg, type);
    };
    const money = (n) => {
        if (n === null || n === undefined || n === '' || Number.isNaN(Number(n))) return i18n.notSpecified;
        return Number(n).toLocaleString(locale, { maximumFractionDigits: 0 }) + ' ' + i18n.currency;
    };
    const escapeHtml = (s) => String(s??'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const formatDate = (iso) => {
        if (!iso) return i18n.notSpecified;
        const d = new Date(iso + (String(iso).length <= 10 ? 'T00:00:00' : ''));
        if (Number.isNaN(d.getTime())) return i18n.notSpecified;
        return d.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
    };
    const debounce = (fn, ms = 350) => {
        let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    const kanbanEl = document.getElementById('crm-kanban');
    const listEl = document.getElementById('crm-list');
    const listBody = document.getElementById('crm-list-body');
    const workEl = document.getElementById('ov-work');
    const searchEl = document.getElementById('crm-search');
    const overdueEl = document.getElementById('crm-filter-overdue');
    if (searchEl) searchEl.value = state.search;
    if (overdueEl) overdueEl.checked = state.filterOverdue;

    function stageName(key) {
        return (state.pipeline.stages || []).find(s => s.system_key === key)?.name || key;
    }
    function stageColor(key) {
        return (state.pipeline.stages || []).find(s => s.system_key === key)?.color || '#64748b';
    }
    function progress(p) {
        if (p.checklist_progress) return p.checklist_progress;
        const stages = p.stages || [];
        let total = 0, done = 0;
        stages.forEach(s => (s.steps||[]).forEach(st => { total++; if (st.result_status === 'done') done++; }));
        return { total, done, percent: total ? Math.round(done/total*100) : 0 };
    }
    function isOverdue(p) {
        if (!p.planned_end_date || p.actual_end_date) return false;
        return p.planned_end_date < new Date().toISOString().slice(0,10);
    }
    function isDueToday(p) {
        if (!p.planned_end_date || p.actual_end_date) return false;
        return p.planned_end_date === new Date().toISOString().slice(0,10);
    }
    function hasDelayedSupply(p) {
        if (typeof p.has_delayed_supply === 'boolean') return p.has_delayed_supply;
        const today = new Date().toISOString().slice(0,10);
        return (p.supplier_orders || []).some(o =>
            o.date_planned && o.date_planned < today && o.status !== 'delivery_completed'
        );
    }
    function filtered() {
        const q = state.search.trim().toLowerCase();
        return state.projects.filter(p => {
            if (state.filterOverdue && !isOverdue(p)) return false;
            if (!q) return true;
            return String(p.name||'').toLowerCase().includes(q)
                || String(p.client_name||'').toLowerCase().includes(q)
                || String(p.owner_name||p.responsible_name||'').toLowerCase().includes(q)
                || String(p.object_address||p.city||p.object_city||'').toLowerCase().includes(q);
        });
    }
    function sorted(items) {
        const key = state.sortKey;
        const dir = state.sortDir === 'desc' ? -1 : 1;
        return [...items].sort((a, b) => {
            let av, bv;
            if (key === 'budget') {
                av = Number(a.repair_budget_planned ?? a.planned_cost ?? 0);
                bv = Number(b.repair_budget_planned ?? b.planned_cost ?? 0);
            } else if (key === 'progress') {
                av = progress(a).percent; bv = progress(b).percent;
            } else if (key === 'status') {
                av = stageName(a.status); bv = stageName(b.status);
            } else {
                av = a[key] ?? ''; bv = b[key] ?? '';
            }
            if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
            return String(av).localeCompare(String(bv), locale, { sensitivity: 'base' }) * dir;
        });
    }
    function syncTitleDisplay() {
        const name = document.getElementById('ov-name').value.trim();
        const el = document.getElementById('ov-title-display');
        el.textContent = name || @json(__('projects.new_project'));
        el.classList.toggle('text-[var(--crm-muted)]', !name);
        el.classList.toggle('text-[var(--crm-fg)]', !!name);
    }
    function setView(view, { persist = true } = {}) {
        state.view = view === 'list' ? 'list' : 'kanban';
        document.querySelectorAll('.crm-view-btn').forEach(btn => {
            const on = btn.dataset.view === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        kanbanEl.classList.toggle('is-hidden', state.view !== 'kanban');
        listEl.classList.toggle('is-hidden', state.view !== 'list');
        if (persist) {
            localStorage.setItem(VIEW_KEY, state.view);
            const url = new URL(window.location.href);
            url.searchParams.set('view', state.view);
            window.history.replaceState({}, '', url);
        }
        render();
    }

    function renderKanban() {
        const scrollLeft = kanbanEl.scrollLeft;
        const items = filtered();
        kanbanEl.innerHTML = '';
        (state.pipeline.stages || []).forEach(stage => {
            const col = document.createElement('div');
            col.className = 'crm-board-col';
            col.style.setProperty('--col-color', stage.color || '#f59e0b');
            const cards = items.filter(p => p.status === stage.system_key);
            col.innerHTML = `
                <div class="crm-board-col-head">
                    <span class="crm-board-col-title" title="${escapeHtml(stage.name)}">${escapeHtml(stage.name)}</span>
                    <span class="crm-board-col-count" data-count="${stage.system_key}">${cards.length}</span>
                </div>
                <div class="crm-board-col-body" data-drop="${stage.system_key}"></div>`;
            const body = col.querySelector('[data-drop]');
            const highlight = (on) => col.classList.toggle('is-drop-target', on);
            body.addEventListener('dragover', e => { e.preventDefault(); highlight(true); });
            body.addEventListener('dragleave', (e) => {
                if (!body.contains(e.relatedTarget)) highlight(false);
            });
            col.addEventListener('dragover', e => { e.preventDefault(); highlight(true); });
            col.addEventListener('dragleave', (e) => {
                if (!col.contains(e.relatedTarget)) highlight(false);
            });
            const onDrop = async (e) => {
                e.preventDefault();
                highlight(false);
                await moveProject(Number(e.dataTransfer.getData('text/plain')), stage.system_key);
            };
            body.addEventListener('drop', onDrop);
            col.addEventListener('drop', onDrop);
            if (!cards.length) {
                body.innerHTML = `<div class="crm-board-empty">${i18n.noProjectsYet}</div>`;
            } else {
                cards.forEach(p => body.appendChild(cardEl(p)));
            }
            kanbanEl.appendChild(col);
        });
        kanbanEl.scrollLeft = state.kanbanScroll || scrollLeft;
    }

    function cardEl(p) {
        const pr = progress(p);
        const overdue = isOverdue(p);
        const dueToday = isDueToday(p);
        const delayed = hasDelayedSupply(p);
        const checklistsDone = pr.total > 0 && pr.done === pr.total;
        const owner = p.owner_name || p.responsible_name || i18n.notSpecified;
        const client = p.client_name || i18n.notSpecified;
        const deadlineClass = overdue ? 'is-danger' : (dueToday ? 'is-warn' : '');
        const el = document.createElement('div');
        el.className = 'crm-board-card' + (overdue ? ' is-overdue' : '');
        el.draggable = true;
        el.innerHTML = `
            <div class="crm-board-card-title" title="${escapeHtml(p.name||'')}">${escapeHtml(p.name||'')}</div>
            <div class="crm-board-card-meta">
                <div>${escapeHtml(i18n.metaClient)}: <strong>${escapeHtml(client)}</strong></div>
                <div>${escapeHtml(i18n.metaResponsible)}: <strong>${escapeHtml(owner)}</strong></div>
                <div class="${deadlineClass}" title="${overdue ? escapeHtml(i18n.overdue) : (dueToday ? escapeHtml(i18n.dueToday) : '')}">
                    ${escapeHtml(i18n.metaDeadline)}: <strong>${escapeHtml(formatDate(p.planned_end_date))}</strong>
                </div>
                <div>${escapeHtml(i18n.metaBudget)}: <strong>${escapeHtml(money(p.repair_budget_planned ?? p.planned_cost))}</strong></div>
            </div>
            <div class="crm-board-card-foot">
                <div class="crm-board-card-progress" title="${escapeHtml(i18n.metaChecklists)}: ${pr.done}/${pr.total}">
                    <span style="width:${pr.percent}%"></span>
                </div>
                <span class="crm-board-card-progress-label">${pr.done}/${pr.total}</span>
                <div class="crm-board-card-flags">
                    ${overdue ? `<span class="crm-flag is-danger" title="${escapeHtml(i18n.overdue)}"></span>` : ''}
                    ${dueToday && !overdue ? `<span class="crm-flag is-warn" title="${escapeHtml(i18n.dueToday)}"></span>` : ''}
                    ${delayed ? `<span class="crm-flag is-warn" title="${escapeHtml(i18n.delayedSupply)}"></span>` : ''}
                    ${checklistsDone ? `<span class="crm-flag is-ok" title="${escapeHtml(i18n.checklistsDone)}"></span>` : ''}
                </div>
            </div>`;
        el.addEventListener('dragstart', e => {
            el.classList.add('is-dragging');
            state.kanbanScroll = kanbanEl.scrollLeft;
            e.dataTransfer.setData('text/plain', String(p.id));
            e.dataTransfer.effectAllowed = 'move';
        });
        el.addEventListener('dragend', () => el.classList.remove('is-dragging'));
        el.addEventListener('click', () => openProject(p.id));
        return el;
    }

    function renderList() {
        const L = {
            name: @json(__('projects.name')),
            client: @json(__('projects.client')),
            responsible: @json(__('projects.responsible')),
            status: @json(__('projects.status')),
            deadline: @json(__('projects.deadline')),
            budget: @json(__('projects.budget_plan')),
            progress: @json(__('projects.progress')),
        };
        const items = sorted(filtered());
        listBody.innerHTML = items.map(p => {
            const pr = progress(p);
            const owner = p.owner_name || p.responsible_name || i18n.notSpecified;
            return `<tr data-id="${p.id}">
                <td data-label="${escapeHtml(L.name)}">${escapeHtml(p.name||'')}</td>
                <td data-label="${escapeHtml(L.client)}">${escapeHtml(p.client_name || i18n.notSpecified)}</td>
                <td class="hidden md:table-cell" data-label="${escapeHtml(L.responsible)}">${escapeHtml(owner)}</td>
                <td data-label="${escapeHtml(L.status)}"><span class="crm-status-badge" style="box-shadow: inset 3px 0 0 ${escapeHtml(stageColor(p.status))}">${escapeHtml(stageName(p.status))}</span></td>
                <td data-label="${escapeHtml(L.deadline)}" class="${isOverdue(p)?'text-[var(--crm-danger)]':''}">${escapeHtml(formatDate(p.planned_end_date))}</td>
                <td data-label="${escapeHtml(L.budget)}">${escapeHtml(money(p.repair_budget_planned ?? p.planned_cost))}</td>
                <td class="hidden lg:table-cell" data-label="${escapeHtml(L.progress)}">
                    <div class="crm-mini-progress" title="${pr.done}/${pr.total}">
                        <div class="crm-mini-progress-bar"><span style="width:${pr.percent}%"></span></div>
                        <span>${pr.done}/${pr.total}</span>
                    </div>
                </td>
                <td data-label="">
                    <div class="flex flex-wrap gap-1 justify-end">
                        <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-open="${p.id}">${escapeHtml(i18n.open)}</button>
                        <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-delete="${p.id}" style="color:var(--crm-danger)">${escapeHtml(i18n.delete)}</button>
                    </div>
                </td>
            </tr>`;
        }).join('') || `<tr><td colspan="8" class="px-3 py-6 text-center text-[var(--crm-muted)]">${escapeHtml(i18n.noProjectsYet)}</td></tr>`;
        listBody.querySelectorAll('tr[data-id]').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                openProject(Number(tr.dataset.id));
            });
        });
        listBody.querySelectorAll('[data-open]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openProject(Number(btn.dataset.open));
            });
        });
        listBody.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                deleteProject(Number(btn.dataset.delete));
            });
        });
        document.querySelectorAll('.crm-table th[data-sort]').forEach(th => {
            th.classList.toggle('is-asc', th.dataset.sort === state.sortKey && state.sortDir === 'asc');
            th.classList.toggle('is-desc', th.dataset.sort === state.sortKey && state.sortDir === 'desc');
        });
    }

    function render() {
        if (state.view === 'kanban') {
            listBody.innerHTML = '';
            renderKanban();
        } else {
            kanbanEl.innerHTML = '';
            renderList();
        }
    }

    async function moveProject(id, status) {
        const p = state.projects.find(x => x.id === id);
        if (!p || p.status === status) return;
        const prev = p.status;
        p.status = status; render();
        try {
            const res = await fetch(routes.status(id), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ status })
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'error');
            if (data.project) Object.assign(p, data.project);
        } catch (e) {
            p.status = prev; render(); toast(e.message || 'Error', 'error');
        }
    }

    function fillClients(selected) {
        const sel = document.getElementById('ov-client');
        sel.innerHTML = `<option value="">${@json(__('projects.select_client'))}</option>` +
            clients.map(c => `<option value="${c.id}" ${String(c.id)===String(selected)?'selected':''}>${escapeHtml(c.name)}</option>`).join('');
    }
    function fillStatus(selected) {
        const first = (state.pipeline.stages||[])[0]?.system_key || '';
        document.getElementById('ov-status').value = selected || first;
    }
    function renderStageBar(active) {
        const bar = document.getElementById('ov-stage-bar');
        bar.innerHTML = (state.pipeline.stages||[]).map(s =>
            `<button type="button" class="crm-stage-chip ${s.system_key===active?'is-active':''}" data-key="${s.system_key}" title="${escapeHtml(s.name)}">${escapeHtml(s.name)}</button>`
        ).join('');
        bar.querySelectorAll('button').forEach(btn => btn.addEventListener('click', () => {
            fillStatus(btn.dataset.key);
            renderStageBar(btn.dataset.key);
            markDirty();
        }));
    }

    function renderLinks(links) {
        const box = document.getElementById('ov-links');
        const rows = (links && links.length) ? links : [{ title: '', url: '' }];
        box.innerHTML = rows.map((l, i) => `
            <div class="crm-link-row" data-i="${i}">
                <input class="crm-input" data-k="title" placeholder="${@json(__('projects.link_title'))}" value="${escapeHtml(l.title||'')}">
                <input class="crm-input" data-k="url" placeholder="https://" value="${escapeHtml(l.url||'')}">
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-del>×</button>
            </div>`).join('');
        box.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => {
            btn.closest('.crm-link-row').remove();
            if (!box.children.length) renderLinks([]);
            markDirty();
        }));
        box.querySelectorAll('input').forEach(inp => inp.addEventListener('input', markDirty));
    }
    function readLinks() {
        return [...document.querySelectorAll('#ov-links .crm-link-row')].map(row => ({
            title: row.querySelector('[data-k="title"]').value.trim(),
            url: row.querySelector('[data-k="url"]').value.trim(),
        })).filter(l => l.url);
    }
    function renderFiles(items) {
        const box = document.getElementById('ov-files-list');
        state.existingFiles = (items || []).map(f => typeof f === 'string' ? f : f.path).filter(Boolean);
        const list = items || [];
        if (!list.length) { box.innerHTML = ''; return; }
        box.innerHTML = list.map((f, i) => {
            const name = typeof f === 'string' ? f.split('/').pop() : (f.name || f.path);
            const url = typeof f === 'string' ? (@json(asset('storage')) + '/' + f) : f.url;
            const path = typeof f === 'string' ? f : f.path;
            return `<div class="flex items-center justify-between gap-2" data-path="${escapeHtml(path)}">
                <a class="text-[var(--crm-accent)] truncate" href="${escapeHtml(url)}" target="_blank">${escapeHtml(name)}</a>
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-rm-file>×</button>
            </div>`;
        }).join('');
        box.querySelectorAll('[data-rm-file]').forEach(btn => btn.addEventListener('click', () => {
            const path = btn.closest('[data-path]').dataset.path;
            state.existingFiles = state.existingFiles.filter(p => p !== path);
            btn.closest('[data-path]').remove();
            markDirty();
        }));
    }

    function readForm() {
        return {
            name: document.getElementById('ov-name').value.trim(),
            client_id: document.getElementById('ov-client').value || null,
            status: document.getElementById('ov-status').value || (state.pipeline.stages||[])[0]?.system_key || '',
            start_date: document.getElementById('ov-start').value || null,
            planned_end_date: document.getElementById('ov-planned-end').value || null,
            actual_end_date: document.getElementById('ov-actual-end').value || null,
            repair_budget_planned: document.getElementById('ov-budget-plan').value || null,
            repair_budget_actual: document.getElementById('ov-budget-fact').value || null,
            comment: document.getElementById('ov-comment').value || null,
            links: readLinks(),
            existing_files: [...state.existingFiles],
            ...(window.CrmProjectAddress?.read?.() || {}),
        };
    }

    function applyForm(p) {
        document.getElementById('ov-name').value = p?.name || '';
        syncTitleDisplay();
        fillClients(p?.client_id || '');
        fillStatus(p?.status || (state.pipeline.stages||[])[0]?.system_key);
        renderStageBar(document.getElementById('ov-status').value);
        document.getElementById('ov-start').value = p?.start_date || '';
        document.getElementById('ov-planned-end').value = p?.planned_end_date || '';
        document.getElementById('ov-actual-end').value = p?.actual_end_date || '';
        document.getElementById('ov-budget-plan').value = p?.repair_budget_planned ?? p?.planned_cost ?? '';
        document.getElementById('ov-budget-fact').value = p?.repair_budget_actual ?? p?.actual_cost ?? '';
        document.getElementById('ov-comment').value = p?.comment || '';
        renderLinks(p?.links || []);
        renderFiles(p?.file_items || p?.files || []);
        window.CrmProjectAddress?.apply?.(p || {});
        clearFieldErrors();
    }

    function openModal() {
        modalRoot.classList.add('open');
        modalRoot.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        window.CrmProjectAddress?.onModalOpened?.();
    }
    function closeModal(force=false) {
        if (state.dirty && !force) {
            unsaved.classList.add('open');
            unsaved.setAttribute('aria-hidden', 'false');
            return;
        }
        unsaved.classList.remove('open');
        modalRoot.classList.remove('open');
        modalRoot.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        state.dirty = false;
        state.currentId = null;
    }

    function setDeleteVisible(visible) {
        document.getElementById('ov-delete')?.classList.toggle('hidden', !visible);
    }

    function openCreate(opts = {}) {
        state.currentId = null;
        state.dirty = false;
        applyForm({ status: (state.pipeline.stages||[])[0]?.system_key });
        if (opts.clientId) {
            fillClients(String(opts.clientId));
            const sel = document.getElementById('ov-client');
            if (sel) sel.value = String(opts.clientId);
        }
        state.snapshot = readForm();
        const cl = document.getElementById('ov-checklists-list');
        if (cl) cl.innerHTML = `<div class="crm-empty-inline">${@json(__('projects.checklists_after_save'))}</div>`;
        document.getElementById('ov-feed').innerHTML = `<div class="crm-empty-inline">${@json(__('projects.feed_empty'))}</div>`;
        window.CrmSupplies?.onProjectOpened?.(null);
        window.CrmChecklists?.onProjectOpened?.(null);
        setDeleteVisible(false);
        switchTab('general');
        openModal();
    }

    async function openProject(id, opts = {}) {
        state.kanbanScroll = kanbanEl.scrollLeft;
        let p = state.projects.find(x => x.id === id);
        try {
            const res = await fetch(routes.show(id), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data?.id) { p = data; const i = state.projects.findIndex(x => x.id === id); if (i>=0) state.projects[i]=p; }
        } catch (_) {}
        if (!p) return;
        state.currentId = id;
        state.dirty = false;
        applyForm(p);
        state.snapshot = readForm();
        renderChecklists(p);
        renderSupplies(p);
        window.CrmSupplies?.onProjectOpened?.(p);
        window.CrmChecklists?.onProjectOpened?.(p);
        loadFeed(id);
        setDeleteVisible(true);
        switchTab(opts.tab || 'general');
        openModal();
        if (opts.createSupply) {
            setTimeout(() => window.CrmSupplies?.openCreate?.(opts.supplyPrefill || null), 50);
        }
    }

    async function deleteProject(id) {
        const projectId = Number(id || state.currentId);
        if (!projectId) return;
        if (!window.confirm(i18n.deleteConfirm)) return;
        try {
            const res = await fetch(routes.destroy(projectId), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                throw new Error(data.message || i18n.deleteError);
            }
            state.projects = state.projects.filter((p) => Number(p.id) !== projectId);
            state.dirty = false;
            closeModal(true);
            render();
            toast(data.message || i18n.deleted, 'success');
        } catch (e) {
            toast(e.message || i18n.deleteError, 'error');
        }
    }

    function renderChecklists(p) {
        if (window.CrmChecklists?.renderChecklists) {
            window.CrmChecklists.renderChecklists(p);
            return;
        }
        const wrap = document.getElementById('ov-checklists-list');
        if (wrap) wrap.innerHTML = `<div class="crm-empty-inline">${@json(__('projects.checklists_empty_title'))}</div>`;
    }

    function renderSupplies(p) {
        if (window.CrmSupplies?.renderSupplies) {
            window.CrmSupplies.renderSupplies(p);
            return;
        }
        const wrap = document.getElementById('ov-supplies-kanban');
        if (wrap) wrap.innerHTML = `<div class="crm-empty-inline">${@json(__('projects.supplies_empty_title'))}</div>`;
    }

    async function loadFeed(id) {
        const feed = document.getElementById('ov-feed');
        feed.innerHTML = '<div class="crm-skeleton h-14"></div>';
        try {
            const res = await fetch(routes.activity(id) + '?filter=' + state.feedFilter, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const items = data.data || [];
            if (!items.length) {
                feed.innerHTML = `<div class="crm-empty-inline">${@json(__('projects.feed_empty'))}</div>`;
                return;
            }
            feed.innerHTML = items.map(ev => `
                <div class="crm-timeline-item ${ev.event_type==='comment'?'':'is-system'}">
                    <div class="flex justify-between gap-2 text-xs text-[var(--crm-muted)] mb-1">
                        <span>${escapeHtml(ev.actor?.name || '')}</span>
                        <span>${escapeHtml(ev.created_at || '')}</span>
                    </div>
                    <div>${escapeHtml(ev.body || ev.event_type)}</div>
                </div>`).join('');
        } catch {
            feed.innerHTML = `<div class="crm-empty-inline">${@json(__('projects.feed_empty'))}</div>`;
        }
    }

    function markDirty() { state.dirty = true; }
    function clearFieldErrors() {
        document.querySelectorAll('[data-error]').forEach(el => { el.textContent=''; el.classList.add('hidden'); });
    }
    function showFieldErrors(errors) {
        clearFieldErrors();
        Object.entries(errors || {}).forEach(([key, msgs]) => {
            const el = document.querySelector(`[data-error="${key}"]`);
            if (el) { el.textContent = Array.isArray(msgs) ? msgs[0] : msgs; el.classList.remove('hidden'); }
        });
    }

    async function saveProject() {
        const payload = readForm();
        if (!payload.name || !payload.status) {
            toast(@json(__('projects.validation_required')), 'error');
            return;
        }
        const fd = new FormData();
        Object.entries(payload).forEach(([k, v]) => {
            if (k === 'links') {
                payload.links.forEach((l, i) => {
                    fd.append(`links[${i}][title]`, l.title || '');
                    fd.append(`links[${i}][url]`, l.url || '');
                });
                return;
            }
            if (k === 'existing_files') {
                payload.existing_files.forEach((f, i) => fd.append(`existing_files[${i}]`, f));
                return;
            }
            if (v !== null && v !== undefined) fd.append(k, v);
        });
        const fileInput = document.getElementById('ov-files');
        [...(fileInput.files || [])].forEach(f => fd.append('files[]', f));
        fd.append('_token', csrf);
        let url = routes.store;
        if (state.currentId) {
            url = routes.update(state.currentId);
            fd.append('_method', 'PUT');
        }
        try {
            const res = await fetch(url, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body: fd });
            const data = await res.json();
            if (!res.ok || !data.success) {
                if (data.code === 'PROJECT_LIMIT_REACHED' || data.error === 'PROJECT_LIMIT_REACHED') {
                    closeModal(true);
                    projectLimit.canCreate = false;
                    openLimitModal(data.message);
                    return;
                }
                if (data.errors) showFieldErrors(data.errors);
                throw new Error(data.message || Object.values(data.errors||{}).flat()[0] || 'Error');
            }
            const p = data.project;
            const idx = state.projects.findIndex(x => x.id === p.id);
            if (idx >= 0) state.projects[idx] = p; else state.projects.unshift(p);
            state.dirty = false;
            // After creating a project from QR scan — open supply with the scanned product.
            if (state.pendingQrProduct && p?.id) {
                const prefill = {
                    supplier_id: state.pendingQrProduct.supplier_id,
                    product_items: [{
                        product_id: state.pendingQrProduct.id,
                        name: state.pendingQrProduct.name,
                        qty: 1,
                        price: state.pendingQrProduct.price,
                        unit: state.pendingQrProduct.unit || '',
                    }],
                };
                state.pendingQrProduct = null;
                closeModal(true);
                render();
                kanbanEl.scrollLeft = state.kanbanScroll;
                await openProject(p.id, { tab: 'supplies', createSupply: true, supplyPrefill: prefill });
                return;
            }
            closeModal(true);
            render();
            kanbanEl.scrollLeft = state.kanbanScroll;
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    function switchTab(name) {
        state.tab = name;
        document.querySelectorAll('.crm-modal-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
        document.querySelectorAll('.ov-panel').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== name));
        workEl.classList.toggle('is-split', name === 'general');
        workEl.classList.toggle('is-wazzup', name === 'wazzup');
        document.getElementById('ov-feed-panel').classList.toggle('hidden', name !== 'general');
        const footer = document.getElementById('ov-project-footer');
        footer?.classList.toggle('is-tab-hidden', name !== 'general');
        footer?.classList.toggle('hidden', name !== 'general');
        if (name === 'checklists' && window.CrmChecklists?.onProjectOpened) {
            window.CrmChecklists.onProjectOpened(state.projects.find(x => x.id === state.currentId) || null);
        }
        if (name === 'supplies' && window.CrmSupplies?.onProjectOpened) {
            window.CrmSupplies.onProjectOpened(state.projects.find(x => x.id === state.currentId) || null);
        }
        if (name === 'wazzup' && window.CrmWazzup?.onProjectOpened) {
            window.CrmWazzup.onProjectOpened(state.projects.find(x => x.id === state.currentId) || null);
        }
        if (name === 'general') {
            window.CrmProjectAddress?.onModalOpened?.();
        }
    }

    // Events
    const applySearch = debounce(() => {
        state.search = searchEl.value;
        localStorage.setItem(SEARCH_KEY, state.search);
        render();
    }, 350);
    searchEl.addEventListener('input', applySearch);
    overdueEl?.addEventListener('change', () => {
        state.filterOverdue = !!overdueEl.checked;
        localStorage.setItem(OVERDUE_KEY, state.filterOverdue ? '1' : '0');
        render();
    });
    document.querySelectorAll('.crm-view-btn').forEach(btn => {
        btn.addEventListener('click', () => setView(btn.dataset.view));
        btn.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                setView(state.view === 'kanban' ? 'list' : 'kanban');
            }
        });
    });
    document.querySelectorAll('.crm-table th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const key = th.dataset.sort;
            if (!key) return;
            if (state.sortKey === key) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            else { state.sortKey = key; state.sortDir = 'asc'; }
            if (state.view === 'list') renderList();
        });
    });
    // Shift+wheel horizontal scroll on kanban
    kanbanEl.addEventListener('wheel', (e) => {
        if (state.view !== 'kanban') return;
        if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            if (e.shiftKey && e.deltaY) {
                e.preventDefault();
                kanbanEl.scrollLeft += e.deltaY;
            }
            state.kanbanScroll = kanbanEl.scrollLeft;
        }
    }, { passive: false });
    kanbanEl.addEventListener('scroll', () => { state.kanbanScroll = kanbanEl.scrollLeft; });

    document.getElementById('crm-create-btn').addEventListener('click', () => {
        if (!projectLimit.canCreate) {
            openLimitModal();
            return;
        }
        openCreate();
    });
    document.getElementById('ov-close').addEventListener('click', () => closeModal(false));
    modalRoot.querySelector('[data-close-backdrop]').addEventListener('click', () => closeModal(false));
    document.getElementById('ov-cancel').addEventListener('click', () => {
        if (state.snapshot) {
            applyForm({
                ...state.snapshot,
                links: state.snapshot.links,
                file_items: (state.snapshot.existing_files||[]).map(p => ({
                    path: p,
                    name: p.split('/').pop(),
                    url: (@json(asset('storage')) + '/' + p),
                })),
            });
        }
        state.dirty = false;
        closeModal(true);
    });
    document.getElementById('ov-save').addEventListener('click', saveProject);
    document.getElementById('ov-delete')?.addEventListener('click', () => deleteProject(state.currentId));
    document.getElementById('unsaved-continue').addEventListener('click', () => { unsaved.classList.remove('open'); });
    document.getElementById('unsaved-leave').addEventListener('click', () => closeModal(true));
    document.querySelectorAll('.crm-modal-tab').forEach(t => t.addEventListener('click', () => switchTab(t.dataset.tab)));
    document.getElementById('ov-add-link').addEventListener('click', () => {
        const cur = readLinks(); cur.push({ title: '', url: '' }); renderLinks(cur); markDirty();
    });
    ['ov-name','ov-client','ov-start','ov-planned-end','ov-actual-end','ov-budget-plan','ov-budget-fact','ov-comment','ov-files']
        .forEach(id => {
            const el = document.getElementById(id);
            el.addEventListener('change', markDirty);
            el.addEventListener('input', markDirty);
        });
    document.getElementById('ov-name').addEventListener('input', syncTitleDisplay);

    document.querySelectorAll('.crm-feed-filter').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.crm-feed-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active'); state.feedFilter = btn.dataset.feed;
        if (state.currentId) loadFeed(state.currentId);
    }));
    document.getElementById('ov-comment-form').addEventListener('submit', async e => {
        e.preventDefault();
        if (!state.currentId) return;
        const body = document.getElementById('ov-comment-input').value.trim();
        if (!body) return;
        const res = await fetch(routes.comment(state.currentId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ body })
        });
        if (res.ok) { document.getElementById('ov-comment-input').value = ''; loadFeed(state.currentId); }
    });

    window.CrmPipelineSettingsConfig = {
        csrf,
        routes,
        toast,
        escapeHtml,
        getProjects: () => state.projects,
        getPipeline: () => state.pipeline,
        setPipeline: (p) => { if (p) state.pipeline = p; },
        renderBoard: () => render(),
        pipelineType: 'project',
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

    const params = new URLSearchParams(location.search);
    const openId = params.get('open') || params.get('project_id');
    const wantSupplyCreate = params.get('create') === '1' && (params.get('tab') === 'supplies' || params.has('project_id'));
    const wantChecklist = params.get('checklist');
    const wantTab = params.get('tab') || (wantSupplyCreate ? 'supplies' : (wantChecklist ? 'checklists' : null));

    window.CrmProjectBridge = {
        csrf,
        getCurrentProject: () => {
            if (!state.currentId) return null;
            return state.projects.find(x => x.id === state.currentId) || null;
        },
        refreshCurrentProject: async () => {
            if (!state.currentId) return null;
            try {
                const res = await fetch(routes.show(state.currentId), { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data?.id) {
                    const i = state.projects.findIndex(x => x.id === state.currentId);
                    if (i >= 0) state.projects[i] = data;
                    renderSupplies(data);
                    renderChecklists(data);
                    return data;
                }
            } catch (_) {}
            return state.projects.find(x => x.id === state.currentId) || null;
        },
        toast,
        escapeHtml,
        money,
        formatDate,
        switchTab,
        getClients: () => clients,
    };

    setView(state.view, { persist: true });

    window.CrmProjectAddress?.init?.({
        cities: citiesList,
        onChange: markDirty,
    });

    if (openId) {
        openProject(Number(openId), {
            tab: wantTab || 'general',
            createSupply: wantSupplyCreate,
            supplyPrefill: wantSupplyCreate ? qrSupplyPrefill : null,
        }).then(() => {
            const supplyId = params.get('supply');
            if (supplyId && window.CrmSupplies?.openDetail) {
                setTimeout(() => window.CrmSupplies.openDetail(Number(supplyId)), 80);
            }
            const checklistId = params.get('checklist');
            const stepId = params.get('step') || params.get('item');
            if (checklistId && window.CrmChecklists?.openDetail) {
                setTimeout(() => {
                    window.CrmChecklists.openDetail(Number(checklistId), {
                        stepId: stepId ? Number(stepId) : null,
                    });
                }, 100);
            }
        });
    } else if (params.get('create') === '1' && !wantSupplyCreate) {
        if (qrScanProduct) state.pendingQrProduct = qrScanProduct;
        const prefClient = params.get('client_id');
        openCreate(prefClient ? { clientId: Number(prefClient) } : {});
    }
})();
</script>
@include('designer.projects.partials.supply-scripts')
@include('designer.projects.partials.checklist-scripts')
@include('designer.projects.partials.pipeline-settings-scripts')
@include('designer.projects.partials.wazzup-scripts')
@endpush
