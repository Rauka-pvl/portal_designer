<style>
.crm-checklist-toolbar {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    flex: 0 0 auto;
}
.crm-checklist-toolbar-left,
.crm-checklist-toolbar-right {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}
.crm-checklist-toolbar-right { margin-left: auto; }
.crm-checklist-toolbar-right .crm-toolbar-search {
    width: 11rem;
    min-width: 7rem;
    max-width: 100%;
}
.ov-panel[data-panel="checklists"]:not(.hidden) {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}
.crm-checklist-list {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
}
.crm-checklist-card {
    border: 1px solid color-mix(in srgb, var(--crm-border) 35%, transparent);
    border-radius: 0.65rem;
    background: var(--crm-surface);
    padding: 0.75rem 0.85rem;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}
.crm-checklist-card:hover {
    border-color: color-mix(in srgb, var(--crm-accent) 45%, var(--crm-border));
    background: color-mix(in srgb, var(--crm-surface-2) 55%, transparent);
}
.crm-checklist-card-title {
    font-size: 0.9rem;
    font-weight: 600;
}
.crm-checklist-card-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.25rem 0.75rem;
    margin-top: 0.4rem;
    font-size: 0.75rem;
    color: var(--crm-muted);
}
.crm-checklist-progress {
    height: 0.35rem;
    border-radius: 999px;
    background: var(--crm-surface-2);
    overflow: hidden;
    margin-top: 0.55rem;
}
.crm-checklist-progress > span {
    display: block;
    height: 100%;
    background: var(--crm-accent);
    border-radius: inherit;
}
.crm-checklist-overdue {
    color: var(--crm-danger);
    font-weight: 600;
}
.crm-checklist-modal {
    width: 96vw !important;
    max-width: none !important;
    height: 92dvh !important;
    max-height: 94dvh !important;
}
.crm-checklist-modal-header {
    display: flex !important;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    flex-shrink: 0;
    padding: 0.85rem 1.1rem;
}
.crm-checklist-modal-heading { flex: 1; min-width: 0; }
.crm-checklist-header-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex-shrink: 0;
}
.crm-checklist-close {
    width: 2.25rem;
    height: 2.25rem;
    min-width: 36px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 0.45rem;
    background: transparent;
    color: var(--crm-muted);
    cursor: pointer;
    flex-shrink: 0;
}
.crm-checklist-close:hover {
    background: var(--crm-surface-2);
    color: var(--crm-text);
}
.crm-checklist-modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    padding: 0 !important;
    display: flex;
    flex-direction: column;
}
.crm-checklist-modal-body > form {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.crm-checklist-form-grid {
    flex: 1 1 auto;
    min-height: 0;
    display: grid;
    grid-template-columns: minmax(280px, 30%) 1fr;
    gap: 0;
    height: 100%;
}
.crm-checklist-form-left,
.crm-checklist-form-right {
    min-height: 0;
    overflow: auto;
}
.crm-checklist-form-left {
    border-right: 1px solid color-mix(in srgb, var(--crm-border) 30%, transparent);
    background: color-mix(in srgb, var(--crm-surface-2) 55%, var(--crm-surface));
}
.crm-checklist-panel {
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}
.crm-checklist-steps-panel {
    height: 100%;
    background: var(--crm-surface);
}
.crm-checklist-meta-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}
.crm-checklist-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.crm-checklist-templates {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    max-height: min(38vh, 280px);
    overflow: auto;
    padding-right: 0.15rem;
}
.crm-checklist-tpl-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 0.65rem;
    border: 1px solid color-mix(in srgb, var(--crm-border) 32%, transparent);
    border-radius: 0.55rem;
    background: var(--crm-surface);
}
.crm-checklist-tpl-row.is-selected {
    border-color: color-mix(in srgb, var(--crm-accent) 55%, var(--crm-border));
    background: color-mix(in srgb, var(--crm-accent) 7%, var(--crm-surface));
    box-shadow: inset 3px 0 0 var(--crm-accent);
}
.crm-checklist-tpl-badge {
    font-size: 0.65rem;
    color: var(--crm-muted);
    white-space: nowrap;
}
.crm-checklist-selected-badge {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--crm-accent);
    padding: 0.2rem 0.45rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--crm-accent) 12%, transparent);
    white-space: nowrap;
}
.crm-checklist-menu-wrap { position: relative; }
.crm-checklist-menu-btn {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 0.4rem;
    background: transparent;
    color: var(--crm-muted);
    cursor: pointer;
}
.crm-checklist-menu-btn:hover { background: var(--crm-surface-2); color: var(--crm-text); }
.crm-checklist-menu {
    position: absolute;
    right: 0;
    top: 100%;
    z-index: 5;
    min-width: 9rem;
    padding: 0.25rem;
    border-radius: 0.5rem;
    border: 1px solid color-mix(in srgb, var(--crm-border) 40%, transparent);
    background: var(--crm-surface);
    box-shadow: 0 8px 24px rgba(0,0,0,.18);
}
.crm-checklist-menu button {
    display: block;
    width: 100%;
    text-align: left;
    padding: 0.45rem 0.6rem;
    border: 0;
    border-radius: 0.35rem;
    background: transparent;
    color: var(--crm-text);
    font-size: 0.8rem;
    cursor: pointer;
}
.crm-checklist-menu button:hover { background: var(--crm-surface-2); }
.crm-checklist-menu button.is-danger { color: var(--crm-danger); }
.crm-checklist-steps-editor {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    margin-top: 0.35rem;
}
.crm-checklist-step-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.35rem;
    border: 1px solid color-mix(in srgb, var(--crm-border) 22%, transparent);
    border-radius: 0.5rem;
    background: color-mix(in srgb, var(--crm-surface-2) 35%, var(--crm-surface));
}
.crm-checklist-step-row:hover {
    border-color: color-mix(in srgb, var(--crm-border) 45%, transparent);
}
.crm-checklist-step-row .crm-drag {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--crm-muted);
    cursor: grab;
    user-select: none;
    flex-shrink: 0;
}
.crm-checklist-step-num {
    width: 1.5rem;
    text-align: right;
    font-size: 0.75rem;
    color: var(--crm-muted);
    flex-shrink: 0;
}
.crm-checklist-step-row input[type=text] {
    flex: 1;
    min-width: 0;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    min-height: 36px;
}
.crm-checklist-save-tpl {
    display: inline-flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--crm-muted);
}
.crm-checklist-exists {
    margin: 0.85rem 1.1rem 0;
    border-radius: 0.65rem;
    border: 1px solid color-mix(in srgb, var(--crm-danger) 40%, transparent);
    background: color-mix(in srgb, var(--crm-danger) 8%, transparent);
    padding: 0.75rem 0.85rem;
}
.crm-checklist-modal-footer {
    display: flex !important;
    justify-content: flex-end;
    gap: 0.5rem;
    flex-shrink: 0;
    padding: 0.75rem 1.1rem;
    border-top: 1px solid color-mix(in srgb, var(--crm-border) 35%, transparent);
    background: var(--crm-surface);
}
.crm-checklist-detail-layout {
    display: grid;
    grid-template-columns: 1.7fr 1fr;
    gap: 1rem;
    min-height: 0;
    padding: 0.85rem 1.1rem !important;
}
.crm-checklist-detail-main,
.crm-checklist-detail-side {
    min-height: 0;
    overflow: auto;
}
.crm-checklist-detail-side {
    border-left: 1px solid color-mix(in srgb, var(--crm-border) 28%, transparent);
    padding-left: 0.85rem;
}
.crm-checklist-step-item {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.45rem 0.5rem;
    border-radius: 0.45rem;
    cursor: pointer;
}
.crm-checklist-step-item:hover,
.crm-checklist-step-item.is-active {
    background: color-mix(in srgb, var(--crm-surface-2) 70%, transparent);
}
.crm-checklist-step-item.has-result .crm-checklist-result-dot {
    background: var(--crm-accent);
}
.crm-checklist-result-dot {
    width: 0.4rem;
    height: 0.4rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--crm-muted) 40%, transparent);
    margin-top: 0.4rem;
    flex-shrink: 0;
}
.crm-modal-footer.is-tab-hidden { display: none !important; }
@media (max-width: 1024px) {
    .crm-checklist-form-grid { grid-template-columns: 34% 1fr; }
}
@media (max-width: 900px) {
    .crm-checklist-form-grid {
        grid-template-columns: 1fr;
        overflow: auto;
    }
    .crm-checklist-form-left {
        border-right: 0;
        border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 30%, transparent);
        max-height: none;
    }
    .crm-checklist-meta-row { grid-template-columns: 1fr; }
    .crm-checklist-detail-layout { grid-template-columns: 1fr; }
    .crm-checklist-detail-side {
        border-left: 0;
        border-top: 1px solid color-mix(in srgb, var(--crm-border) 28%, transparent);
        padding-left: 0;
        padding-top: 0.75rem;
    }
}
@media (max-width: 768px) {
    .crm-checklist-toolbar { flex-wrap: wrap; }
    .crm-checklist-toolbar-right {
        width: 100%;
        margin-left: 0;
        flex-wrap: wrap;
    }
    .crm-checklist-toolbar-right .crm-toolbar-search {
        flex: 1 1 100%;
        width: 100%;
    }
    .crm-checklist-modal {
        width: 100vw !important;
        max-width: 100vw !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        border-radius: 0 !important;
    }
}
</style>

