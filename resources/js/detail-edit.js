/**
 * Shared view/edit + dirty-state for detail pages.
 *
 * Usage:
 *   window.initDetailEdit({
 *     root: '#order-detail',
 *     form: '#supplier-order-details-form',
 *     editBtn: '#btn-edit',
 *     cancelBtn: '#btn-cancel',
 *     saveBtn: '#btn-save',
 *     sticky: '#detail-sticky-actions',
 *     viewBlocks: '[data-detail-view]',
 *     editBlocks: '[data-detail-edit]',
 *     onSave: async (form) => { ... return true on success },
 *     unsavedModal: '#detail-unsaved-modal',
 *   });
 */
window.initDetailEdit = function initDetailEdit(opts) {
    const form = typeof opts.form === 'string' ? document.querySelector(opts.form) : opts.form;
    if (!form) return null;

    const editBtn = document.querySelector(opts.editBtn || '#btn-edit');
    const cancelBtn = document.querySelector(opts.cancelBtn || '#btn-cancel');
    const saveBtn = document.querySelector(opts.saveBtn || '#btn-save');
    const sticky = document.querySelector(opts.sticky || '#detail-sticky-actions');
    const viewBlocks = () => document.querySelectorAll(opts.viewBlocks || '[data-detail-view]');
    const editBlocks = () => document.querySelectorAll(opts.editBlocks || '[data-detail-edit]');
    const unsavedModal = document.querySelector(opts.unsavedModal || '#detail-unsaved-modal');

    let editing = false;
    let baseline = '';
    let bypassGuard = false;

    function serialize() {
        const fd = new FormData(form);
        const entries = [];
        for (const [k, v] of fd.entries()) {
            if (v instanceof File) {
                if (v.name) entries.push(k + '=file:' + v.name + ':' + v.size);
            } else {
                entries.push(k + '=' + String(v ?? '').trim());
            }
        }
        entries.sort();
        return entries.join('\n');
    }

    function isDirty() {
        return editing && serialize() !== baseline;
    }

    function setEditing(on) {
        editing = on;
        viewBlocks().forEach((el) => el.classList.toggle('hidden', on));
        editBlocks().forEach((el) => el.classList.toggle('hidden', !on));
        editBtn?.classList.toggle('hidden', on);
        if (on) {
            baseline = serialize();
        }
        syncSticky();
        opts.onModeChange?.(on);
    }

    function syncSticky() {
        if (!editing) {
            sticky?.classList.add('hidden');
            if (saveBtn) saveBtn.disabled = true;
            return;
        }
        // Always show action bar in edit mode (Cancel must stay reachable).
        sticky?.classList.remove('hidden');
        const dirty = isDirty();
        const hint = sticky?.querySelector('[data-unsaved-hint]');
        hint?.classList.toggle('opacity-40', !dirty);
        if (saveBtn) saveBtn.disabled = !dirty;
    }

    function openUnsaved(onLeave) {
        if (!unsavedModal) {
            if (confirm(opts.unsavedMessage || 'Unsaved changes')) onLeave();
            return;
        }
        unsavedModal.classList.remove('hidden');
        unsavedModal.classList.add('flex');
        const stay = unsavedModal.querySelector('[data-unsaved-stay]');
        const leave = unsavedModal.querySelector('[data-unsaved-leave]');
        const close = () => {
            unsavedModal.classList.add('hidden');
            unsavedModal.classList.remove('flex');
        };
        const onStay = () => { close(); cleanup(); };
        const onLeaveClick = () => { close(); cleanup(); onLeave(); };
        function cleanup() {
            stay?.removeEventListener('click', onStay);
            leave?.removeEventListener('click', onLeaveClick);
        }
        stay?.addEventListener('click', onStay);
        leave?.addEventListener('click', onLeaveClick);
    }

    function guardLeave(fn) {
        if (!isDirty()) {
            fn();
            return;
        }
        openUnsaved(fn);
    }

    editBtn?.addEventListener('click', () => setEditing(true));

    cancelBtn?.addEventListener('click', () => {
        guardLeave(() => {
            form.reset();
            setEditing(false);
            opts.onCancel?.();
        });
    });

    form.addEventListener('input', syncSticky);
    form.addEventListener('change', syncSticky);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!isDirty()) return;
        if (typeof opts.onSave !== 'function') return;
        if (!window.lockSubmit?.(form)) {
            // If lock helper missing, still allow save
            if (typeof window.lockSubmit === 'function') return;
        }
        try {
            const ok = await opts.onSave(form);
            if (ok) {
                bypassGuard = true;
                baseline = serialize();
                setEditing(false);
                opts.onSaved?.(form);
            }
        } finally {
            window.unlockSubmit?.(form);
            syncSticky();
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (bypassGuard || !isDirty()) return;
        e.preventDefault();
        e.returnValue = '';
    });

    document.querySelectorAll('a[href]').forEach((a) => {
        a.addEventListener('click', (e) => {
            if (!isDirty()) return;
            if (a.target === '_blank' || e.metaKey || e.ctrlKey) return;
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
            e.preventDefault();
            openUnsaved(() => {
                bypassGuard = true;
                window.location.href = a.href;
            });
        });
    });

    return { setEditing, isDirty, syncSticky, guardLeave };
};

