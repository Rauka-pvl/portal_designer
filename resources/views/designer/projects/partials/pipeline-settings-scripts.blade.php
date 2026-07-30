{{-- Pipeline settings draft editor — uses window.CrmPipelineSettings API from crm.blade --}}
@if ($canManage ?? false)
<script>
(function () {
    'use strict';

    const cfg = window.CrmPipelineSettingsConfig;
    if (!cfg) return;

    const {
        csrf, routes, toast, escapeHtml, getProjects, getPipeline, setPipeline, renderBoard, i18n,
    } = cfg;

    const icons = {
        grip: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/></svg>`,
        more: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>`,
        trash: `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>`,
    };

    let draft = [];
    let snapshot = '';
    let tempSeq = 0;
    let dragKey = null;
    let pendingDeleteKey = null;
    let openMenuKey = null;

    const els = {
        root: () => document.getElementById('pipeline-modal'),
        list: () => document.getElementById('pipeline-stages-list'),
        unsaved: () => document.getElementById('pipeline-unsaved-modal'),
        move: () => document.getElementById('pipeline-move-modal'),
        confirm: () => document.getElementById('pipeline-confirm-modal'),
        newName: () => document.getElementById('pipeline-new-name'),
        newColor: () => document.getElementById('pipeline-new-color'),
        newSwatch: () => document.getElementById('pipeline-new-swatch'),
        newError: () => document.getElementById('pipeline-new-error'),
        saveBtn: () => document.getElementById('pipeline-save'),
    };

    const openModal = (el) => { el?.classList.add('open'); el?.setAttribute('aria-hidden', 'false'); };
    const closeModal = (el) => { el?.classList.remove('open'); el?.setAttribute('aria-hidden', 'true'); };

    function cloneStages(stages) {
        return (stages || []).map((s, i) => ({
            key: s.id ? `id:${s.id}` : `tmp:${s.temp_id || (++tempSeq)}`,
            id: s.id ? Number(s.id) : null,
            temp_id: s.id ? null : (s.temp_id || `new_${++tempSeq}`),
            name: String(s.name || ''),
            color: normalizeColor(s.color || '#64748b'),
            system_key: s.system_key || null,
            is_system: !!s.is_system,
            markedDelete: false,
            moveToId: null,
            error: '',
            position: i,
        }));
    }

    function normalizeColor(c) {
        const v = String(c || '#64748b').trim();
        if (/^#[0-9a-fA-F]{6}$/.test(v)) return v.toLowerCase();
        if (/^#[0-9a-fA-F]{3}$/.test(v)) {
            return ('#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]).toLowerCase();
        }
        return '#64748b';
    }

    function draftSnapshot() {
        return JSON.stringify(draft.map((s) => ({
            id: s.id,
            temp_id: s.temp_id,
            name: s.name,
            color: s.color,
            markedDelete: s.markedDelete,
            moveToId: s.moveToId,
        })));
    }

    function isDirty() {
        return draftSnapshot() !== snapshot;
    }

    function cardCount(stage) {
        if (!stage.system_key) return 0;
        return (getProjects() || []).filter((p) => p.status === stage.system_key).length;
    }

    function activeStages() {
        return draft.filter((s) => !s.markedDelete);
    }

    function resetFromPipeline() {
        draft = cloneStages(getPipeline()?.stages || []);
        snapshot = draftSnapshot();
        openMenuKey = null;
        pendingDeleteKey = null;
        const err = els.newError();
        if (err) { err.textContent = ''; err.classList.add('hidden'); }
        const name = els.newName();
        if (name) name.value = '';
        const color = els.newColor();
        if (color) color.value = '#64748b';
        syncNewSwatch();
        renderList();
    }

    function syncNewSwatch() {
        const color = els.newColor()?.value || '#64748b';
        const face = els.newSwatch();
        if (face) face.style.setProperty('--swatch', color);
    }

    function renderList() {
        const list = els.list();
        if (!list) return;
        list.innerHTML = draft.map((s) => rowHtml(s)).join('') || `<div class="text-sm text-[var(--crm-muted)] py-4 text-center">—</div>`;
        bindRows(list);
    }

    function rowHtml(s) {
        const delClass = s.markedDelete ? ' is-pending-delete' : '';
        const dragAttr = s.markedDelete ? '' : ' draggable="true"';
        return `<div class="crm-pipeline-row${delClass}" data-key="${escapeHtml(s.key)}"${dragAttr}>
            <button type="button" class="crm-pipeline-drag" data-act="drag" aria-label="${escapeHtml(i18n.drag)}" title="${escapeHtml(i18n.drag)}" ${s.markedDelete ? 'disabled' : ''}>${icons.grip}</button>
            <label class="crm-pipeline-swatch" title="${escapeHtml(i18n.color)}">
                <input type="color" value="${escapeHtml(s.color)}" data-act="color" aria-label="${escapeHtml(i18n.color)}" ${s.markedDelete ? 'disabled' : ''}>
                <span class="crm-pipeline-swatch-face" style="--swatch:${escapeHtml(s.color)}"></span>
            </label>
            <div class="crm-pipeline-row-main">
                <input type="text" class="crm-input crm-pipeline-name-input" maxlength="120" value="${escapeHtml(s.name)}" data-act="name" placeholder="${escapeHtml(i18n.namePlaceholder)}" ${s.markedDelete ? 'disabled' : ''} autocomplete="off">
                ${s.markedDelete ? `<div class="crm-pipeline-pending">${escapeHtml(i18n.deleteMarked)}</div>` : ''}
                ${s.error ? `<div class="crm-pipeline-row-error">${escapeHtml(s.error)}</div>` : ''}
            </div>
            <div class="crm-pipeline-row-actions">
                <button type="button" class="crm-pipeline-icon-btn" data-act="menu" aria-label="${escapeHtml(i18n.more)}" title="${escapeHtml(i18n.more)}">${icons.more}</button>
                <div class="crm-pipeline-menu ${openMenuKey === s.key ? 'open' : ''}" data-menu>
                    ${s.markedDelete
                        ? `<button type="button" data-act="undo-del">${escapeHtml(i18n.undoDelete)}</button>`
                        : `<button type="button" data-act="del" class="is-danger">${icons.trash}<span>${escapeHtml(i18n.deleteStage)}</span></button>`}
                </div>
            </div>
            <span class="crm-pipeline-accent" style="background:${escapeHtml(s.color)}"></span>
        </div>`;
    }

    function bindRows(list) {
        list.querySelectorAll('.crm-pipeline-row').forEach((row) => {
            const key = row.dataset.key;
            const stage = draft.find((x) => x.key === key);
            if (!stage) return;

            const nameInput = row.querySelector('[data-act="name"]');
            nameInput?.addEventListener('input', (e) => {
                stage.name = e.target.value;
                stage.error = '';
            });
            nameInput?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') e.preventDefault();
            });

            const colorInput = row.querySelector('[data-act="color"]');
            colorInput?.addEventListener('input', (e) => {
                stage.color = normalizeColor(e.target.value);
                const face = row.querySelector('.crm-pipeline-swatch-face');
                if (face) face.style.setProperty('--swatch', stage.color);
                const accent = row.querySelector('.crm-pipeline-accent');
                if (accent) accent.style.background = stage.color;
            });

            row.querySelector('[data-act="menu"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                openMenuKey = openMenuKey === key ? null : key;
                renderList();
            });

            row.querySelector('[data-act="del"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                openMenuKey = null;
                requestDelete(stage);
            });

            row.querySelector('[data-act="undo-del"]')?.addEventListener('click', (e) => {
                e.stopPropagation();
                stage.markedDelete = false;
                stage.moveToId = null;
                openMenuKey = null;
                renderList();
            });

            const dragBtn = row.querySelector('[data-act="drag"]');
            dragBtn?.addEventListener('mousedown', () => { row.dataset.canDrag = '1'; });
            dragBtn?.addEventListener('mouseup', () => { row.dataset.canDrag = '0'; });
            dragBtn?.addEventListener('mouseleave', () => { row.dataset.canDrag = '0'; });

            row.addEventListener('dragstart', (e) => {
                if (row.dataset.canDrag !== '1' || stage.markedDelete) {
                    e.preventDefault();
                    return;
                }
                dragKey = key;
                row.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', key);
            });
            row.addEventListener('dragend', () => {
                dragKey = null;
                row.dataset.canDrag = '0';
                row.classList.remove('is-dragging');
                list.querySelectorAll('.is-drop-target').forEach((el) => el.classList.remove('is-drop-target'));
            });
            row.addEventListener('dragover', (e) => {
                if (!dragKey || dragKey === key || stage.markedDelete) return;
                e.preventDefault();
                row.classList.add('is-drop-target');
            });
            row.addEventListener('dragleave', () => row.classList.remove('is-drop-target'));
            row.addEventListener('drop', (e) => {
                e.preventDefault();
                row.classList.remove('is-drop-target');
                const fromKey = dragKey || e.dataTransfer.getData('text/plain');
                if (!fromKey || fromKey === key) return;
                reorderDraft(fromKey, key);
                renderList();
            });
        });
    }

    function reorderDraft(fromKey, toKey) {
        const fromIdx = draft.findIndex((s) => s.key === fromKey);
        const toIdx = draft.findIndex((s) => s.key === toKey);
        if (fromIdx < 0 || toIdx < 0) return;
        const [item] = draft.splice(fromIdx, 1);
        draft.splice(toIdx, 0, item);
    }

    function requestDelete(stage) {
        const count = cardCount(stage);
        pendingDeleteKey = stage.key;
        if (count > 0) {
            const targets = activeStages().filter((s) => s.key !== stage.key && s.id);
            const sel = document.getElementById('pipeline-move-target');
            const text = document.getElementById('pipeline-move-text');
            const countEl = document.getElementById('pipeline-move-count');
            if (text) text.textContent = i18n.moveTitle;
            if (countEl) countEl.textContent = i18n.moveCount.replace(':count', String(count));
            if (sel) {
                sel.innerHTML = targets.map((t) => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('');
            }
            openModal(els.move());
            return;
        }
        openModal(els.confirm());
    }

    function markPendingDelete(moveToId = null) {
        const stage = draft.find((s) => s.key === pendingDeleteKey);
        if (!stage) return;
        stage.markedDelete = true;
        stage.moveToId = moveToId;
        stage.error = '';
        pendingDeleteKey = null;
        renderList();
    }

    function addLocalStage() {
        const nameEl = els.newName();
        const colorEl = els.newColor();
        const err = els.newError();
        const name = (nameEl?.value || '').trim();
        if (!name) {
            if (err) {
                err.textContent = i18n.nameRequired;
                err.classList.remove('hidden');
            }
            return;
        }
        if (err) { err.textContent = ''; err.classList.add('hidden'); }
        const temp_id = `new_${++tempSeq}`;
        draft.push({
            key: `tmp:${temp_id}`,
            id: null,
            temp_id,
            name,
            color: normalizeColor(colorEl?.value || '#64748b'),
            system_key: null,
            is_system: false,
            markedDelete: false,
            moveToId: null,
            error: '',
            position: draft.length,
        });
        if (nameEl) nameEl.value = '';
        renderList();
        nameEl?.focus();
    }

    function validateDraft() {
        let ok = true;
        const remaining = activeStages();
        if (!remaining.length) {
            toast(i18n.nameRequired, 'error');
            return false;
        }
        remaining.forEach((s) => {
            if (!String(s.name || '').trim()) {
                s.error = i18n.nameRequired;
                ok = false;
            } else {
                s.error = '';
            }
        });
        if (!ok) renderList();
        return ok;
    }

    async function saveDraft() {
        if (!validateDraft()) return;
        const btn = els.saveBtn();
        if (btn) btn.disabled = true;

        const stagesPayload = activeStages().map((s) => ({
            id: s.id || null,
            name: String(s.name).trim(),
            color: s.color,
        }));
        const deletions = draft.filter((s) => s.markedDelete && s.id).map((s) => ({
            id: s.id,
            target_stage_id: s.moveToId || null,
        }));

        try {
            const res = await fetch(routes.pipelineSync, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ type: cfg.pipelineType || 'project', stages: stagesPayload, deletions }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                const msg = data.message || data.errors?.stages?.[0] || data.errors?.deletions?.[0] || i18n.saveError;
                toast(msg, 'error');
                return;
            }

            // Keep local project cards in sync with moved statuses
            draft.filter((s) => s.markedDelete && s.id && s.moveToId).forEach((from) => {
                const to = draft.find((s) => Number(s.id) === Number(from.moveToId));
                if (!from.system_key || !to?.system_key) return;
                (getProjects() || []).forEach((p) => {
                    if (p.status === from.system_key) p.status = to.system_key;
                });
            });

            setPipeline(data.pipeline);
            resetFromPipeline();
            closeModal(els.root());
            renderBoard();
            toast(i18n.saved, 'success');
        } catch (e) {
            toast(e.message || i18n.saveError, 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function requestClose() {
        if (isDirty()) {
            openModal(els.unsaved());
            return;
        }
        closeEditor(true);
    }

    function closeEditor(force) {
        closeModal(els.unsaved());
        closeModal(els.move());
        closeModal(els.confirm());
        if (!force && isDirty()) {
            openModal(els.unsaved());
            return;
        }
        resetFromPipeline();
        closeModal(els.root());
    }

    function openEditor() {
        resetFromPipeline();
        openModal(els.root());
    }

    function bindGlobal() {
        document.getElementById('pipeline-settings-btn')?.addEventListener('click', () => openEditor());
        document.getElementById('pipeline-close')?.addEventListener('click', () => requestClose());
        els.root()?.addEventListener('click', (e) => {
            if (e.target === els.root()) requestClose();
        });
        document.querySelector('[data-pipeline-backdrop]')?.addEventListener('click', () => requestClose());
        document.getElementById('pipeline-cancel')?.addEventListener('click', () => closeEditor(true));
        document.getElementById('pipeline-save')?.addEventListener('click', () => saveDraft());
        document.getElementById('pipeline-add')?.addEventListener('click', () => addLocalStage());
        els.newName()?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); addLocalStage(); }
        });
        els.newColor()?.addEventListener('input', syncNewSwatch);

        document.getElementById('pipeline-unsaved-continue')?.addEventListener('click', () => closeModal(els.unsaved()));
        document.getElementById('pipeline-unsaved-leave')?.addEventListener('click', () => closeEditor(true));

        document.getElementById('pipeline-confirm-cancel')?.addEventListener('click', () => {
            pendingDeleteKey = null;
            closeModal(els.confirm());
        });
        document.getElementById('pipeline-confirm-ok')?.addEventListener('click', () => {
            closeModal(els.confirm());
            markPendingDelete(null);
        });

        document.getElementById('pipeline-move-cancel')?.addEventListener('click', () => {
            pendingDeleteKey = null;
            closeModal(els.move());
        });
        document.getElementById('pipeline-move-confirm')?.addEventListener('click', () => {
            const sel = document.getElementById('pipeline-move-target');
            const targetId = Number(sel?.value || 0);
            if (!targetId) {
                toast(i18n.moveTitle, 'error');
                return;
            }
            closeModal(els.move());
            markPendingDelete(targetId);
        });

        document.addEventListener('click', () => {
            if (!openMenuKey) return;
            openMenuKey = null;
            renderList();
        });

        ['pipeline-modal', 'pipeline-unsaved-modal', 'pipeline-move-modal', 'pipeline-confirm-modal'].forEach((id) => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) document.body.appendChild(el);
        });
    }

    bindGlobal();
    window.CrmPipelineSettings = { open: openEditor, isDirty };
})();
</script>
@endif
