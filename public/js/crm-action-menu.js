/**
 * CrmActionMenu — portal dropdown for CRM tables/cards.
 * Renders menu into document.body so overflow/clipping parents cannot cut it.
 *
 * Usage:
 *   CrmActionMenu.open(triggerEl, {
 *     items: [{ id, label, iconHtml?, danger?, disabled?, onSelect }],
 *     ariaLabel: '...',
 *     onClose: () => {},
 *   });
 *   CrmActionMenu.close();
 */
(function (global) {
    'use strict';

    const ROOT_ID = 'crm-action-menu-root';
    const Z = 'var(--crm-z-dropdown, 80)';
    const GAP = 8;
    const EDGE = 12;
    const MENU_WIDTH = 240;

    let state = {
        open: false,
        trigger: null,
        onClose: null,
        menuEl: null,
        placement: 'bottom',
        mobileSheet: false,
    };

    function ensureRoot() {
        let root = document.getElementById(ROOT_ID);
        if (!root) {
            root = document.createElement('div');
            root.id = ROOT_ID;
            root.className = 'crm-action-menu-root';
            root.setAttribute('data-crm-portal', 'action-menu');
            document.body.appendChild(root);
        }
        return root;
    }

    function isMobile() {
        return window.matchMedia('(max-width: 640px)').matches;
    }

    function close(opts = {}) {
        if (!state.open) return;
        const trigger = state.trigger;
        const onClose = state.onClose;
        const menuEl = state.menuEl;
        const root = document.getElementById(ROOT_ID);
        if (root) root.innerHTML = '';
        if (menuEl) menuEl.remove();
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-controls');
            if (opts.restoreFocus !== false) {
                try { trigger.focus({ preventScroll: true }); } catch (_) { trigger.focus(); }
            }
        }
        state = { open: false, trigger: null, onClose: null, menuEl: null, placement: 'bottom', mobileSheet: false };
        document.removeEventListener('keydown', onKeyDown, true);
        document.removeEventListener('mousedown', onPointerDown, true);
        window.removeEventListener('resize', onViewportChange, true);
        window.removeEventListener('scroll', onScrollClose, true);
        if (typeof onClose === 'function') onClose();
    }

    function onKeyDown(e) {
        if (!state.open || !state.menuEl) return;
        const items = [...state.menuEl.querySelectorAll('[role="menuitem"]:not([disabled])')];
        const active = document.activeElement;
        const idx = items.indexOf(active);

        if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            close();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const next = items[(idx + 1 + items.length) % items.length] || items[0];
            next?.focus();
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prev = items[(idx - 1 + items.length) % items.length] || items[items.length - 1];
            prev?.focus();
            return;
        }
        if (e.key === 'Home') {
            e.preventDefault();
            items[0]?.focus();
            return;
        }
        if (e.key === 'End') {
            e.preventDefault();
            items[items.length - 1]?.focus();
        }
    }

    function onPointerDown(e) {
        if (!state.open) return;
        const t = e.target;
        if (state.menuEl?.contains(t)) return;
        if (state.trigger?.contains(t) || state.trigger === t) return;
        close({ restoreFocus: false });
    }

    function onViewportChange() {
        if (!state.open) return;
        positionMenu();
    }

    function onScrollClose(e) {
        if (!state.open) return;
        // Ignore scrolls inside the menu itself
        if (state.menuEl && e.target && state.menuEl.contains(e.target)) return;
        close({ restoreFocus: false });
    }

    function buildMenu(cfg) {
        const mobile = isMobile();
        const wrap = document.createElement('div');
        wrap.className = mobile ? 'crm-action-menu crm-action-menu--sheet' : 'crm-action-menu';
        wrap.id = 'crm-action-menu-panel';
        wrap.setAttribute('role', 'menu');
        if (cfg.ariaLabel) wrap.setAttribute('aria-label', cfg.ariaLabel);

        if (mobile) {
            const handle = document.createElement('div');
            handle.className = 'crm-action-menu-sheet-handle';
            handle.setAttribute('aria-hidden', 'true');
            wrap.appendChild(handle);
        }

        (cfg.items || []).forEach((item, i) => {
            if (item.divider) {
                const hr = document.createElement('div');
                hr.className = 'crm-action-menu-divider';
                hr.setAttribute('role', 'separator');
                wrap.appendChild(hr);
                return;
            }
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'crm-action-menu-item' + (item.danger ? ' is-danger' : '');
            btn.setAttribute('role', 'menuitem');
            btn.dataset.itemId = String(item.id ?? i);
            if (item.disabled) btn.disabled = true;
            btn.innerHTML = `${item.iconHtml || ''}<span>${item.label || ''}</span>`;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (item.disabled) return;
                const fn = item.onSelect;
                close({ restoreFocus: false });
                if (typeof fn === 'function') fn(item);
            });
            wrap.appendChild(btn);
        });

        return wrap;
    }

    function positionMenu() {
        if (!state.open || !state.menuEl || !state.trigger) return;
        const menu = state.menuEl;

        if (isMobile()) {
            state.mobileSheet = true;
            menu.classList.add('crm-action-menu--sheet');
            menu.style.position = 'fixed';
            menu.style.left = '0';
            menu.style.right = '0';
            menu.style.bottom = '0';
            menu.style.top = 'auto';
            menu.style.width = '100%';
            menu.style.maxWidth = '100%';
            menu.style.maxHeight = 'min(70dvh, 420px)';
            menu.style.overflowY = 'auto';
            menu.style.zIndex = '';
            return;
        }

        state.mobileSheet = false;
        menu.classList.remove('crm-action-menu--sheet');
        menu.style.width = MENU_WIDTH + 'px';
        menu.style.maxWidth = `calc(100vw - ${EDGE * 2}px)`;
        menu.style.visibility = 'hidden';
        menu.style.display = 'block';
        menu.style.position = 'fixed';
        menu.style.zIndex = '';

        const rect = state.trigger.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        const spaceBelow = vh - rect.bottom - EDGE;
        const spaceAbove = rect.top - EDGE;
        const preferUp = spaceBelow < menuRect.height + GAP && spaceAbove > spaceBelow;

        let top;
        if (preferUp) {
            top = rect.top - GAP - menuRect.height;
            state.placement = 'top';
        } else {
            top = rect.bottom + GAP;
            state.placement = 'bottom';
        }

        // Align to right edge of trigger
        let left = rect.right - menuRect.width;
        if (left < EDGE) left = EDGE;
        if (left + menuRect.width > vw - EDGE) left = Math.max(EDGE, vw - EDGE - menuRect.width);

        const maxH = Math.max(120, vh - EDGE * 2);
        menu.style.maxHeight = maxH + 'px';

        const available = preferUp ? spaceAbove : spaceBelow;
        if (menuRect.height > available + 4 && menuRect.height > maxH * 0.85) {
            menu.style.overflowY = 'auto';
        } else {
            menu.style.overflowY = 'visible';
        }

        if (top < EDGE) top = EDGE;
        if (top + Math.min(menuRect.height, maxH) > vh - EDGE) {
            top = Math.max(EDGE, vh - EDGE - Math.min(menuRect.height, maxH));
        }

        menu.style.top = Math.round(top) + 'px';
        menu.style.left = Math.round(left) + 'px';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        menu.style.visibility = 'visible';
    }

    function open(trigger, cfg = {}) {
        if (!trigger) return;
        if (state.open && state.trigger === trigger) {
            close();
            return;
        }
        close({ restoreFocus: false });

        const root = ensureRoot();
        const menu = buildMenu(cfg);
        root.appendChild(menu);

        state.open = true;
        state.trigger = trigger;
        state.onClose = cfg.onClose || null;
        state.menuEl = menu;

        const menuId = menu.id;
        trigger.setAttribute('aria-expanded', 'true');
        trigger.setAttribute('aria-controls', menuId);
        trigger.setAttribute('aria-haspopup', 'menu');

        document.addEventListener('keydown', onKeyDown, true);
        document.addEventListener('mousedown', onPointerDown, true);
        window.addEventListener('resize', onViewportChange, true);
        // capture scroll on any scrollable ancestor
        window.addEventListener('scroll', onScrollClose, true);

        positionMenu();
        // Remeasure after paint for accurate flip when near viewport edges
        requestAnimationFrame(() => {
            if (state.open) positionMenu();
        });

        const first = menu.querySelector('[role="menuitem"]:not([disabled])');
        first?.focus({ preventScroll: true });
    }

    global.CrmActionMenu = {
        open,
        close,
        isOpen: () => state.open,
        getTrigger: () => state.trigger,
        getPlacement: () => state.placement,
    };
})(typeof window !== 'undefined' ? window : globalThis);