<script>
(function () {
    'use strict';

    const bridge = window.CrmProjectBridge || {};
    const csrf = bridge.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const locale = document.getElementById('crm-workspace')?.dataset.locale || 'ru-RU';

    let templates = @json($templatesData ?? []);
    const stageTypes = @json($stageTypes ?? []);
    const users = @json($users ?? []);

    const routes = {
        update: (id) => @json(url('/projects')) + '/' + id,
        show: (id) => @json(url('/projects')) + '/' + id,
        templates: @json(route('projects.templates.index')),
        saveTemplate: @json(route('projects.templates.store')),
        deleteTemplate: (id) => @json(url('/projects/templates')) + '/' + id,
        stepUpdate: (id) => @json(url('/checklist-steps')) + '/' + id,
    };

    const i18n = {
        emptyTitle: @json(__('projects.checklists_empty_title')),
        emptyBody: @json(__('projects.checklists_empty_body')),
        create: @json(__('projects.create_checklist')),
        newTitle: @json(__('projects.new_checklist_title')),
        subtitle: @json(__('projects.new_checklist_subtitle')),
        notSpecified: @json(__('projects.not_specified')),
        responsible: @json(__('projects.responsible')),
        deadline: @json(__('projects.deadline')),
        progress: @json(__('projects.checklist_progress')),
        open: @json(__('projects.checklist_open')),
        edit: @json(__('projects.checklist_edit')),
        del: @json(__('projects.checklist_delete')),
        delConfirm: @json(__('projects.checklist_delete_confirm')),
        stageExists: @json(__('projects.checklist_stage_exists')),
        useTemplate: @json(__('projects.checklist_use_template')),
        selectedBadge: @json(__('projects.checklist_selected')),
        tplRename: @json(__('projects.checklist_tpl_rename')),
        tplEdit: @json(__('projects.checklist_tpl_edit')),
        tplDelete: @json(__('projects.checklist_tpl_delete')),
        stepDelete: @json(__('projects.checklist_step_delete')),
        stageMeta: @json(__('projects.project_stages')),
        tplSystem: @json(__('projects.checklist_template_system')),
        tplMine: @json(__('projects.checklist_template_mine')),
        noTemplates: @json(__('projects.checklist_no_templates')),
        stepsCount: @json(__('projects.checklist_steps_count')),
        created: @json(__('projects.checklist_created')),
        saved: @json(__('projects.checklist_saved')),
        deleted: @json(__('projects.checklist_deleted')),
        resultSaved: @json(__('projects.checklist_result_saved')),
        resultSaving: @json(__('projects.checklist_result_saving')),
        noResult: @json(__('projects.checklist_no_result')),
        selectStage: @json(__('projects.select_stage_placeholder')),
        selectTemplate: @json(__('projects.select_template')),
        addAtLeastOne: @json(__('projects.add_at_least_one_step')),
        templateSaved: @json(__('projects.template_saved')),
        templateDeleted: @json(__('projects.template_deleted')),
        templateDeleteOnlyOwn: @json(__('projects.template_delete_only_own')),
        deleteTemplateConfirm: @json(__('projects.delete_template_confirm')),
        stepPlaceholder: @json(__('projects.step_title_placeholder')),
        error: @json(__('projects.save_error_generic')),
        notFound: @json(__('projects.checklist_not_found')),
        openProject: @json(__('projects.checklist_open_project')),
        stageLabels: {
            measurement: @json(__('projects.stage_measurement')),
            planning: @json(__('projects.stage_planning')),
            drawings: @json(__('projects.stage_drawings')),
            equipment: @json(__('projects.stage_equipment')),
            estimate: @json(__('projects.stage_estimate')),
            visualization: @json(__('projects.stage_visualization')),
        },
        states: {
            not_started: @json(__('projects.checklist_state_not_started')),
            in_progress: @json(__('projects.checklist_state_in_progress')),
            done: @json(__('projects.checklist_state_done')),
            overdue: @json(__('projects.checklist_state_overdue')),
        },
    };

    const toast = typeof bridge.toast === 'function'
        ? bridge.toast
        : (msg, type) => { if (type === 'success') return; if (window.projectAlert) window.projectAlert('error', msg); };
    const escapeHtml = typeof bridge.escapeHtml === 'function'
        ? bridge.escapeHtml
        : (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const formatDate = typeof bridge.formatDate === 'function'
        ? bridge.formatDate
        : (iso) => {
            if (!iso) return i18n.notSpecified;
            const d = new Date(String(iso).length <= 10 ? iso + 'T00:00:00' : iso);
            if (Number.isNaN(d.getTime())) return i18n.notSpecified;
            return d.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
        };
    let standaloneProject = null;
    const getCurrentProject = () => {
        if (typeof bridge.getCurrentProject === 'function') {
            const fromBridge = bridge.getCurrentProject();
            if (fromBridge) return fromBridge;
        }
        return standaloneProject;
    };
    const setStandaloneProject = (project) => {
        standaloneProject = project || null;
        if (typeof bridge.setCurrentProject === 'function') {
            bridge.setCurrentProject(project || null);
        }
    };
    const refreshCurrentProject = async () => {
        if (typeof bridge.refreshCurrentProject === 'function') {
            const refreshed = await bridge.refreshCurrentProject();
            if (refreshed) {
                standaloneProject = refreshed;
                return refreshed;
            }
        }
        const current = getCurrentProject();
        if (!current?.id) return current;
        try {
            const res = await fetch(routes.show(current.id), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (data?.id) {
                setStandaloneProject(data);
                return data;
            }
        } catch (_) {}
        return current;
    };

    const debounce = (fn, ms = 350) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    const state = {
        search: '',
        stageFilter: '',
        stateFilter: '',
        formDirty: false,
        formSnapshot: '',
        editingId: null,
        selectedTemplateId: null,
        formSteps: [],
        detailStageId: null,
        selectedStepId: null,
        editMode: false,
        existingConflictId: null,
        detailDirty: false,
        unsavedTarget: null,
        detailContext: null,
    };

    const els = {
        list: () => document.getElementById('ov-checklists-list'),
        count: () => document.getElementById('ov-checklists-count'),
        search: () => document.getElementById('ov-checklist-search'),
        stageFilter: () => document.getElementById('ov-checklist-stage-filter'),
        stateFilter: () => document.getElementById('ov-checklist-state-filter'),
        addBtn: () => document.getElementById('ov-add-checklist'),
        root: () => document.getElementById('checklist-modal-root'),
        detailRoot: () => document.getElementById('checklist-detail-root'),
        unsaved: () => document.getElementById('checklist-unsaved-modal'),
        form: () => document.getElementById('checklist-form'),
        formFields: () => document.getElementById('checklist-form-fields'),
        existsBox: () => document.getElementById('checklist-stage-exists-box'),
    };

    function stageLabel(type) {
        return i18n.stageLabels[type] || type || i18n.notSpecified;
    }

    function computeState(stage) {
        if (stage.is_overdue) return 'overdue';
        return stage.state || 'not_started';
    }

    function filteredStages(project) {
        const stages = project?.stages || [];
        const q = state.search.trim().toLowerCase();
        return stages.filter((s) => {
            if (state.stageFilter && s.stage_type !== state.stageFilter) return false;
            const st = computeState(s);
            if (state.stateFilter && st !== state.stateFilter) return false;
            if (!q) return true;
            const hay = [s.name, s.stage_type_label, s.responsible_name, stageLabel(s.stage_type)].join(' ').toLowerCase();
            return hay.includes(q);
        });
    }

    function progressText(stage) {
        const done = stage.steps_done ?? (stage.steps || []).filter((x) => x.result_status === 'done').length;
        const total = stage.steps_total ?? (stage.steps || []).length;
        return i18n.progress.replace(':done', done).replace(':total', total);
    }

    function renderEmpty(container) {
        container.innerHTML = `<div class="crm-empty-inline" style="margin:auto;text-align:center;padding:2rem 1rem">
            <div class="font-medium text-sm">${escapeHtml(i18n.emptyTitle)}</div>
            <div class="text-xs text-[var(--crm-muted)] mt-1 mb-3">${escapeHtml(i18n.emptyBody)}</div>
        </div>`;
    }

    function renderChecklists(project) {
        const list = els.list();
        if (!list) return;
        const stages = project?.stages || [];
        if (els.count()) els.count().textContent = String(stages.length);

        const filtered = filteredStages(project);
        if (!stages.length) {
            renderEmpty(list);
            return;
        }
        if (!filtered.length) {
            list.innerHTML = `<div class="crm-empty-inline text-sm text-[var(--crm-muted)]">${escapeHtml(i18n.emptyTitle)}</div>`;
            return;
        }

        list.innerHTML = filtered.map((s) => {
            const st = computeState(s);
            const pct = s.progress_percent ?? 0;
            const overdueCls = s.is_overdue ? 'crm-checklist-overdue' : '';
            return `<article class="crm-checklist-card" data-stage-id="${s.id}">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="crm-checklist-card-title truncate">${escapeHtml(s.name || stageLabel(s.stage_type))}</div>
                        <div class="crm-checklist-card-meta">
                            <div>${escapeHtml(i18n.stageMeta)}: ${escapeHtml(stageLabel(s.stage_type))}</div>
                            <div>${escapeHtml(i18n.responsible)}: ${escapeHtml(s.responsible_name || i18n.notSpecified)}</div>
                            <div class="${overdueCls}">${escapeHtml(i18n.deadline)}: ${escapeHtml(formatDate(s.deadline))}</div>
                            <div><span class="crm-status-badge">${escapeHtml(i18n.states[st] || st)}</span></div>
                        </div>
                        <div class="text-xs mt-1.5">${escapeHtml(progressText(s))}</div>
                        <div class="crm-checklist-progress" aria-hidden="true"><span style="width:${pct}%"></span></div>
                    </div>
                    <div class="shrink-0 flex gap-1" data-stop>
                        <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-open="${s.id}">${escapeHtml(i18n.open)}</button>
                        <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-del="${s.id}" title="${escapeHtml(i18n.del)}">✕</button>
                    </div>
                </div>
            </article>`;
        }).join('');

        list.querySelectorAll('.crm-checklist-card').forEach((card) => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-stop]')) return;
                openDetail(Number(card.dataset.stageId));
            });
        });
        list.querySelectorAll('[data-open]').forEach((btn) => {
            btn.addEventListener('click', (e) => { e.stopPropagation(); openDetail(Number(btn.dataset.open)); });
        });
        list.querySelectorAll('[data-del]').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (!confirm(i18n.delConfirm)) return;
                await deleteChecklist(Number(btn.dataset.del));
            });
        });
    }

    function populateFilters() {
        const stageSel = els.stageFilter();
        if (stageSel && !stageSel.dataset.init) {
            stageSel.dataset.init = '1';
            stageSel.innerHTML = `<option value="">${escapeHtml(@json(__('projects.checklist_filter_stage')))}</option>` +
                stageTypes.map((t) => `<option value="${escapeHtml(t)}">${escapeHtml(stageLabel(t))}</option>`).join('');
        }
        const stateSel = els.stateFilter();
        if (stateSel && !stateSel.dataset.init) {
            stateSel.dataset.init = '1';
            stateSel.innerHTML = `<option value="">${escapeHtml(@json(__('projects.checklist_filter_state')))}</option>` +
                Object.entries(i18n.states).map(([k, v]) => `<option value="${k}">${escapeHtml(v)}</option>`).join('');
        }
    }

    function openModal(root) {
        root?.classList.add('open');
        root?.setAttribute('aria-hidden', 'false');
    }
    function closeModal(root) {
        root?.classList.remove('open');
        root?.setAttribute('aria-hidden', 'true');
    }

    function readFormSnapshot() {
        return JSON.stringify({
            name: document.getElementById('checklist-name')?.value || '',
            stage: document.getElementById('checklist-stage-type')?.value || '',
            responsible: document.getElementById('checklist-responsible')?.value || '',
            deadline: document.getElementById('checklist-deadline')?.value || '',
            steps: state.formSteps,
            saveTpl: !!document.getElementById('checklist-save-template')?.checked,
            tplName: document.getElementById('checklist-template-name')?.value || '',
            templateId: state.selectedTemplateId,
        });
    }

    function markDirty() {
        state.formDirty = readFormSnapshot() !== state.formSnapshot;
    }

    function updateStepsCount() {
        const el = document.getElementById('checklist-steps-count');
        if (el) el.textContent = String(state.formSteps.filter((s) => String(s || '').trim() !== '').length || state.formSteps.length);
    }

    function fillResponsible() {
        const sel = document.getElementById('checklist-responsible');
        if (!sel) return;
        sel.innerHTML = users.map((u) => `<option value="${u.id}">${escapeHtml(u.name || u.email || ('#' + u.id))}</option>`).join('');
        if (users[0]) sel.value = String(users[0].id);
    }

    function fillStageSelect(disabledType) {
        const sel = document.getElementById('checklist-stage-type');
        if (!sel) return;
        sel.innerHTML = `<option value="">${escapeHtml(i18n.selectStage)}</option>` +
            stageTypes.map((t) => `<option value="${escapeHtml(t)}" ${disabledType === t ? 'disabled' : ''}>${escapeHtml(stageLabel(t))}</option>`).join('');
    }

    function closeOpenMenus(except) {
        document.querySelectorAll('.crm-checklist-menu').forEach((m) => {
            if (except && m === except) return;
            m.classList.add('hidden');
        });
    }

    function renderStepsEditor() {
        const box = document.getElementById('checklist-steps-editor');
        if (!box) return;
        updateStepsCount();
        if (!state.formSteps.length) {
            box.innerHTML = `<div class="text-xs text-[var(--crm-muted)] py-3">${escapeHtml(i18n.addAtLeastOne)}</div>`;
            return;
        }
        box.innerHTML = state.formSteps.map((title, idx) => `
            <div class="crm-checklist-step-row" data-idx="${idx}" draggable="true">
                <span class="crm-drag" title="drag">⋮⋮</span>
                <span class="crm-checklist-step-num">${idx + 1}.</span>
                <input type="text" class="crm-input" value="${escapeHtml(title)}" data-step-title placeholder="${escapeHtml(i18n.stepPlaceholder)}">
                <div class="crm-checklist-menu-wrap">
                    <button type="button" class="crm-checklist-menu-btn" data-step-menu aria-label="menu">⋯</button>
                    <div class="crm-checklist-menu hidden" data-step-menu-panel>
                        <button type="button" class="is-danger" data-rm>${escapeHtml(i18n.stepDelete)}</button>
                    </div>
                </div>
            </div>`).join('');

        box.querySelectorAll('[data-step-title]').forEach((inp) => {
            inp.addEventListener('input', () => {
                const idx = Number(inp.closest('[data-idx]')?.dataset.idx);
                state.formSteps[idx] = inp.value;
                updateStepsCount();
                markDirty();
            });
        });
        box.querySelectorAll('[data-step-menu]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = btn.parentElement?.querySelector('[data-step-menu-panel]');
                const open = panel && !panel.classList.contains('hidden');
                closeOpenMenus();
                if (panel && !open) panel.classList.remove('hidden');
            });
        });
        box.querySelectorAll('[data-rm]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.closest('[data-idx]')?.dataset.idx);
                state.formSteps.splice(idx, 1);
                renderStepsEditor();
                markDirty();
            });
        });

        let dragIdx = null;
        box.querySelectorAll('[data-idx]').forEach((row) => {
            row.addEventListener('dragstart', (e) => {
                if (e.target.closest('input,button')) { e.preventDefault(); return; }
                dragIdx = Number(row.dataset.idx);
                row.classList.add('opacity-50');
            });
            row.addEventListener('dragend', () => row.classList.remove('opacity-50'));
            row.addEventListener('dragover', (e) => e.preventDefault());
            row.addEventListener('drop', (e) => {
                e.preventDefault();
                const to = Number(row.dataset.idx);
                if (dragIdx === null || dragIdx === to) return;
                const [item] = state.formSteps.splice(dragIdx, 1);
                state.formSteps.splice(to, 0, item);
                renderStepsEditor();
                markDirty();
            });
        });
    }

    function applyTemplate(tpl) {
        if (!tpl) return;
        state.selectedTemplateId = tpl.id;
        state.formSteps = (tpl.steps || []).map((s) => String(s));
        const nameEl = document.getElementById('checklist-name');
        if (nameEl && (!nameEl.value.trim() || nameEl.dataset.fromTemplate === '1')) {
            nameEl.value = tpl.name || '';
            nameEl.dataset.fromTemplate = '1';
        }
        const type = document.getElementById('checklist-stage-type')?.value || tpl.type || '';
        renderTemplatesList(type);
        renderStepsEditor();
        markDirty();
    }

    function renderTemplatesList(type) {
        const box = document.getElementById('checklist-templates-list');
        if (!box) return;
        const list = (templates || []).filter((t) => !type || t.type === type);
        if (!list.length) {
            box.innerHTML = `<div class="text-xs text-[var(--crm-muted)]">${escapeHtml(i18n.noTemplates)}</div>`;
            return;
        }
        box.innerHTML = list.map((t) => {
            const count = Array.isArray(t.steps) ? t.steps.length : 0;
            const badge = t.is_owned ? i18n.tplMine : i18n.tplSystem;
            const isSelected = Number(state.selectedTemplateId) === Number(t.id);
            const selected = isSelected ? 'is-selected' : '';
            const action = isSelected
                ? `<span class="crm-checklist-selected-badge">${escapeHtml(i18n.selectedBadge)}</span>`
                : `<button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" data-use-tpl="${t.id}">${escapeHtml(i18n.useTemplate)}</button>`;
            const menu = t.is_owned ? `
                <div class="crm-checklist-menu-wrap">
                    <button type="button" class="crm-checklist-menu-btn" data-tpl-menu="${t.id}" aria-label="menu">⋯</button>
                    <div class="crm-checklist-menu hidden" data-tpl-menu-panel="${t.id}">
                        <button type="button" data-rename-tpl="${t.id}">${escapeHtml(i18n.tplRename)}</button>
                        <button type="button" data-use-tpl="${t.id}">${escapeHtml(i18n.tplEdit)}</button>
                        <button type="button" class="is-danger" data-del-tpl="${t.id}">${escapeHtml(i18n.tplDelete)}</button>
                    </div>
                </div>` : '';
            return `<div class="crm-checklist-tpl-row ${selected}" data-tpl="${t.id}">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-medium truncate">${escapeHtml(t.name)}</div>
                    <div class="text-xs text-[var(--crm-muted)]">${escapeHtml(i18n.stepsCount.replace(':count', count))} · <span class="crm-checklist-tpl-badge">${escapeHtml(badge)}</span></div>
                </div>
                ${action}
                ${menu}
            </div>`;
        }).join('');

        box.querySelectorAll('[data-use-tpl]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const tpl = templates.find((t) => Number(t.id) === Number(btn.dataset.useTpl));
                applyTemplate(tpl);
                closeOpenMenus();
            });
        });
        box.querySelectorAll('[data-tpl-menu]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.tplMenu;
                const panel = box.querySelector(`[data-tpl-menu-panel="${id}"]`);
                const open = panel && !panel.classList.contains('hidden');
                closeOpenMenus();
                if (panel && !open) panel.classList.remove('hidden');
            });
        });
        box.querySelectorAll('[data-rename-tpl]').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                closeOpenMenus();
                const tpl = templates.find((t) => Number(t.id) === Number(btn.dataset.renameTpl));
                if (!tpl?.is_owned) return;
                const name = prompt(@json(__('projects.template_name_placeholder')), tpl.name || '');
                if (!name || !name.trim()) return;
                // Rename = recreate owned template with new name + same steps, then delete old (keeps business rules)
                const res = await fetch(routes.saveTemplate, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: JSON.stringify({ name: name.trim(), type: tpl.type, steps: tpl.steps || [] }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.template) { toast(data.message || i18n.error, 'error'); return; }
                await fetch(routes.deleteTemplate(tpl.id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                templates = templates.filter((t) => Number(t.id) !== Number(tpl.id));
                templates.unshift(data.template);
                if (Number(state.selectedTemplateId) === Number(tpl.id)) state.selectedTemplateId = data.template.id;
                renderTemplatesList(type);
                toast(i18n.templateSaved, 'success');
            });
        });
        box.querySelectorAll('[data-del-tpl]').forEach((btn) => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                closeOpenMenus();
                const tpl = templates.find((t) => Number(t.id) === Number(btn.dataset.delTpl));
                if (!tpl?.is_owned) { toast(i18n.templateDeleteOnlyOwn, 'error'); return; }
                if (!confirm(i18n.deleteTemplateConfirm)) return;
                const res = await fetch(routes.deleteTemplate(tpl.id), {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) { toast(data.message || i18n.error, 'error'); return; }
                templates = templates.filter((t) => Number(t.id) !== Number(tpl.id));
                if (Number(state.selectedTemplateId) === Number(tpl.id)) state.selectedTemplateId = null;
                renderTemplatesList(type);
                toast(i18n.templateDeleted, 'success');
            });
        });
    }

    function findExistingByType(type) {
        const project = getCurrentProject();
        return (project?.stages || []).find((s) => s.stage_type === type) || null;
    }

    function onStageTypeChange() {
        const type = document.getElementById('checklist-stage-type')?.value || '';
        const exists = type ? findExistingByType(type) : null;
        const box = els.existsBox();
        const fields = els.formFields();
        const footer = document.getElementById('checklist-create-footer');

        if (exists && !state.editingId) {
            state.existingConflictId = exists.id;
            box?.classList.remove('hidden');
            fields?.classList.add('hidden');
            footer?.classList.add('hidden');
            const text = document.getElementById('checklist-stage-exists-text');
            if (text) text.textContent = i18n.stageExists.replace(':stage', stageLabel(type));
            return;
        }

        state.existingConflictId = null;
        box?.classList.add('hidden');
        fields?.classList.remove('hidden');
        footer?.classList.remove('hidden');
        state.selectedTemplateId = null;
        state.formSteps = [];
        const nameEl = document.getElementById('checklist-name');
        if (nameEl && (!nameEl.value.trim() || nameEl.dataset.fromTemplate === '1')) {
            nameEl.value = type ? stageLabel(type) : '';
            nameEl.dataset.fromTemplate = '1';
        }
        renderTemplatesList(type);
        renderStepsEditor();
        markDirty();
    }

    function openCreate() {
        const project = getCurrentProject();
        if (!project?.id) return;
        state.editingId = null;
        state.formDirty = false;
        state.selectedTemplateId = null;
        state.formSteps = [];
        state.existingConflictId = null;
        const typeSel = document.getElementById('checklist-stage-type');
        if (typeSel) typeSel.disabled = false;
        document.getElementById('checklist-editing-id').value = '';
        document.getElementById('checklist-modal-title').textContent = i18n.newTitle;
        document.getElementById('checklist-modal-subtitle').textContent = i18n.subtitle.replace(':name', project.name || '');
        document.getElementById('checklist-submit').textContent = i18n.create;
        document.getElementById('checklist-save-template').checked = false;
        document.getElementById('checklist-template-name-wrap').classList.add('hidden');
        document.getElementById('checklist-template-name').value = '';
        document.getElementById('checklist-deadline').value = '';
        const nameEl = document.getElementById('checklist-name');
        if (nameEl) {
            nameEl.value = '';
            nameEl.dataset.fromTemplate = '0';
        }
        fillStageSelect();
        fillResponsible();
        els.existsBox()?.classList.add('hidden');
        els.formFields()?.classList.remove('hidden');
        document.getElementById('checklist-create-footer')?.classList.remove('hidden');
        renderTemplatesList('');
        renderStepsEditor();
        state.formSnapshot = readFormSnapshot();
        openModal(els.root());
    }

    async function maybeSaveTemplate(type, steps) {
        const save = document.getElementById('checklist-save-template')?.checked;
        if (!save) return null;
        const name = (document.getElementById('checklist-template-name')?.value || '').trim();
        if (!name || !steps.length) return null;
        const res = await fetch(routes.saveTemplate, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body: JSON.stringify({ name, type, steps }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
        if (data.template) {
            templates = [data.template, ...templates.filter((t) => Number(t.id) !== Number(data.template.id))];
            state.selectedTemplateId = data.template.id;
        }
        return data.template || null;
    }

    async function persistStages(stagesPayload) {
        const project = getCurrentProject();
        if (!project?.id) throw new Error(i18n.error);

        // JSON so empty `stages: []` is always sent (FormData omits it → delete no-ops).
        const body = {
            name: project.name || '',
            status: project.status || '',
            client_id: project.client_id || null,
            start_date: project.start_date || null,
            planned_end_date: project.planned_end_date || null,
            comment: project.comment || null,
            repair_budget_planned: project.repair_budget_planned ?? null,
            repair_budget_actual: project.repair_budget_actual ?? null,
            stages: (stagesPayload || []).map((stage) => ({
                id: stage.id || undefined,
                stage_type: stage.stage_type,
                name: stage.name || null,
                template_id: stage.template_id || null,
                deadline: stage.deadline || null,
                responsible_id: stage.responsible_id || null,
                assign_task: !!stage.assign_task,
                steps: (stage.steps || []).map((step) => {
                    if (typeof step === 'string') {
                        return { title: step, result_status: 'pending' };
                    }
                    return {
                        id: step.id || undefined,
                        title: step.title || '',
                        deadline: step.deadline || null,
                        responsible_id: step.responsible_id || null,
                        link: step.link || null,
                        result_status: step.result_status || 'pending',
                        result_comment: step.result_comment || null,
                    };
                }),
            })),
        };

        const res = await fetch(routes.update(project.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = data.message || Object.values(data.errors || {})[0]?.[0] || i18n.error;
            throw new Error(msg);
        }
        return (await refreshCurrentProject()) || data.project;
    }

    function stagesPayloadFromProject(project, mutator) {
        const stages = (project?.stages || []).map((s) => ({
            id: s.id,
            stage_type: s.stage_type,
            name: s.custom_name || s.name || '',
            template_id: s.template_id,
            deadline: s.deadline,
            responsible_id: s.responsible_id,
            assign_task: s.assign_task,
            steps: (s.steps || []).map((st) => ({
                id: st.id,
                title: st.title,
                deadline: st.deadline,
                responsible_id: st.responsible_id,
                link: st.link,
                result_status: st.result_status || 'pending',
                result_comment: st.result_comment,
            })),
        }));
        return typeof mutator === 'function' ? mutator(stages) : stages;
    }

    async function submitCreate() {
        if (state.editingId) {
            await submitEdit(state.editingId);
            return;
        }
        const project = getCurrentProject();
        const type = document.getElementById('checklist-stage-type')?.value || '';
        if (!type) {
            toast(i18n.selectStage, 'error');
            return;
        }
        if (findExistingByType(type)) {
            onStageTypeChange();
            return;
        }
        const steps = state.formSteps.map((s) => s.trim()).filter(Boolean);
        if (!steps.length) {
            toast(i18n.addAtLeastOne, 'error');
            return;
        }

        const btn = document.getElementById('checklist-submit');
        btn.disabled = true;
        try {
            const tpl = await maybeSaveTemplate(type, steps);
            const payload = stagesPayloadFromProject(project, (stages) => {
                stages.push({
                    stage_type: type,
                    name: (document.getElementById('checklist-name')?.value || '').trim() || stageLabel(type),
                    template_id: tpl?.id || state.selectedTemplateId || null,
                    deadline: document.getElementById('checklist-deadline')?.value || null,
                    responsible_id: document.getElementById('checklist-responsible')?.value || null,
                    assign_task: false,
                    steps: steps.map((title) => ({ title, result_status: 'pending' })),
                });
                return stages;
            });
            const refreshed = await persistStages(payload);
            state.formDirty = false;
            closeModal(els.root());
            renderChecklists(refreshed || await refreshCurrentProject());
            toast(i18n.created, 'success');
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async function deleteChecklist(stageId) {
        const project = getCurrentProject();
        try {
            const payload = stagesPayloadFromProject(project, (stages) => stages.filter((s) => Number(s.id) !== Number(stageId)));
            const refreshed = await persistStages(payload);
            renderChecklists(refreshed || await refreshCurrentProject());
            toast(i18n.deleted, 'success');
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    function getStage(stageId) {
        return (getCurrentProject()?.stages || []).find((s) => Number(s.id) === Number(stageId)) || null;
    }

    function openDetail(stageId, opts = {}) {
        const stage = getStage(stageId);
        if (!stage) return false;
        const project = getCurrentProject();
        state.detailStageId = Number(stageId);
        state.selectedStepId = null;
        state.editMode = false;
        state.detailDirty = false;
        state.detailContext = {
            projectId: project?.id ? Number(project.id) : null,
            checklistId: Number(stageId),
            stepId: opts.stepId ? Number(opts.stepId) : null,
        };
        document.getElementById('checklist-detail-title').textContent = stage.name || stageLabel(stage.stage_type);
        const projectName = project?.name ? String(project.name) : '';
        document.getElementById('checklist-detail-meta').textContent = [
            projectName || null,
            stageLabel(stage.stage_type),
            `${i18n.responsible}: ${stage.responsible_name || i18n.notSpecified}`,
            `${i18n.deadline}: ${formatDate(stage.deadline)}`,
        ].filter(Boolean).join(' · ');

        const openProjectBtn = document.getElementById('checklist-detail-open-project');
        if (openProjectBtn) {
            if (project?.id) {
                const q = new URLSearchParams({
                    open: String(project.id),
                    tab: 'checklists',
                    checklist: String(stageId),
                });
                if (opts.stepId) q.set('step', String(opts.stepId));
                openProjectBtn.href = @json(url('/projects')) + '?' + q.toString();
                openProjectBtn.classList.remove('hidden');
            } else {
                openProjectBtn.href = '#';
                openProjectBtn.classList.add('hidden');
            }
        }

        renderDetailProgress(stage);
        renderDetailSteps(stage);
        showResultPanel(null);
        openModal(els.detailRoot());

        const wantStep = opts.stepId ? Number(opts.stepId) : null;
        if (wantStep && (stage.steps || []).some((s) => Number(s.id) === wantStep)) {
            selectStep(wantStep, { scroll: true });
            if (state.detailContext) state.detailContext.stepId = wantStep;
        }
        return true;
    }

    async function openByIds({ projectId, checklistId, stepId } = {}) {
        const pid = Number(projectId);
        const cid = Number(checklistId);
        if (!pid || !cid) {
            toast(i18n.notFound, 'error');
            return false;
        }

        let project = getCurrentProject();
        if (!project || Number(project.id) !== pid) {
            try {
                const res = await fetch(routes.show(pid), { headers: { Accept: 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data?.id) {
                    toast(data.message || i18n.notFound, 'error');
                    return false;
                }
                setStandaloneProject(data);
                project = data;
            } catch (e) {
                toast(e.message || i18n.notFound, 'error');
                return false;
            }
        }

        const stage = (project.stages || []).find((s) => Number(s.id) === cid);
        if (!stage) {
            toast(i18n.notFound, 'error');
            return false;
        }

        let resolvedStep = stepId ? Number(stepId) : null;
        if (resolvedStep && !(stage.steps || []).some((s) => Number(s.id) === resolvedStep)) {
            resolvedStep = null;
        }

        return openDetail(cid, { stepId: resolvedStep });
    }

    function renderDetailProgress(stage) {
        const box = document.getElementById('checklist-detail-progress');
        if (!box) return;
        const pct = stage.progress_percent ?? 0;
        box.innerHTML = `<div class="text-xs mb-1">${escapeHtml(progressText(stage))} (${pct}%)</div>
            <div class="crm-checklist-progress"><span style="width:${pct}%"></span></div>`;
    }

    function renderDetailSteps(stage) {
        const box = document.getElementById('checklist-detail-steps');
        if (!box) return;
        const steps = stage.steps || [];
        box.innerHTML = steps.map((st) => {
            const done = st.result_status === 'done';
            const has = st.has_result || (st.result_comment && String(st.result_comment).trim());
            const active = Number(state.selectedStepId) === Number(st.id) ? 'is-active' : '';
            return `<div class="crm-checklist-step-item ${active} ${has ? 'has-result' : ''}" data-step="${st.id}">
                <input type="checkbox" ${done ? 'checked' : ''} data-toggle="${st.id}">
                <span class="crm-checklist-result-dot" title="${has ? escapeHtml(i18n.resultSaved) : escapeHtml(i18n.noResult)}"></span>
                <div class="min-w-0 flex-1 text-sm">${escapeHtml(st.title || '')}</div>
            </div>`;
        }).join('') || `<div class="text-xs text-[var(--crm-muted)]">—</div>`;

        box.querySelectorAll('[data-step]').forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.target.closest('[data-toggle]')) return;
                selectStep(Number(row.dataset.step));
            });
        });
        box.querySelectorAll('[data-toggle]').forEach((cb) => {
            cb.addEventListener('change', async (e) => {
                e.stopPropagation();
                const stepId = Number(cb.dataset.toggle);
                const step = (getStage(state.detailStageId)?.steps || []).find((x) => Number(x.id) === stepId);
                await updateStep(stepId, cb.checked ? 'done' : 'pending', step?.result_comment || '');
            });
        });
    }

    function isDetailDirty() {
        if (!state.selectedStepId) return false;
        const stage = getStage(state.detailStageId);
        const step = (stage?.steps || []).find((x) => Number(x.id) === Number(state.selectedStepId));
        const ta = document.getElementById('checklist-detail-result');
        if (!ta || !step) return false;
        return String(ta.value || '') !== String(step.result_comment || '');
    }

    async function selectStep(stepId, opts = {}) {
        if (state.selectedStepId && Number(state.selectedStepId) !== Number(stepId) && isDetailDirty()) {
            await autosaveSelectedResult();
        }
        state.selectedStepId = stepId;
        state.detailDirty = false;
        if (state.detailContext) state.detailContext.stepId = Number(stepId);
        const stage = getStage(state.detailStageId);
        const step = (stage?.steps || []).find((x) => Number(x.id) === Number(stepId));
        renderDetailSteps(stage);
        showResultPanel(step);
        if (opts.scroll) {
            requestAnimationFrame(() => {
                const row = document.querySelector(`#checklist-detail-steps [data-step="${stepId}"]`);
                row?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        }
    }

    function showResultPanel(step) {
        const empty = document.getElementById('checklist-detail-result-empty');
        const box = document.getElementById('checklist-detail-result-box');
        if (!step) {
            empty?.classList.remove('hidden');
            box?.classList.add('hidden');
            return;
        }
        empty?.classList.add('hidden');
        box?.classList.remove('hidden');
        document.getElementById('checklist-detail-step-title').textContent = step.title || '';
        const ta = document.getElementById('checklist-detail-result');
        if (ta && document.activeElement !== ta) {
            ta.value = step.result_comment || '';
        }
        setResultHint(step.has_result || step.result_comment ? i18n.resultSaved : i18n.noResult);
    }

    function setResultHint(text) {
        const hint = document.getElementById('checklist-detail-result-hint');
        if (hint) hint.textContent = text || '';
    }

    function closeDetail(force, opts = {}) {
        if (!force && isDetailDirty()) {
            state.unsavedTarget = 'detail';
            openModal(els.unsaved());
            return;
        }
        state.detailDirty = false;
        state.detailStageId = null;
        state.selectedStepId = null;
        state.detailContext = null;
        closeModal(els.detailRoot());
        closeModal(els.unsaved());
        if (!opts.skipClosedHook && typeof bridge.onChecklistDetailClosed === 'function') {
            bridge.onChecklistDetailClosed();
        }
    }

    async function updateStep(stepId, status, comment, { preserveEditor = false } = {}) {
        try {
            const res = await fetch(routes.stepUpdate(stepId), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ result_status: status, result_comment: comment }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);

            const project = getCurrentProject();
            const stage = (project?.stages || []).find((s) => Number(s.id) === Number(state.detailStageId));
            const step = (stage?.steps || []).find((x) => Number(x.id) === Number(stepId));
            if (step) {
                step.result_status = data.step?.result_status || status;
                step.result_comment = data.step?.result_comment ?? comment;
                step.has_result = !!(step.result_comment && String(step.result_comment).trim());
            }
            state.detailDirty = false;
            if (stage) {
                const total = (stage.steps || []).length;
                const done = (stage.steps || []).filter((x) => x.result_status === 'done').length;
                stage.steps_total = total;
                stage.steps_done = done;
                stage.progress_percent = total ? Math.round((done / total) * 100) : 0;
                stage.state = stage.progress_percent >= 100 ? 'done' : (done > 0 ? 'in_progress' : 'not_started');
                renderDetailProgress(stage);
                renderDetailSteps(stage);
                if (Number(state.selectedStepId) === Number(stepId)) {
                    if (preserveEditor) {
                        setResultHint(step?.has_result ? i18n.resultSaved : i18n.noResult);
                    } else {
                        showResultPanel(step);
                    }
                }
            }
            renderChecklists(project);
            if (typeof bridge.refreshCurrentProject === 'function') {
                refreshCurrentProject().then((p) => { if (p) renderChecklists(p); });
            }
        } catch (e) {
            toast(e.message || i18n.error, 'error');
            setResultHint(i18n.error);
            const stage = getStage(state.detailStageId);
            if (stage) renderDetailSteps(stage);
        }
    }

    async function autosaveSelectedResult() {
        const stepId = state.selectedStepId;
        if (!stepId) return;
        const stage = getStage(state.detailStageId);
        const step = (stage?.steps || []).find((x) => Number(x.id) === Number(stepId));
        const comment = document.getElementById('checklist-detail-result')?.value || '';
        if ((step?.result_comment || '') === comment) {
            setResultHint(comment.trim() ? i18n.resultSaved : i18n.noResult);
            return;
        }
        const status = step?.result_status === 'done' ? 'done' : 'pending';
        setResultHint(i18n.resultSaving);
        await updateStep(stepId, status, comment, { preserveEditor: true });
    }

    const autosaveResult = debounce(() => { autosaveSelectedResult(); }, 450);

    function closeCreate(force) {
        if (!force && state.formDirty) {
            state.unsavedTarget = 'create';
            openModal(els.unsaved());
            return;
        }
        state.formDirty = false;
        state.unsavedTarget = null;
        closeModal(els.root());
        closeModal(els.unsaved());
    }

    function bindEvents() {
        els.addBtn()?.addEventListener('click', (e) => { e.preventDefault(); openCreate(); });
        document.getElementById('checklist-close')?.addEventListener('click', () => closeCreate(false));
        document.getElementById('checklist-cancel')?.addEventListener('click', () => closeCreate(false));
        els.root()?.querySelector('[data-checklist-close-backdrop]')?.addEventListener('click', () => closeCreate(false));
        document.getElementById('checklist-unsaved-continue')?.addEventListener('click', () => closeModal(els.unsaved()));
        document.getElementById('checklist-unsaved-leave')?.addEventListener('click', () => {
            const target = state.unsavedTarget;
            state.unsavedTarget = null;
            if (target === 'detail') closeDetail(true);
            else closeCreate(true);
        });

        document.getElementById('checklist-stage-type')?.addEventListener('change', onStageTypeChange);
        document.getElementById('checklist-responsible')?.addEventListener('change', markDirty);
        document.getElementById('checklist-deadline')?.addEventListener('change', markDirty);
        document.getElementById('checklist-name')?.addEventListener('input', () => {
            const el = document.getElementById('checklist-name');
            if (el) el.dataset.fromTemplate = '0';
            markDirty();
        });
        document.addEventListener('click', () => closeOpenMenus());
        document.getElementById('checklist-add-step')?.addEventListener('click', () => {
            state.formSteps.push('');
            renderStepsEditor();
            markDirty();
        });
        document.getElementById('checklist-use-empty')?.addEventListener('click', () => {
            state.selectedTemplateId = null;
            state.formSteps = [''];
            const nameEl = document.getElementById('checklist-name');
            const type = document.getElementById('checklist-stage-type')?.value || '';
            if (nameEl && (!nameEl.value.trim() || nameEl.dataset.fromTemplate === '1')) {
                nameEl.value = type ? stageLabel(type) : '';
                nameEl.dataset.fromTemplate = '1';
            }
            renderTemplatesList(type);
            renderStepsEditor();
            markDirty();
        });
        document.getElementById('checklist-save-template')?.addEventListener('change', (e) => {
            document.getElementById('checklist-template-name-wrap')?.classList.toggle('hidden', !e.target.checked);
            markDirty();
        });
        document.getElementById('checklist-template-name')?.addEventListener('input', markDirty);
        document.getElementById('checklist-submit')?.addEventListener('click', (e) => { e.preventDefault(); submitCreate(); });
        document.getElementById('checklist-open-existing')?.addEventListener('click', () => {
            const id = state.existingConflictId;
            closeCreate(true);
            if (id) openDetail(id);
        });
        document.getElementById('checklist-cancel-create')?.addEventListener('click', () => closeCreate(true));

        document.getElementById('checklist-detail-close')?.addEventListener('click', () => closeDetail(false));
        els.detailRoot()?.querySelector('[data-checklist-detail-backdrop]')?.addEventListener('click', () => closeDetail(false));
        document.getElementById('checklist-detail-result')?.addEventListener('input', () => {
            state.detailDirty = isDetailDirty();
            setResultHint(i18n.resultSaving);
            autosaveResult();
        });
        document.getElementById('checklist-detail-result')?.addEventListener('blur', () => {
            autosaveSelectedResult();
        });
        document.getElementById('checklist-detail-edit')?.addEventListener('click', () => {
            const stage = getStage(state.detailStageId);
            if (!stage) return;
            closeModal(els.detailRoot());
            openCreate();
            state.editingId = stage.id;
            document.getElementById('checklist-editing-id').value = String(stage.id);
            document.getElementById('checklist-modal-title').textContent = i18n.edit;
            document.getElementById('checklist-submit').textContent = @json(__('projects.save'));
            fillStageSelect();
            const typeSel = document.getElementById('checklist-stage-type');
            if (typeSel) {
                typeSel.value = stage.stage_type;
                typeSel.disabled = true;
            }
            els.existsBox()?.classList.add('hidden');
            els.formFields()?.classList.remove('hidden');
            document.getElementById('checklist-create-footer')?.classList.remove('hidden');
            fillResponsible();
            const resp = document.getElementById('checklist-responsible');
            if (resp && stage.responsible_id) resp.value = String(stage.responsible_id);
            const dl = document.getElementById('checklist-deadline');
            if (dl) dl.value = stage.deadline || '';
            const nameEl = document.getElementById('checklist-name');
            if (nameEl) {
                nameEl.value = stage.custom_name || stage.name || stageLabel(stage.stage_type);
                nameEl.dataset.fromTemplate = '0';
            }
            state.selectedTemplateId = stage.template_id;
            state.formSteps = (stage.steps || []).map((s) => s.title || '');
            renderTemplatesList(stage.stage_type);
            renderStepsEditor();
            state.formSnapshot = readFormSnapshot();
            state.formDirty = false;
        });

        const searchEl = els.search();
        searchEl?.addEventListener('input', debounce(() => {
            state.search = searchEl.value || '';
            renderChecklists(getCurrentProject());
        }, 350));
        els.stageFilter()?.addEventListener('change', () => {
            state.stageFilter = els.stageFilter().value || '';
            renderChecklists(getCurrentProject());
        });
        els.stateFilter()?.addEventListener('change', () => {
            state.stateFilter = els.stateFilter().value || '';
            renderChecklists(getCurrentProject());
        });
    }

    async function submitEdit(stageId) {
        const project = getCurrentProject();
        const type = document.getElementById('checklist-stage-type')?.value || '';
        const steps = state.formSteps.map((s) => s.trim()).filter(Boolean);
        if (!steps.length) { toast(i18n.addAtLeastOne, 'error'); return; }
        const btn = document.getElementById('checklist-submit');
        btn.disabled = true;
        try {
            await maybeSaveTemplate(type, steps);
            const existing = getStage(stageId);
            const payload = stagesPayloadFromProject(project, (stages) => stages.map((s) => {
                if (Number(s.id) !== Number(stageId)) return s;
                const oldSteps = [...(existing?.steps || [])];
                const mapped = steps.map((title) => {
                    const foundIdx = oldSteps.findIndex((os) => os.title === title);
                    if (foundIdx >= 0) {
                        const found = oldSteps.splice(foundIdx, 1)[0];
                        return { ...found, title };
                    }
                    return { title, result_status: 'pending' };
                });
                return {
                    ...s,
                    name: (document.getElementById('checklist-name')?.value || '').trim() || s.name || stageLabel(type),
                    deadline: document.getElementById('checklist-deadline')?.value || null,
                    responsible_id: document.getElementById('checklist-responsible')?.value || null,
                    template_id: state.selectedTemplateId || s.template_id,
                    steps: mapped,
                };
            }));
            const refreshed = await persistStages(payload);
            state.formDirty = false;
            state.editingId = null;
            const typeSel = document.getElementById('checklist-stage-type');
            if (typeSel) typeSel.disabled = false;
            closeModal(els.root());
            renderChecklists(refreshed || await refreshCurrentProject());
            toast(i18n.saved, 'success');
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function onProjectOpened(project) {
        populateFilters();
        const addBtn = els.addBtn();
        if (addBtn) {
            if (project?.id) addBtn.classList.remove('pointer-events-none', 'opacity-50');
            else addBtn.classList.add('pointer-events-none', 'opacity-50');
        }
        renderChecklists(project);
    }

    // Portal modals to body
    ['checklist-modal-root', 'checklist-detail-root', 'checklist-unsaved-modal'].forEach((id) => {
        const el = document.getElementById(id);
        if (el && el.parentElement !== document.body) document.body.appendChild(el);
    });

    populateFilters();
    bindEvents();

    window.CrmChecklists = {
        renderChecklists,
        openCreate,
        openDetail,
        openByIds,
        closeDetail,
        onProjectOpened,
    };
})();
</script>
