@extends('layouts.dashboard')

@section('title', __('tasks.title'))
@section('header_title', __('tasks.title'))
@section('main_class', 'crm-main-fill')

@push('styles')
<style>
    #crm-workspace .crm-toolbar-search-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        width: min(260px, 100%);
    }
    #crm-workspace .crm-toolbar-search {
        width: 100%;
        padding-left: 2rem;
    }
    #crm-workspace .crm-filters-wrap { position: relative; }
    #crm-workspace .crm-filters-btn { position: relative; }
    #crm-workspace .crm-filters-badge {
        position: absolute;
        top: -0.35rem;
        right: -0.35rem;
        min-width: 1.1rem;
        height: 1.1rem;
        padding: 0 0.3rem;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 1.1rem;
        text-align: center;
        background: var(--crm-accent);
        color: #fff;
    }
    #crm-workspace .crm-filters-panel {
        position: absolute;
        top: calc(100% + 0.4rem);
        right: 0;
        z-index: 40;
        width: min(300px, calc(100vw - 2rem));
        background: var(--crm-surface);
        border: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
        border-radius: 10px;
        box-shadow: var(--crm-shadow);
        padding: 0.85rem;
        display: none;
    }
    #crm-workspace .crm-filters-panel.is-open { display: block; }
    #crm-workspace .crm-filters-panel .crm-label { margin-bottom: 0.3rem; }
    #crm-workspace .crm-filters-panel .crm-input { width: 100%; }
    #crm-workspace .crm-filters-backdrop {
        position: fixed;
        inset: 0;
        z-index: 35;
        display: none;
    }
    #crm-workspace .crm-filters-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 0.5rem;
    }
    #crm-workspace .tasks-filter-checks {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        margin-bottom: 0.75rem;
    }
    #crm-workspace .tasks-filter-check {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: var(--crm-text);
        cursor: pointer;
    }
    #crm-workspace .tasks-filter-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8125rem;
        color: var(--crm-muted);
        cursor: pointer;
        margin-bottom: 0.6rem;
    }
    #crm-workspace .tasks-filter-select-wrap { margin-bottom: 0.75rem; }

    /* Calendar panel */
    #crm-workspace .tasks-calendar-head {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
        background: var(--crm-surface);
        border-radius: 8px 8px 0 0;
    }
    #crm-workspace .tasks-calendar-nav { display: flex; align-items: center; gap: 0.5rem; }
    #crm-workspace .tasks-calendar-title {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--crm-text);
        margin-left: 0.35rem;
        text-transform: capitalize;
    }
    #crm-workspace .tasks-calendar-grid {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        grid-auto-rows: minmax(96px, 1fr);
        gap: 1px;
        background: color-mix(in srgb, var(--crm-border) 30%, transparent);
        border: 1px solid color-mix(in srgb, var(--crm-border) 30%, transparent);
        border-radius: 0 0 8px 8px;
    }
    #crm-workspace .tasks-cal-day-header {
        background: var(--crm-surface-2);
        padding: 0.45rem;
        text-align: center;
        font-weight: 600;
        font-size: 0.7rem;
        color: var(--crm-muted);
        grid-auto-rows: min-content;
    }
    #crm-workspace .tasks-cal-day {
        background: var(--crm-surface);
        padding: 0.35rem;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-height: 0;
        overflow: hidden;
    }
    #crm-workspace .tasks-cal-day.is-other-month { background: var(--crm-surface-2); opacity: 0.55; }
    #crm-workspace .tasks-cal-day.is-today { box-shadow: inset 0 0 0 2px var(--crm-accent); }
    #crm-workspace .tasks-cal-day-number { font-weight: 600; font-size: 0.75rem; color: var(--crm-text); }
    #crm-workspace .tasks-cal-event {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
        font-size: 0.65rem;
        line-height: 1.25;
        background: color-mix(in srgb, var(--crm-accent) 16%, transparent);
        color: var(--crm-text);
        text-decoration: none;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    #crm-workspace .tasks-cal-event.is-done { opacity: 0.6; text-decoration: line-through; }
    #crm-workspace .tasks-cal-event.is-overdue { background: color-mix(in srgb, var(--crm-danger) 16%, transparent); }
    #crm-workspace .tasks-cal-more {
        font-size: 0.65rem;
        color: var(--crm-accent);
        background: transparent;
        border: none;
        text-align: left;
        cursor: pointer;
        padding: 0;
    }
    #crm-workspace .tasks-day-drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 55;
    }
    #crm-workspace .tasks-day-drawer-overlay.hidden { display: none; }
    #crm-workspace .tasks-day-drawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100%;
        width: min(380px, 92vw);
        background: var(--crm-surface);
        border-left: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
        box-shadow: var(--crm-shadow);
        z-index: 56;
        display: flex;
        flex-direction: column;
    }
    #crm-workspace .tasks-day-drawer.hidden { display: none; }
    #crm-workspace .tasks-day-drawer-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 25%, transparent);
        flex: 0 0 auto;
    }
    #crm-workspace .tasks-day-drawer-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        padding: 0.85rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    /* Task modal */
    #task-form-modal.crm-modal-root { align-items: center; justify-content: center; padding: 1.5rem; }
    #task-form-modal .crm-modal { width: min(640px, 92vw); height: auto; max-height: min(90dvh, 92vh); display: flex; flex-direction: column; }
    #task-form-modal .task-form-body { flex: 1 1 auto; min-height: 0; overflow: auto; padding: 1rem 1.15rem; background: var(--crm-bg); }

    @media (max-width: 768px) {
        #crm-workspace .crm-toolbar { flex-wrap: wrap; }
        #crm-workspace .crm-toolbar-right { width: 100%; flex-wrap: wrap; }
        #crm-workspace .crm-toolbar-search-wrap { width: 100%; max-width: none; flex: 1 1 100%; }
        #crm-workspace .crm-filters-panel {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            top: auto;
            width: 100%;
            border-radius: 14px 14px 0 0;
            max-height: min(80vh, 560px);
            overflow: auto;
        }
        #crm-workspace .crm-filters-backdrop.is-open { display: block; }
        #task-form-modal.crm-modal-root { padding: 0; }
        #task-form-modal .crm-modal { width: 100vw; max-width: 100vw; height: 100dvh; max-height: 100dvh; border-radius: 0; border: none; }
        #task-form-modal .crm-grid-2 { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="crm-workspace" id="crm-workspace" data-locale="{{ str_replace('_', '-', app()->getLocale()) }}">
    <div class="crm-toolbar" role="toolbar" aria-label="{{ __('tasks.title') }}">
        <div class="crm-toolbar-left">
            <h1 class="text-lg font-semibold text-[var(--crm-text)] m-0">{{ __('tasks.title') }}</h1>
            <span id="tasks-active-badge" class="crm-status-badge">0</span>
        </div>
        <div class="crm-toolbar-right">
            <div class="crm-toolbar-search-wrap">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="position:absolute;left:0.7rem;pointer-events:none;color:var(--crm-muted)">
                    <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="search" id="tasks-search" class="crm-input crm-toolbar-search" placeholder="{{ __('tasks.search_placeholder') }}" autocomplete="off">
            </div>

            <div class="crm-filters-wrap" id="tasks-filters-wrap">
                <button type="button" id="tasks-filters-btn" class="crm-btn crm-btn-secondary crm-btn-sm crm-filters-btn" aria-expanded="false" aria-haspopup="true" aria-controls="tasks-filters-panel">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                    </svg>
                    <span>{{ __('tasks.filters') }}</span>
                    <span id="tasks-filters-badge" class="crm-filters-badge" hidden>0</span>
                </button>
                <div id="tasks-filters-backdrop" class="crm-filters-backdrop" hidden></div>
                <div id="tasks-filters-panel" class="crm-filters-panel" role="dialog" aria-label="{{ __('tasks.filters') }}" aria-hidden="true">
                    <label class="crm-label">{{ __('tasks.filter_status') }}</label>
                    <div id="tasks-filter-status-list" class="tasks-filter-checks">
                        @foreach ($statusColumns ?? [] as $col)
                            <label class="tasks-filter-check">
                                <input type="checkbox" class="tasks-status-check" value="{{ $col['key'] }}">
                                {{ $col['label'] }}
                            </label>
                        @endforeach
                    </div>

                    @if ($isCorporate ?? false)
                        <div class="tasks-filter-select-wrap">
                            <label class="crm-label" for="tasks-filter-assignee">{{ __('tasks.filter_assignee') }}</label>
                            <select id="tasks-filter-assignee" class="crm-input crm-select">
                                <option value="">{{ __('tasks.filter_all') }}</option>
                            </select>
                        </div>
                    @endif

                    <div class="tasks-filter-select-wrap">
                        <label class="crm-label" for="tasks-filter-project">{{ __('tasks.filter_project') }}</label>
                        <select id="tasks-filter-project" class="crm-input crm-select">
                            <option value="">{{ __('tasks.filter_all') }}</option>
                        </select>
                    </div>

                    <label class="tasks-filter-toggle">
                        <input type="checkbox" id="tasks-filter-overdue">
                        {{ __('tasks.filter_overdue') }}
                    </label>
                    <label class="tasks-filter-toggle">
                        <input type="checkbox" id="tasks-filter-noproject">
                        {{ __('tasks.filter_no_project') }}
                    </label>

                    <div class="crm-filters-actions">
                        <button type="button" id="tasks-filters-reset" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('tasks.filter_clear') }}</button>
                    </div>
                </div>
            </div>

            <div class="crm-view-switch" role="group" aria-label="{{ __('tasks.view_kanban') }}">
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="kanban" aria-pressed="true" title="{{ __('tasks.view_kanban') }}">{{ __('tasks.view_kanban') }}</button>
                <button type="button" class="crm-btn crm-btn-sm crm-view-btn" data-view="calendar" aria-pressed="false" title="{{ __('tasks.view_calendar') }}">{{ __('tasks.view_calendar') }}</button>
            </div>

            <button type="button" id="tasks-create-btn" class="crm-btn crm-btn-primary crm-btn-sm">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                {{ __('tasks.create') }}
            </button>
        </div>
    </div>

    <div class="crm-viewport">
        <div id="crm-kanban" class="crm-board" role="region" aria-label="{{ __('tasks.view_kanban') }}"></div>
        <div id="tasks-calendar-panel" class="crm-view-panel is-hidden" role="region" aria-label="{{ __('tasks.view_calendar') }}">
            @include('designer.tasks.partials.calendar-panel')
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('designer.tasks.partials.task-modal')
@include('designer.projects.partials.checklist-modals')