/**
 * Wait for DOM + initDetailEdit, then wire a standard AJAX save.
 *
 * bootDetailEditPage({
 *   form: '#client-details-form',
 *   successMessage: '...',
 *   errorMessage: '...',
 *   onModeChange: (editing) => {},
 *   beforeSave: (form) => true|false, // return false to abort
 *   afterSave: (data) => {},
 * })
 */
window.bootDetailEditPage = function bootDetailEditPage(opts) {
    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function whenInitReady(fn, triesLeft) {
        if (typeof window.initDetailEdit === 'function' || triesLeft <= 0) {
            fn();
            return;
        }
        setTimeout(function () {
            whenInitReady(fn, triesLeft - 1);
        }, 40);
    }

    whenReady(function () {
        whenInitReady(function () {
            const form = typeof opts.form === 'string'
                ? document.querySelector(opts.form)
                : opts.form;
            if (!form) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const defaultSave = async function (f) {
                if (typeof opts.beforeSave === 'function' && opts.beforeSave(f) === false) {
                    return false;
                }
                const fd = new FormData(f);
                const r = await fetch(f.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || data.success === false) {
                    const msg =
                        data.message ||
                        Object.values(data.errors || {})
                            .flat()
                            .join(' ') ||
                        opts.errorMessage ||
                        'Error';
                    window.projectAlert?.('error', msg, '', 4000);
                    return false;
                }
                window.projectAlert?.(
                    'success',
                    data.message || opts.successMessage || 'Saved',
                    '',
                    2500
                );
                opts.afterSave?.(data);
                if (opts.reload !== false) {
                    setTimeout(function () {
                        location.reload();
                    }, 400);
                }
                return true;
            };

            if (typeof window.initDetailEdit === 'function') {
                window.initDetailEdit({
                    form,
                    editBtn: opts.editBtn || '#btn-edit',
                    cancelBtn: opts.cancelBtn || '#btn-cancel',
                    saveBtn: opts.saveBtn || '#btn-save',
                    sticky: opts.sticky || '#detail-sticky-actions',
                    unsavedModal: opts.unsavedModal || '#detail-unsaved-modal',
                    viewBlocks: opts.viewBlocks,
                    editBlocks: opts.editBlocks,
                    onModeChange: opts.onModeChange,
                    onCancel: opts.onCancel,
                    onSaved: opts.onSaved,
                    onSave: opts.onSave || defaultSave,
                });
                return;
            }

            // Fallback: toggle visibility only
            const editBtn = document.querySelector(opts.editBtn || '#btn-edit');
            const sticky = document.querySelector(opts.sticky || '#detail-sticky-actions');
            editBtn?.addEventListener('click', function () {
                document.querySelectorAll(opts.viewBlocks || '[data-detail-view]').forEach((el) => {
                    el.classList.add('hidden');
                });
                document.querySelectorAll(opts.editBlocks || '[data-detail-edit]').forEach((el) => {
                    el.classList.remove('hidden');
                });
                editBtn.classList.add('hidden');
                sticky?.classList.remove('hidden');
            });
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                await defaultSave(form);
            });
        }, 50);
    });
};