<script>
window.CrmProjectBridge = {
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    _project: null,
    getCurrentProject() { return this._project; },
    setCurrentProject(project) { this._project = project || null; },
    async refreshCurrentProject() {
        if (!this._project?.id) return this._project;
        try {
            const res = await fetch(@json(url('/projects')) + '/' + this._project.id, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data?.id) {
                this._project = data;
                return data;
            }
        } catch (_) {}
        return this._project;
    },
    toast(msg, type) {
        if (type === 'error' && typeof window.projectAlert === 'function') {
            window.projectAlert('error', msg, '', 3500);
        }
    },
    escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },
    formatDate(iso) {
        if (!iso) return @json(__('projects.not_specified'));
        const d = new Date(String(iso).length <= 10 ? iso + 'T00:00:00' : iso);
        if (Number.isNaN(d.getTime())) return @json(__('projects.not_specified'));
        const loc = @json(app()->getLocale() === 'kk' ? 'kk-KZ' : (app()->getLocale() === 'en' ? 'en-US' : 'ru-RU'));
        return d.toLocaleDateString(loc, { day: 'numeric', month: 'long', year: 'numeric' });
    },
    onChecklistDetailClosed() {
        if (typeof window.__tasksClearChecklistDeepLink === 'function') {
            window.__tasksClearChecklistDeepLink();
        }
        if (typeof window.__tasksReloadCalendar === 'function') {
            window.__tasksReloadCalendar();
        }
    },
};
</script>
@include('designer.projects.partials.checklist-scripts')

<script>
(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const workspace = document.getElementById('crm-workspace');
    const locale = workspace?.dataset.locale || 'ru-RU';
    const isCorporate = @json((bool) ($isCorporate ?? false));
    const currentUser = @json($currentUser ?? ['id' => 0, 'name' => '']);
    const assigneeOptions = @json($assigneeOptions ?? []);
    const projectsData = @json($projectsData ?? []);
    const statusColumns = @json($statusColumns ?? []);
    const VIEW_KEY = 'tasks_view_mode';

    const routes = {
        data: @json(route('tasks.data')),
        events: @json(route('tasks.events')),
        store: @json(route('tasks.store')),
        show: (id) => @json(url('/tasks')) + '/' + id,
        update: (id) => @json(url('/tasks')) + '/' + id,
        status: (id) => @json(url('/tasks')) + '/' + id + '/status',
        destroy: (id) => @json(url('/tasks')) + '/' + id,
    };

    const i18n = {
        activeCountTpl: @json(__('tasks.active_count')),
        createTitle: @json(__('tasks.create_title')),
        createSubtitle: @json(__('tasks.create_subtitle')),
        editTitle: @json(__('tasks.edit_title')),
        viewTitle: @json(__('tasks.view_title')),
        projectNone: @json(__('tasks.project_none')),
        emptyColumn: @json(__('tasks.empty_column')),
        overdue: @json(__('tasks.overdue')),
        dueToday: @json(__('tasks.due_today')),
        created: @json(__('tasks.created')),
        updated: @json(__('tasks.updated')),
        deleted: @json(__('tasks.deleted')),
        statusUpdated: @json(__('tasks.status_updated')),
        deleteConfirm: @json(__('tasks.delete_confirm')),
        error: @json(__('tasks.generic_error')),
        fieldCreator: @json(__('tasks.field_creator')),
        fieldAssignee: @json(__('tasks.field_assignee')),
        fieldProject: @json(__('tasks.field_project')),
        fieldDueAt: @json(__('tasks.field_due_at')),
        sourceChecklist: @json(__('tasks.source_checklist')),
        notSpecified: @json(__('projects.not_specified')),
        noTasksToday: @json(__('dashboard.no_tasks')),
    };

    const els = {
        kanban: document.getElementById('crm-kanban'),
        calendarPanel: document.getElementById('tasks-calendar-panel'),
        calendarGrid: document.getElementById('tasks-calendar-grid'),
        calendarTitle: document.getElementById('tasks-cal-title'),
        activeBadge: document.getElementById('tasks-active-badge'),
        search: document.getElementById('tasks-search'),
        filtersWrap: document.getElementById('tasks-filters-wrap'),
        filtersBtn: document.getElementById('tasks-filters-btn'),
        filtersBackdrop: document.getElementById('tasks-filters-backdrop'),
        filtersPanel: document.getElementById('tasks-filters-panel'),
        filtersBadge: document.getElementById('tasks-filters-badge'),
        filterAssignee: document.getElementById('tasks-filter-assignee'),
        filterProject: document.getElementById('tasks-filter-project'),
        filterOverdue: document.getElementById('tasks-filter-overdue'),
        filterNoProject: document.getElementById('tasks-filter-noproject'),
        filtersReset: document.getElementById('tasks-filters-reset'),
        dayDrawerOverlay: document.getElementById('tasks-day-drawer-overlay'),
        dayDrawer: document.getElementById('tasks-day-drawer'),
        dayDrawerTitle: document.getElementById('tasks-day-drawer-title'),
        dayDrawerBody: document.getElementById('tasks-day-drawer-body'),
    };

    function readStoredView() {
        const params = new URLSearchParams(window.location.search);
        const fromQuery = params.get('view');
        if (fromQuery === 'kanban' || fromQuery === 'calendar') return fromQuery;
        const fromStore = localStorage.getItem(VIEW_KEY);
        if (fromStore === 'kanban' || fromStore === 'calendar') return fromStore;
        return 'kanban';
    }

    const state = {
        view: readStoredView(),
        tasks: [],
        activeCount: {{ (int) ($activeTasksCount ?? 0) }},
        search: '',
        filters: { status: [], assigneeId: '', projectId: '', overdue: false, noProject: false },
        kanbanScroll: 0,
        editingId: null,
        editingTask: null,
        dirty: false,
        snapshot: null,
        deleteId: null,
        calendar: { date: new Date(), byDate: {} },
    };

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

    const debounce = (fn, ms = 350) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    /* ---------------------------------------------------------------- View switching ---------------------------------------------------------------- */

    function syncUrlView() {
        const url = new URL(window.location.href);
        url.searchParams.set('view', state.view);
        window.history.replaceState({}, '', url);
    }

    function setView(view, { persist = true } = {}) {
        state.view = view === 'calendar' ? 'calendar' : 'kanban';
        document.querySelectorAll('#crm-workspace .crm-view-btn').forEach((btn) => {
            const on = btn.dataset.view === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        els.kanban.classList.toggle('is-hidden', state.view !== 'kanban');
        els.calendarPanel.classList.toggle('is-hidden', state.view !== 'calendar');
        if (persist) {
            localStorage.setItem(VIEW_KEY, state.view);
            syncUrlView();
        }
        if (state.view === 'kanban') {
            els.calendarGrid.innerHTML = '';
            loadTasks();
        } else {
            els.kanban.innerHTML = '';
            renderCalendar();
        }
    }

    /* ---------------------------------------------------------------- Kanban ---------------------------------------------------------------- */

    function updateBadge() {
        if (els.activeBadge) els.activeBadge.textContent = i18n.activeCountTpl.replace(':count', state.activeCount);
    }

    function buildQuery() {
        const params = new URLSearchParams();
        if (state.search) params.set('q', state.search);
        if (state.filters.status.length) params.set('status', state.filters.status.join(','));
        if (isCorporate && state.filters.assigneeId) params.set('assignee_id', state.filters.assigneeId);
        if (state.filters.noProject) params.set('project_id', 'none');
        else if (state.filters.projectId) params.set('project_id', state.filters.projectId);
        if (state.filters.overdue) params.set('overdue', '1');
        return params.toString();
    }

    async function loadTasks() {
        try {
            const qs = buildQuery();
            const res = await fetch(routes.data + (qs ? ('?' + qs) : ''), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            state.tasks = data.tasks || [];
            state.activeCount = data.active_count ?? state.activeCount;
            updateBadge();
            if (state.view === 'kanban') renderKanban();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    function formatDue(task) {
        return task.due_at_label || '';
    }

    function cardEl(task) {
        const isChecklist = task.source_type === 'checklist';
        const overdue = !!task.is_overdue;
        const dueToday = !!task.is_due_today;
        const dueClass = overdue ? 'is-danger' : (dueToday ? 'is-warn' : '');
        const el = document.createElement('div');
        el.className = 'crm-board-card' + (overdue ? ' is-overdue' : '') + (isChecklist ? ' is-checklist' : '');
        el.draggable = !isChecklist && task.draggable !== false;
        el.dataset.id = String(task.id);
        if (isChecklist) el.dataset.checklistId = String(task.checklist_id || '');
        if (isChecklist) el.dataset.projectId = String(task.project_id || '');
        el.innerHTML = `
            <div class="crm-board-card-title" title="${escapeHtml(task.title || '')}">${escapeHtml(task.title || '')}</div>
            <div class="crm-board-card-meta">
                ${isChecklist ? `<div class="text-[11px] text-[var(--crm-muted)]">${escapeHtml(i18n.sourceChecklist || '')}</div>` : ''}
                <div>${escapeHtml(i18n.fieldAssignee)}: <strong>${escapeHtml(task.assignee_name || i18n.notSpecified || '—')}</strong></div>
                ${task.project_name ? `<div>${escapeHtml(i18n.fieldProject)}: <strong>${escapeHtml(task.project_name)}</strong></div>` : ''}
                <div class="${dueClass}">
                    ${escapeHtml(i18n.fieldDueAt)}: <strong>${escapeHtml(formatDue(task) || '—')}</strong>
                </div>
            </div>
            <div class="crm-board-card-foot">
                <div class="crm-board-card-flags" style="margin-left:0">
                    ${overdue ? `<span class="crm-flag is-danger" title="${escapeHtml(i18n.overdue)}"></span>` : ''}
                    ${dueToday && !overdue ? `<span class="crm-flag is-warn" title="${escapeHtml(i18n.dueToday)}"></span>` : ''}
                </div>
            </div>
        `;
        if (!isChecklist) {
            el.addEventListener('dragstart', (e) => {
                el.classList.add('is-dragging');
                state.kanbanScroll = els.kanban.scrollLeft;
                e.dataTransfer.setData('text/plain', String(task.id));
                e.dataTransfer.effectAllowed = 'move';
            });
            el.addEventListener('dragend', () => el.classList.remove('is-dragging'));
            el.addEventListener('click', () => openEditTask(task.id));
        } else {
            el.addEventListener('click', () => {
                const projectId = Number(task.project_id || 0);
                const checklistId = Number(task.checklist_id || 0);
                if (projectId && checklistId) {
                    openChecklistFromCalendar({ projectId, checklistId, itemId: null, date: '', pushUrl: true });
                }
            });
        }
        return el;
    }

    function renderKanban() {
        const scrollLeft = els.kanban.scrollLeft;
        els.kanban.innerHTML = '';
        statusColumns.forEach((col) => {
            const cards = state.tasks.filter((t) => t.status === col.key);
            const colEl = document.createElement('div');
            colEl.className = 'crm-board-col';
            colEl.innerHTML = `
                <div class="crm-board-col-head">
                    <span class="crm-board-col-title" title="${escapeHtml(col.label)}">${escapeHtml(col.label)}</span>
                    <span class="crm-board-col-count">${cards.length}</span>
                </div>
                <div class="crm-board-col-body" data-drop="${escapeHtml(col.key)}"></div>
            `;
            const body = colEl.querySelector('[data-drop]');
            const highlight = (on) => colEl.classList.toggle('is-drop-target', on);
            const onDragOver = (e) => { e.preventDefault(); highlight(true); };
            const onDragLeave = (e) => { if (!colEl.contains(e.relatedTarget)) highlight(false); };
            const onDrop = async (e) => {
                e.preventDefault();
                highlight(false);
                const id = Number(e.dataTransfer.getData('text/plain'));
                if (id) await moveTaskStatus(id, col.key);
            };
            body.addEventListener('dragover', onDragOver);
            colEl.addEventListener('dragover', onDragOver);
            body.addEventListener('dragleave', onDragLeave);
            colEl.addEventListener('dragleave', onDragLeave);
            body.addEventListener('drop', onDrop);
            colEl.addEventListener('drop', onDrop);

            if (!cards.length) {
                body.innerHTML = `<div class="crm-board-empty">${escapeHtml(i18n.emptyColumn)}</div>`;
            } else {
                cards.forEach((t) => body.appendChild(cardEl(t)));
            }
            els.kanban.appendChild(colEl);
        });
        els.kanban.scrollLeft = state.kanbanScroll || scrollLeft;
    }

    async function moveTaskStatus(id, status) {
        const task = state.tasks.find((t) => String(t.id) === String(id));
        if (!task || task.source_type === 'checklist' || task.status === status) return;
        if (task.can_change_status === false) return;
        const prevStatus = task.status;
        task.status = status;
        renderKanban();
        try {
            const res = await fetch(routes.status(id), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ status }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            if (data.task) {
                const idx = state.tasks.findIndex((t) => String(t.id) === String(id));
                if (idx >= 0) state.tasks[idx] = data.task;
            }
            renderKanban();
            toast(data.message || i18n.statusUpdated, 'success');
        } catch (e) {
            task.status = prevStatus;
            renderKanban();
            toast(e.message || i18n.error, 'error');
        }
    }

    /* ---------------------------------------------------------------- Filters ---------------------------------------------------------------- */

    function populateFilterSelects() {
        if (els.filterAssignee) {
            els.filterAssignee.innerHTML = `<option value="">${escapeHtml(@json(__('tasks.filter_all')))}</option>` +
                assigneeOptions.map((o) => `<option value="${o.id}">${escapeHtml(o.name || o.email || ('#' + o.id))}</option>`).join('');
        }
        if (els.filterProject) {
            els.filterProject.innerHTML = `<option value="">${escapeHtml(@json(__('tasks.filter_all')))}</option>` +
                projectsData.map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
        }
    }

    function updateFiltersBadge() {
        let count = 0;
        if (state.filters.status.length) count += 1;
        if (state.filters.assigneeId) count += 1;
        if (state.filters.projectId || state.filters.noProject) count += 1;
        if (state.filters.overdue) count += 1;
        if (els.filtersBadge) {
            els.filtersBadge.hidden = count === 0;
            els.filtersBadge.textContent = String(count);
        }
    }

    function openFilters() {
        els.filtersPanel.classList.add('is-open');
        els.filtersBackdrop.hidden = false;
        els.filtersBackdrop.classList.add('is-open');
        els.filtersBtn.setAttribute('aria-expanded', 'true');
    }
    function closeFilters() {
        els.filtersPanel.classList.remove('is-open');
        els.filtersBackdrop.hidden = true;
        els.filtersBackdrop.classList.remove('is-open');
        els.filtersBtn.setAttribute('aria-expanded', 'false');
    }

    function applyFilters() {
        updateFiltersBadge();
        if (state.view === 'kanban') loadTasks();
    }

    /* ---------------------------------------------------------------- Task modal ---------------------------------------------------------------- */

    const modal = {
        root: document.getElementById('task-form-modal'),
        title: document.getElementById('task-form-title'),
        subtitle: document.getElementById('task-form-subtitle'),
        form: document.getElementById('task-form'),
        id: document.getElementById('tf-id'),
        titleInput: document.getElementById('tf-title'),
        description: document.getElementById('tf-description'),
        assignee: document.getElementById('tf-assignee'),
        assigneeHidden: document.getElementById('tf-assignee-hidden'),
        status: document.getElementById('tf-status'),
        project: document.getElementById('tf-project'),
        due: document.getElementById('tf-due'),
        meta: document.getElementById('tf-meta'),
        deleteBtn: document.getElementById('task-form-delete'),
        saveBtn: document.getElementById('task-form-save'),
        unsaved: document.getElementById('task-unsaved-modal'),
        deleteModal: document.getElementById('task-delete-modal'),
        deleteMessage: null,
    };
    [modal.root, modal.unsaved, modal.deleteModal].forEach((el) => {
        if (el && el.parentElement !== document.body) document.body.appendChild(el);
    });

    function populateStaticSelects() {
        if (modal.status) {
            modal.status.innerHTML = statusColumns.map((c) => `<option value="${escapeHtml(c.key)}">${escapeHtml(c.label)}</option>`).join('');
        }
        if (modal.project) {
            modal.project.innerHTML = `<option value="">${escapeHtml(i18n.projectNone)}</option>` +
                projectsData.map((p) => `<option value="${p.id}">${escapeHtml(p.name)}</option>`).join('');
        }
        if (isCorporate && modal.assignee && modal.assignee.tagName === 'SELECT') {
            modal.assignee.innerHTML = assigneeOptions.map((o) => `<option value="${o.id}">${escapeHtml(o.name || o.email || ('#' + o.id))}</option>`).join('');
        }
    }

    function isoToDatetimeLocal(iso) {
        if (!iso || typeof iso !== 'string') return '';
        const m = iso.match(/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/);
        return m ? `${m[1]}T${m[2]}` : '';
    }

    function readTaskForm() {
        const assigneeId = isCorporate
            ? (modal.assignee?.value || String(currentUser.id))
            : String(currentUser.id);
        return {
            title: modal.titleInput.value.trim(),
            description: modal.description.value.trim() || null,
            assignee_id: assigneeId,
            status: modal.status.value,
            project_id: modal.project.value || null,
            due_at: modal.due.value,
        };
    }

    function readFormSnapshot() {
        return JSON.stringify(readTaskForm());
    }

    function markTaskDirty() {
        if (!state.snapshot) return;
        state.dirty = readFormSnapshot() !== state.snapshot;
    }

    function clearTaskFieldErrors() {
        modal.form.querySelectorAll('[data-error]').forEach((el) => { el.textContent = ''; el.classList.add('hidden'); });
    }

    function showTaskFieldErrors(errors) {
        clearTaskFieldErrors();
        Object.entries(errors || {}).forEach(([key, msgs]) => {
            const el = modal.form.querySelector(`[data-error="${key}"]`);
            if (el) { el.textContent = Array.isArray(msgs) ? msgs[0] : msgs; el.classList.remove('hidden'); }
        });
    }

    function setTaskFieldsDisabled(disabled) {
        [modal.titleInput, modal.description, modal.project, modal.due].forEach((el) => { if (el) el.disabled = disabled; });
        if (isCorporate && modal.assignee?.tagName === 'SELECT') modal.assignee.disabled = disabled;
    }

    function applyTaskForm(task) {
        clearTaskFieldErrors();
        modal.id.value = task?.id || '';
        modal.titleInput.value = task?.title || '';
        modal.description.value = task?.description || '';
        if (isCorporate && modal.assignee?.tagName === 'SELECT') {
            modal.assignee.value = String(task?.assignee_id || currentUser.id);
        }
        modal.status.value = task?.status || (statusColumns[0]?.key || '');
        modal.project.value = task?.project_id ? String(task.project_id) : '';
        modal.due.value = isoToDatetimeLocal(task?.due_at);

        const canEdit = task ? !!task.can_edit : true;
        const canChangeStatus = task ? !!task.can_change_status : true;
        const canDelete = task ? !!task.can_delete : false;

        setTaskFieldsDisabled(!canEdit);
        modal.status.disabled = !(canEdit || canChangeStatus);
        modal.deleteBtn.classList.toggle('hidden', !canDelete);
        modal.saveBtn.classList.toggle('hidden', !(canEdit || canChangeStatus));

        modal.title.textContent = !task ? i18n.createTitle : ((canEdit || canChangeStatus) ? i18n.editTitle : i18n.viewTitle);
        modal.subtitle.textContent = task ? (task.status_label || '') : i18n.createSubtitle;

        if (task?.creator_name) {
            modal.meta.textContent = `${i18n.fieldCreator}: ${task.creator_name}`;
            modal.meta.classList.remove('hidden');
        } else {
            modal.meta.classList.add('hidden');
        }
    }

    function openModalRoot(root) {
        root.classList.add('open');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeModalRoot(root) {
        root.classList.remove('open');
        root.setAttribute('aria-hidden', 'true');
        if (![modal.root, modal.unsaved, modal.deleteModal].some((el) => el.classList.contains('open'))) {
            document.body.style.overflow = '';
        }
    }

    function clearTaskUrlParam() {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('task')) return;
        url.searchParams.delete('task');
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
    }

    function setTaskUrlParam(id) {
        const url = new URL(window.location.href);
        url.searchParams.set('task', String(id));
        window.history.replaceState({}, '', url.pathname + url.search);
    }

    function closeTaskModal(force = false) {
        if (state.dirty && !force) {
            openModalRoot(modal.unsaved);
            return;
        }
        closeModalRoot(modal.unsaved);
        closeModalRoot(modal.root);
        state.dirty = false;
        state.editingId = null;
        state.editingTask = null;
        state.snapshot = null;
        modal.form.reset();
        clearTaskUrlParam();
    }

    function openCreateTask() {
        state.editingId = null;
        state.editingTask = null;
        state.dirty = false;
        applyTaskForm(null);
        state.snapshot = readFormSnapshot();
        openModalRoot(modal.root);
    }

    async function openEditTask(id) {
        state.editingId = id;
        try {
            const res = await fetch(routes.show(id), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            state.editingTask = data.task;
            state.dirty = false;
            applyTaskForm(data.task);
            state.snapshot = readFormSnapshot();
            openModalRoot(modal.root);
            setTaskUrlParam(id);
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    async function submitTaskForm(e) {
        e.preventDefault();
        clearTaskFieldErrors();
        const payload = readTaskForm();
        if (!payload.title) {
            showTaskFieldErrors({ title: [@json(__('tasks.field_title'))] });
            return;
        }
        if (!payload.due_at) {
            showTaskFieldErrors({ due_at: [@json(__('tasks.field_due_at'))] });
            return;
        }
        modal.saveBtn.disabled = true;
        try {
            let res;
            const editing = state.editingTask;
            const statusOnly = editing && !editing.can_edit && editing.can_change_status;
            if (statusOnly) {
                res = await fetch(routes.status(state.editingId), {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ status: payload.status }),
                });
            } else if (state.editingId) {
                res = await fetch(routes.update(state.editingId), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify(payload),
                });
            } else {
                res = await fetch(routes.store, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify(payload),
                });
            }
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                if (data.errors) showTaskFieldErrors(data.errors);
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || i18n.error);
            }
            const task = data.task;
            const idx = state.tasks.findIndex((t) => t.id === task.id);
            if (idx >= 0) state.tasks[idx] = task; else state.tasks.unshift(task);
            state.dirty = false;
            closeTaskModal(true);
            toast(data.message || (state.editingId ? i18n.updated : i18n.created), 'success');
            if (state.view === 'kanban') renderKanban();
            else renderCalendar();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        } finally {
            modal.saveBtn.disabled = false;
        }
    }

    function askDeleteTask() {
        openModalRoot(modal.deleteModal);
    }

    async function confirmDeleteTask() {
        const id = state.editingId;
        if (!id) return;
        try {
            const res = await fetch(routes.destroy(id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            state.tasks = state.tasks.filter((t) => t.id !== id);
            closeModalRoot(modal.deleteModal);
            closeTaskModal(true);
            toast(data.message || i18n.deleted, 'success');
            if (state.view === 'kanban') renderKanban();
            else renderCalendar();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    /* ---------------------------------------------------------------- Calendar ---------------------------------------------------------------- */

    const months = {
        kk: ['Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'],
        ru: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
        en: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    };
    const weekdays = {
        kk: ['Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб', 'Жс'],
        ru: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
        en: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    };
    const localeKey = '{{ app()->getLocale() }}';
    const monthNames = months[localeKey] || months.en;
    const dayNames = weekdays[localeKey] || weekdays.en;

    function toISODate(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function getFirstDayOfMonth(date) { return new Date(date.getFullYear(), date.getMonth(), 1); }
    function getLastDayOfMonth(date) { return new Date(date.getFullYear(), date.getMonth() + 1, 0); }

    function calcMonthRange(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = getFirstDayOfMonth(date);
        const lastDay = getLastDayOfMonth(date);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = (firstDay.getDay() + 6) % 7;
        const weeks = Math.ceil((startingDayOfWeek + daysInMonth) / 7);
        const gridStart = new Date(year, month, 1 - startingDayOfWeek);
        const gridEnd = new Date(year, month, 1 - startingDayOfWeek + (weeks * 7) - 1);
        return { gridStart, gridEnd, startingDayOfWeek, daysInMonth, weeks, year, month };
    }

    async function loadCalendarEvents(startISO, endISO) {
        try {
            const res = await fetch(`${routes.events}?start=${encodeURIComponent(startISO)}&end=${encodeURIComponent(endISO)}`, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) throw new Error('events fetch failed');
            const data = await res.json();
            const events = Array.isArray(data?.events) ? data.events : [];
            const byDate = {};
            events.forEach((ev) => {
                const key = ev?.date ? String(ev.date) : null;
                if (!key) return;
                if (!byDate[key]) byDate[key] = [];
                byDate[key].push(ev);
            });
            state.calendar.byDate = byDate;
        } catch (e) {
            state.calendar.byDate = {};
        }
    }

    function eventTaskId(ev) {
        if (ev?.event_meta?.task_id) return Number(ev.event_meta.task_id);
        const parts = String(ev?.id || '').split(':');
        return parts[0] === 'designer_task' ? Number(parts[1] || 0) : 0;
    }

    function renderEventLink(ev) {
        const isChecklist = ev.event_type === 'checklist_step';
        const isTask = ev.event_type === 'designer_task';
        const title = escapeHtml(ev.title || '');
        const time = ev.time ? `${escapeHtml(ev.time)} ` : '';
        const href = ev.url_show ? escapeHtml(ev.url_show) : '#';
        let attrs = '';
        if (isChecklist) {
            attrs = ` data-checklist-event="1" data-project-id="${Number(ev.project_id || 0)}" data-checklist-id="${Number(ev.project_stage_id || 0)}" data-item-id="${Number(ev.source_id || 0)}" data-event-date="${escapeHtml(ev.date || '')}"`;
        } else if (isTask) {
            attrs = ` data-task-event="1" data-task-id="${eventTaskId(ev)}"`;
        }
        const doneClass = ev.done ? ' is-done' : '';
        const overdueClass = (isTask && ev.is_overdue && !ev.done) ? ' is-overdue' : '';
        return `<a href="${href}" class="tasks-cal-event${doneClass}${overdueClass}" title="${title}"${attrs}>${time}${title}</a>`;
    }

    function renderCalendarGrid() {
        const { gridStart, weeks, month } = calcMonthRange(state.calendar.date);
        let html = '';
        dayNames.forEach((d) => { html += `<div class="tasks-cal-day-header">${escapeHtml(d)}</div>`; });

        const today = new Date();
        for (let i = 0; i < weeks * 7; i++) {
            const date = new Date(gridStart);
            date.setDate(date.getDate() + i);
            const key = toISODate(date);
            const isOtherMonth = date.getMonth() !== month;
            const isToday = date.toDateString() === today.toDateString();
            const events = state.calendar.byDate[key] || [];
            const shown = events.slice(0, 3);
            const rest = Math.max(0, events.length - 3);
            html += `
                <div class="tasks-cal-day${isOtherMonth ? ' is-other-month' : ''}${isToday ? ' is-today' : ''}">
                    <div class="tasks-cal-day-number">${date.getDate()}</div>
                    ${shown.map(renderEventLink).join('')}
                    ${rest > 0 ? `<button type="button" class="tasks-cal-more" data-day-more data-date="${key}">+${rest}</button>` : ''}
                </div>
            `;
        }
        els.calendarGrid.innerHTML = html;
        els.calendarTitle.textContent = `${monthNames[month]} ${state.calendar.date.getFullYear()}`;
    }

    async function renderCalendar() {
        const { gridStart, gridEnd } = calcMonthRange(state.calendar.date);
        await loadCalendarEvents(toISODate(gridStart), toISODate(gridEnd));
        renderCalendarGrid();
    }

    function openDayDrawer(dateISO) {
        const dt = new Date(`${dateISO}T00:00:00`);
        const valid = !Number.isNaN(dt.getTime());
        els.dayDrawerTitle.textContent = valid ? `${dt.getDate()} ${monthNames[dt.getMonth()]} ${dt.getFullYear()}` : dateISO;
        const events = state.calendar.byDate[dateISO] || [];
        els.dayDrawerBody.innerHTML = events.length
            ? events.map((ev) => `<div>${renderEventLink(ev)}</div>`).join('')
            : `<div class="text-sm text-[var(--crm-muted)] text-center py-6">${escapeHtml(i18n.noTasksToday)}</div>`;
        els.dayDrawerOverlay.classList.remove('hidden');
        els.dayDrawer.classList.remove('hidden');
        els.dayDrawer.setAttribute('aria-hidden', 'false');
    }
    function closeDayDrawer() {
        els.dayDrawerOverlay.classList.add('hidden');
        els.dayDrawer.classList.add('hidden');
        els.dayDrawer.setAttribute('aria-hidden', 'true');
    }

    /* ---------------------------------------------------------------- Checklist deep link bridge ---------------------------------------------------------------- */

    let checklistDeepLinkPushed = false;

    function buildChecklistDeepLink({ projectId, checklistId, itemId, date }) {
        const url = new URL(window.location.href);
        url.searchParams.set('project', String(projectId));
        url.searchParams.set('checklist', String(checklistId));
        if (itemId) url.searchParams.set('item', String(itemId));
        else url.searchParams.delete('item');
        if (date) url.searchParams.set('date', String(date));
        return url;
    }
    function clearChecklistDeepLinkParams(url) {
        url.searchParams.delete('project');
        url.searchParams.delete('checklist');
        url.searchParams.delete('item');
        url.searchParams.delete('step');
        return url;
    }

    window.__tasksClearChecklistDeepLink = function () {
        if (checklistDeepLinkPushed) {
            checklistDeepLinkPushed = false;
            history.back();
            return;
        }
        const url = clearChecklistDeepLinkParams(new URL(window.location.href));
        history.replaceState({}, '', url.pathname + url.search);
    };
    window.__tasksReloadCalendar = function () {
        if (state.view === 'calendar') renderCalendar();
    };

    async function openChecklistFromCalendar({ projectId, checklistId, itemId, date, pushUrl = true }) {
        if (!window.CrmChecklists?.openByIds) return false;
        const ok = await window.CrmChecklists.openByIds({ projectId, checklistId, stepId: itemId || null });
        if (!ok) return false;
        if (pushUrl) {
            const url = buildChecklistDeepLink({ projectId, checklistId, itemId, date });
            const current = window.location.pathname + window.location.search;
            const next = url.pathname + url.search;
            if (current !== next) {
                history.pushState({ checklistModal: true }, '', next);
                checklistDeepLinkPushed = true;
            }
        }
        return true;
    }

    window.addEventListener('popstate', () => {
        checklistDeepLinkPushed = false;
        if (document.getElementById('checklist-detail-root')?.classList.contains('open')) {
            window.CrmChecklists?.closeDetail?.(true, { skipClosedHook: true });
            window.__tasksReloadCalendar();
        }
    });

    /* ---------------------------------------------------------------- Events ---------------------------------------------------------------- */

    document.querySelectorAll('#crm-workspace .crm-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => setView(btn.dataset.view));
    });

    const debouncedSearch = debounce(() => {
        state.search = els.search.value.trim();
        if (state.view === 'kanban') loadTasks();
    }, 350);
    els.search?.addEventListener('input', debouncedSearch);

    els.filtersBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (els.filtersPanel.classList.contains('is-open')) closeFilters(); else openFilters();
    });
    els.filtersBackdrop?.addEventListener('click', closeFilters);
    document.addEventListener('click', (e) => {
        if (!els.filtersWrap.contains(e.target)) closeFilters();
    });
    document.getElementById('tasks-filter-status-list')?.querySelectorAll('.tasks-status-check').forEach((cb) => {
        cb.addEventListener('change', () => {
            state.filters.status = [...document.querySelectorAll('#tasks-filter-status-list .tasks-status-check:checked')].map((c) => c.value);
            applyFilters();
        });
    });
    els.filterAssignee?.addEventListener('change', () => {
        state.filters.assigneeId = els.filterAssignee.value;
        applyFilters();
    });
    els.filterProject?.addEventListener('change', () => {
        state.filters.projectId = els.filterProject.value;
        if (state.filters.projectId && els.filterNoProject) {
            state.filters.noProject = false;
            els.filterNoProject.checked = false;
        }
        applyFilters();
    });
    els.filterOverdue?.addEventListener('change', () => {
        state.filters.overdue = !!els.filterOverdue.checked;
        applyFilters();
    });
    els.filterNoProject?.addEventListener('change', () => {
        state.filters.noProject = !!els.filterNoProject.checked;
        if (state.filters.noProject && els.filterProject) {
            state.filters.projectId = '';
            els.filterProject.value = '';
        }
        applyFilters();
    });
    els.filtersReset?.addEventListener('click', () => {
        state.filters = { status: [], assigneeId: '', projectId: '', overdue: false, noProject: false };
        document.querySelectorAll('#tasks-filter-status-list .tasks-status-check').forEach((c) => { c.checked = false; });
        if (els.filterAssignee) els.filterAssignee.value = '';
        if (els.filterProject) els.filterProject.value = '';
        if (els.filterOverdue) els.filterOverdue.checked = false;
        if (els.filterNoProject) els.filterNoProject.checked = false;
        applyFilters();
    });

    document.getElementById('tasks-create-btn')?.addEventListener('click', openCreateTask);

    modal.form?.addEventListener('submit', submitTaskForm);
    ['tf-title', 'tf-description', 'tf-status', 'tf-project', 'tf-due'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', markTaskDirty);
        document.getElementById(id)?.addEventListener('change', markTaskDirty);
    });
    if (isCorporate) modal.assignee?.addEventListener('change', markTaskDirty);
    document.getElementById('task-form-close')?.addEventListener('click', () => closeTaskModal(false));
    document.getElementById('task-form-cancel')?.addEventListener('click', () => closeTaskModal(false));
    modal.root?.querySelector('[data-close-backdrop]')?.addEventListener('click', () => closeTaskModal(false));
    document.getElementById('task-form-delete')?.addEventListener('click', askDeleteTask);
    document.getElementById('task-unsaved-continue')?.addEventListener('click', () => closeModalRoot(modal.unsaved));
    document.getElementById('task-unsaved-leave')?.addEventListener('click', () => closeTaskModal(true));
    document.getElementById('task-delete-cancel')?.addEventListener('click', () => closeModalRoot(modal.deleteModal));
    document.getElementById('task-delete-confirm')?.addEventListener('click', confirmDeleteTask);

    document.getElementById('tasks-cal-prev')?.addEventListener('click', () => {
        state.calendar.date.setMonth(state.calendar.date.getMonth() - 1);
        renderCalendar();
    });
    document.getElementById('tasks-cal-next')?.addEventListener('click', () => {
        state.calendar.date.setMonth(state.calendar.date.getMonth() + 1);
        renderCalendar();
    });
    document.getElementById('tasks-cal-today')?.addEventListener('click', () => {
        state.calendar.date = new Date();
        renderCalendar();
    });
    document.getElementById('tasks-day-drawer-close')?.addEventListener('click', closeDayDrawer);
    els.dayDrawerOverlay?.addEventListener('click', closeDayDrawer);

    els.calendarGrid?.addEventListener('click', (e) => {
        const checklistLink = e.target.closest('[data-checklist-event="1"]');
        if (checklistLink) {
            e.preventDefault();
            const projectId = Number(checklistLink.dataset.projectId || 0);
            const checklistId = Number(checklistLink.dataset.checklistId || 0);
            const itemId = Number(checklistLink.dataset.itemId || 0);
            const date = checklistLink.dataset.eventDate || '';
            if (projectId && checklistId) openChecklistFromCalendar({ projectId, checklistId, itemId: itemId || null, date, pushUrl: true });
            return;
        }
        const taskLink = e.target.closest('[data-task-event="1"]');
        if (taskLink) {
            e.preventDefault();
            const taskId = Number(taskLink.dataset.taskId || 0);
            if (taskId) openEditTask(taskId);
            return;
        }
        const more = e.target.closest('[data-day-more]');
        if (more) openDayDrawer(more.dataset.date);
    });
    els.dayDrawerBody?.addEventListener('click', (e) => {
        const checklistLink = e.target.closest('[data-checklist-event="1"]');
        if (checklistLink) {
            e.preventDefault();
            const projectId = Number(checklistLink.dataset.projectId || 0);
            const checklistId = Number(checklistLink.dataset.checklistId || 0);
            const itemId = Number(checklistLink.dataset.itemId || 0);
            const date = checklistLink.dataset.eventDate || '';
            if (projectId && checklistId) openChecklistFromCalendar({ projectId, checklistId, itemId: itemId || null, date, pushUrl: true });
            return;
        }
        const taskLink = e.target.closest('[data-task-event="1"]');
        if (taskLink) {
            e.preventDefault();
            const taskId = Number(taskLink.dataset.taskId || 0);
            if (taskId) openEditTask(taskId);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (modal.unsaved.classList.contains('open')) { closeModalRoot(modal.unsaved); return; }
        if (modal.deleteModal.classList.contains('open')) { closeModalRoot(modal.deleteModal); return; }
        if (modal.root.classList.contains('open')) { closeTaskModal(false); return; }
        if (els.filtersPanel.classList.contains('is-open')) closeFilters();
    });

    /* ---------------------------------------------------------------- Boot ---------------------------------------------------------------- */

    async function bootDeepLinks() {
        const params = new URLSearchParams(window.location.search);
        const taskId = Number(params.get('task') || 0);
        const projectId = Number(params.get('project') || 0);
        const checklistId = Number(params.get('checklist') || 0);
        const itemId = Number(params.get('item') || params.get('step') || 0);
        const dateStr = params.get('date');

        if (dateStr && /^\d{4}-\d{2}-\d{2}$/.test(dateStr) && state.view === 'calendar') {
            const parts = dateStr.split('-').map(Number);
            state.calendar.date = new Date(parts[0], parts[1] - 1, parts[2]);
            await renderCalendar();
        }

        if (taskId) {
            openEditTask(taskId);
        }

        if (projectId && checklistId) {
            await openChecklistFromCalendar({
                projectId, checklistId, itemId: itemId || null, date: dateStr || null, pushUrl: false,
            });
        }
    }

    populateFilterSelects();
    populateStaticSelects();
    updateBadge();
    setView(state.view, { persist: true });
    bootDeepLinks();
})();
</script>
@endpush
