<script>
window.allSuppliers = @json($suppliersData ?? []);
(function () {
    'use strict';

    const VIEW_KEY = 'crm.suppliers.view';
    const PER_PAGE_KEY = 'crm.suppliers.perPage';
    const FILTERS_KEY = 'crm.suppliers.filters';

    const i18n = {
        openProfile: @json(__('suppliers.open_profile')),
        open: @json(__('suppliers.open')),
        edit: @json(__('suppliers.edit')),
        delete: @json(__('suppliers.delete')),
        addOrder: @json(__('suppliers.add_order')),
        addFavorite: @json(__('suppliers.add_favorite')),
        removeFavorite: @json(__('suppliers.remove_favorite')),
        moreActions: @json(__('suppliers.more_actions')),
        noRatings: @json(__('suppliers.no_ratings')),
        reviewsCount: @json(__('suppliers.reviews_count')),
        shownOf: @json(__('suppliers.shown_of')),
        emptyTitle: @json(__('suppliers.empty_title')),
        emptyBody: @json(__('suppliers.empty_body')),
        emptyFiltered: @json(__('suppliers.empty_filtered')),
        resetAllFilters: @json(__('suppliers.reset_all_filters')),
        filterAll: @json(__('suppliers.filter_all')),
        filterRecommended: @json(__('suppliers.filter_recommended')),
        filterFavorites: @json(__('suppliers.filter_favorites')),
        prev: @json(__('objects.prev')),
        next: @json(__('objects.next')),
        supplierCol: @json(__('suppliers.supplier_col')),
        contacts: @json(__('suppliers.contacts')),
        city: @json(__('suppliers.city')),
        direction: @json(__('suppliers.direction')),
        rating: @json(__('suppliers.rating')),
        status: @json(__('moderation.status')),
        moderation: {
            approved: @json(__('moderation.approved')),
            pending: @json(__('moderation.pending')),
            rejected: @json(__('moderation.rejected')),
        },
    };

    const icons = {
        more: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>`,
        eye: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>`,
        star: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.5 6.8L12 16.9 5.9 20.3l1.5-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>`,
        starFilled: `<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.5 6.8L12 16.9 5.9 20.3l1.5-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>`,
        package: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16.5 9.4 7.55 4.24M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.29 7 12 12l8.71-5M12 22V12"/></svg>`,
        pencil: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>`,
        trash: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>`,
        starSmall: `<svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.3 6.9.6-5.2 4.6 1.5 6.8L12 16.9 5.9 20.3l1.5-6.8L2.2 8.9l6.9-.6L12 2z"/></svg>`,
    };

    const els = {
        root: document.getElementById('crm-suppliers-workspace'),
        search: document.getElementById('suppliers-search'),
        searchClear: document.getElementById('suppliers-search-clear'),
        filtersBtn: document.getElementById('suppliers-filters-btn'),
        filtersPanel: document.getElementById('suppliers-filters-panel'),
        filtersBackdrop: document.getElementById('suppliers-filters-backdrop'),
        filtersBadge: document.getElementById('suppliers-filters-badge'),
        filterType: document.getElementById('suppliers-filter-type'),
        filterCity: document.getElementById('suppliers-filter-city'),
        filterSphere: document.getElementById('suppliers-filter-sphere'),
        filterBrand: document.getElementById('suppliers-filter-brand'),
        filtersApply: document.getElementById('suppliers-filters-apply'),
        filtersReset: document.getElementById('suppliers-filters-reset'),
        chips: document.getElementById('suppliers-filter-chips'),
        tablePanel: document.getElementById('suppliers-table-panel'),
        cardsPanel: document.getElementById('suppliers-cards-panel'),
        tableBody: document.getElementById('suppliers-table-body'),
        cardsBody: document.getElementById('suppliers-cards-body'),
        empty: document.getElementById('suppliers-empty'),
        emptyBody: document.getElementById('suppliers-empty-body'),
        emptyCreate: document.getElementById('suppliers-empty-create'),
        emptyReset: document.getElementById('suppliers-empty-reset'),
        perPage: document.getElementById('suppliers-per-page'),
        paginationTable: document.getElementById('suppliers-pagination-table'),
        paginationCards: document.getElementById('suppliers-pagination-cards'),
        metaTable: document.getElementById('suppliers-result-meta-table'),
        metaCards: document.getElementById('suppliers-result-meta-cards'),
        headerCount: document.getElementById('suppliers-header-count'),
        createBtn: document.getElementById('add-supplier-btn'),
    };

    function readStoredView() {
        const q = new URLSearchParams(window.location.search).get('view');
        if (q === 'table' || q === 'cards') return q;
        if (q === 'list') return 'cards';
        const stored = localStorage.getItem(VIEW_KEY);
        if (stored === 'table' || stored === 'cards') return stored;
        if (stored === 'list') return 'cards';
        return 'table';
    }

    function readStoredFilters() {
        try {
            const raw = localStorage.getItem(FILTERS_KEY);
            if (!raw) return { type: 'all', city: '', sphere: '', brand: '' };
            const parsed = JSON.parse(raw);
            return {
                type: parsed.type || 'all',
                city: parsed.city || '',
                sphere: parsed.sphere || '',
                brand: parsed.brand || '',
            };
        } catch (_) {
            return { type: 'all', city: '', sphere: '', brand: '' };
        }
    }

    const state = {
        view: readStoredView(),
        preferredView: readStoredView(),
        search: els.search?.value || '',
        draftFilters: readStoredFilters(),
        filters: readStoredFilters(),
        sortKey: 'name',
        sortDir: 'asc',
        page: 1,
        perPage: parseInt(localStorage.getItem(PER_PAGE_KEY) || (els.perPage?.value || '10'), 10) || 10,
        openMenuId: null,
        mobileForceCards: false,
    };

    if (els.perPage) {
        if (![10, 25, 50].includes(state.perPage)) state.perPage = 10;
        els.perPage.value = String(state.perPage);
    }

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    const debounce = (fn, ms = 400) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    function syncFilterDraftInputs() {
        if (els.filterType) els.filterType.value = state.draftFilters.type || 'all';
        if (els.filterCity) els.filterCity.value = state.draftFilters.city || '';
        if (els.filterSphere) els.filterSphere.value = state.draftFilters.sphere || '';
        if (els.filterBrand) els.filterBrand.value = state.draftFilters.brand || '';
    }

    function activeFilterCount(filters = state.filters) {
        let n = 0;
        if (filters.type && filters.type !== 'all') n++;
        if (filters.city) n++;
        if (filters.sphere) n++;
        if (filters.brand) n++;
        return n;
    }

    function hasActiveQuery() {
        return !!(state.search.trim() || activeFilterCount() > 0);
    }

    function syncUrl() {
        const url = new URL(window.location.href);
        url.searchParams.set('view', state.view);
        if (state.search.trim()) url.searchParams.set('search', state.search.trim());
        else url.searchParams.delete('search');
        window.history.replaceState({}, '', url);
    }

    function setView(view, { persist = true } = {}) {
        let next = view === 'cards' ? 'cards' : 'table';
        if (!state.mobileForceCards && persist) state.preferredView = next;
        if (state.mobileForceCards) next = 'cards';
        state.view = next;
        document.querySelectorAll('#crm-suppliers-workspace .crm-view-btn').forEach((btn) => {
            const on = btn.dataset.view === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            btn.disabled = !!state.mobileForceCards;
        });
        if (persist && !state.mobileForceCards) {
            localStorage.setItem(VIEW_KEY, state.preferredView);
            syncUrl();
        }
        closeMenus();
        render();
    }

    function filteredSuppliers() {
        const search = state.search.trim().toLowerCase();
        const { type, city, sphere, brand } = state.filters;
        let data = (window.allSuppliers || []).filter((s) => {
            const hay = [
                s.name, s.phone, s.email, s.website, s.city, s.sphere, s.sphere_display,
                s.address, s.comment, s.brand_display, s.inn,
            ].filter(Boolean).join(' ').toLowerCase();
            const bySearch = !search || hay.includes(search);
            const byType = type === 'all'
                || (type === 'recommended' && !!s.recommend)
                || (type === 'favorites' && !!s.is_favorite);
            const byCity = !city || (s.city || '') === city;
            const bySphere = !sphere || (s.sphere || '') === sphere;
            const brands = Array.isArray(s.brands) ? s.brands : [];
            const byBrand = !brand || brands.includes(brand);
            return bySearch && byType && byCity && bySphere && byBrand;
        });

        const dir = state.sortDir === 'desc' ? -1 : 1;
        const key = state.sortKey;
        data = data.slice().sort((a, b) => {
            let av = a?.[key];
            let bv = b?.[key];
            if (key === 'sphere') {
                av = a.sphere_display || a.sphere || '';
                bv = b.sphere_display || b.sphere || '';
            }
            av = String(av ?? '').toLowerCase();
            bv = String(bv ?? '').toLowerCase();
            if (av === bv) return 0;
            return av > bv ? dir : -dir;
        });
        return data;
    }

    function moderationBadge(status) {
        const st = String(status || 'pending');
        const map = {
            approved: ['is-approved', i18n.moderation.approved],
            pending: ['is-pending', i18n.moderation.pending],
            rejected: ['is-rejected', i18n.moderation.rejected],
        };
        const [cls, label] = map[st] || ['is-unknown', st];
        return `<span class="crm-mod-badge ${cls}">${escapeHtml(label)}</span>`;
    }

    function ratingHtml(rating) {
        const avg = Number(rating?.average);
        const count = Number(rating?.count || 0);
        if (!avg || Number.isNaN(avg) || avg <= 0) {
            return `<span class="crm-rating-empty">${escapeHtml(i18n.noRatings)}</span>`;
        }
        const value = avg.toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 1 });
        const reviews = count > 0
            ? `<span class="crm-rating-count">${escapeHtml(i18n.reviewsCount.replace(':count', String(count)))}</span>`
            : '';
        return `<span class="crm-rating"><span class="crm-rating-row"><span class="crm-rating-star">${icons.starSmall}</span><strong>${escapeHtml(value)}</strong></span>${reviews}</span>`;
    }

    function contactsHtml(s) {
        const phone = (s.phone || '').trim();
        const website = (s.website || '').trim();
        const lines = [];
        if (phone) lines.push(`<div>${escapeHtml(phone)}</div>`);
        if (website) {
            lines.push(`<div><a href="${escapeHtml(website)}" target="_blank" rel="noopener" data-stop>${escapeHtml(website.replace(/^https?:\/\//i, ''))}</a></div>`);
        }
        if (!lines.length) return `<span class="crm-cell-secondary">—</span>`;
        return lines.join('');
    }

    function directionHtml(s) {
        const sphere = s.sphere_display || s.sphere || '';
        const brand = s.brand_display || '';
        if (!sphere && !brand) return `<span class="crm-cell-secondary">—</span>`;
        return `${sphere ? `<div class="crm-cell-primary" style="font-weight:500">${escapeHtml(sphere)}</div>` : ''}
            ${brand ? `<div class="crm-cell-secondary">${escapeHtml(brand)}</div>` : ''}`;
    }

    function supplierPrimaryHtml(s) {
        const brand = s.brand_display || '';
        return `<div class="crm-cell-primary">${escapeHtml(s.name || '')}</div>
            ${brand ? `<div class="crm-cell-secondary">${escapeHtml(brand)}</div>` : ''}`;
    }

    function canManage(s) {
        return !!(s.is_owned_by_designer && s.designer_can_manage);
    }

    function buildActionItems(s) {
        const favLabel = s.is_favorite ? i18n.removeFavorite : i18n.addFavorite;
        const favIcon = s.is_favorite ? icons.starFilled : icons.star;
        const items = [
            {
                id: 'view',
                label: i18n.openProfile,
                iconHtml: icons.eye,
                onSelect: () => viewSupplier(s.id),
            },
            {
                id: 'favorite',
                label: favLabel,
                iconHtml: favIcon,
                onSelect: () => toggleFavorite(s.id, null),
            },
            {
                id: 'order',
                label: i18n.addOrder,
                iconHtml: icons.package,
                onSelect: () => addOrderFromSupplier(s.id),
            },
        ];
        if (canManage(s)) {
            items.push({ divider: true });
            items.push({
                id: 'edit',
                label: i18n.edit,
                iconHtml: icons.pencil,
                onSelect: () => editSupplier(s.id),
            });
            items.push({
                id: 'delete',
                label: i18n.delete,
                iconHtml: icons.trash,
                danger: true,
                onSelect: () => deleteSupplier(s.id),
            });
        }
        return items;
    }

    function actionsMenuHtml(s) {
        return `<div class="crm-actions-wrap" data-stop data-actions-id="${s.id}">
            <button type="button" class="crm-actions-btn" data-menu-toggle="${s.id}"
                aria-label="${escapeHtml(i18n.moreActions)}" title="${escapeHtml(i18n.moreActions)}"
                aria-haspopup="menu" aria-expanded="false">${icons.more}</button>
        </div>`;
    }

    function closeMenus() {
        state.openMenuId = null;
        if (window.CrmActionMenu?.isOpen?.()) window.CrmActionMenu.close({ restoreFocus: false });
        document.querySelectorAll('#crm-suppliers-workspace [data-menu-toggle]').forEach((btn) => {
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    function openActionMenu(btn, supplierId) {
        const s = (window.allSuppliers || []).find((x) => Number(x.id) === Number(supplierId));
        if (!s || typeof window.CrmActionMenu?.open !== 'function') return;
        const willClose = window.CrmActionMenu.isOpen() && window.CrmActionMenu.getTrigger() === btn;
        if (willClose) {
            closeMenus();
            return;
        }
        state.openMenuId = Number(supplierId);
        window.CrmActionMenu.open(btn, {
            ariaLabel: i18n.moreActions,
            items: buildActionItems(s),
            onClose: () => {
                state.openMenuId = null;
                btn.setAttribute('aria-expanded', 'false');
            },
        });
    }

    function openFilters() {
        syncFilterDraftInputs();
        els.filtersPanel?.classList.add('is-open');
        els.filtersBackdrop?.classList.add('is-open');
        els.filtersBackdrop?.removeAttribute('hidden');
        els.filtersBtn?.setAttribute('aria-expanded', 'true');
        els.filtersPanel?.setAttribute('aria-hidden', 'false');
    }

    function closeFilters() {
        els.filtersPanel?.classList.remove('is-open');
        els.filtersBackdrop?.classList.remove('is-open');
        els.filtersBackdrop?.setAttribute('hidden', '');
        els.filtersBtn?.setAttribute('aria-expanded', 'false');
        els.filtersPanel?.setAttribute('aria-hidden', 'true');
    }

    function applyFiltersFromDraft() {
        state.filters = { ...state.draftFilters };
        localStorage.setItem(FILTERS_KEY, JSON.stringify(state.filters));
        state.page = 1;
        closeFilters();
        render();
    }

    function resetFilters() {
        state.draftFilters = { type: 'all', city: '', sphere: '', brand: '' };
        state.filters = { ...state.draftFilters };
        localStorage.setItem(FILTERS_KEY, JSON.stringify(state.filters));
        syncFilterDraftInputs();
        state.page = 1;
        closeFilters();
        render();
    }

    function renderChips() {
        if (!els.chips) return;
        const chips = [];
        const f = state.filters;
        if (f.type === 'recommended') chips.push({ key: 'type', label: i18n.filterRecommended });
        if (f.type === 'favorites') chips.push({ key: 'type', label: i18n.filterFavorites });
        if (f.city) chips.push({ key: 'city', label: f.city });
        if (f.sphere) {
            const opt = [...(els.filterSphere?.options || [])].find((o) => o.value === f.sphere);
            chips.push({ key: 'sphere', label: opt?.textContent || f.sphere });
        }
        if (f.brand) chips.push({ key: 'brand', label: f.brand });

        const count = chips.length;
        if (els.filtersBadge) {
            if (count > 0) {
                els.filtersBadge.hidden = false;
                els.filtersBadge.textContent = String(count);
            } else {
                els.filtersBadge.hidden = true;
            }
        }

        if (!count) {
            els.chips.classList.remove('has-chips');
            els.chips.innerHTML = '';
            return;
        }
        els.chips.classList.add('has-chips');
        els.chips.innerHTML = chips.map((c) => `
            <span class="crm-chip">
                <span>${escapeHtml(c.label)}</span>
                <button type="button" data-chip-remove="${escapeHtml(c.key)}" aria-label="×">×</button>
            </span>
        `).join('') + `<button type="button" class="crm-chip-clear" data-chip-clear>${escapeHtml(i18n.resetAllFilters)}</button>`;
    }

    function updateHeaderCount(total) {
        if (els.headerCount) els.headerCount.textContent = String(total);
    }

    function updateMeta(total, pageItems) {
        const from = total === 0 ? 0 : ((state.page - 1) * state.perPage + 1);
        const to = Math.min(state.page * state.perPage, total);
        const text = i18n.shownOf
            .replace(':from', String(from))
            .replace(':to', String(to))
            .replace(':total', String(total));
        if (els.metaTable) els.metaTable.textContent = text;
        if (els.metaCards) els.metaCards.textContent = text;
    }

    function renderPagination(container, totalPages) {
        if (!container) return;
        if (totalPages <= 1) {
            container.innerHTML = '';
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
        container.innerHTML = `
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-page="${state.page - 1}" ${state.page <= 1 ? 'disabled' : ''}>${escapeHtml(i18n.prev)}</button>
            ${pages.map((p) => {
                if (p === '…') return `<span class="text-xs text-[var(--crm-muted)] px-1">…</span>`;
                const active = p === state.page;
                return `<button type="button" class="crm-btn crm-btn-sm ${active ? 'crm-btn-primary' : 'crm-btn-secondary'}" data-page="${p}" ${active ? 'disabled' : ''}>${p}</button>`;
            }).join('')}
            <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-page="${state.page + 1}" ${state.page >= totalPages ? 'disabled' : ''}>${escapeHtml(i18n.next)}</button>
        `;
        container.querySelectorAll('button[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                state.page = Number(btn.dataset.page);
                render();
            });
        });
    }

    function bindRowOpen(root) {
        root?.querySelectorAll('[data-supplier-id]').forEach((el) => {
            el.addEventListener('click', (e) => {
                if (e.target.closest('[data-stop], a, button, input, select, textarea')) return;
                const id = Number(el.dataset.supplierId);
                if (id) viewSupplier(id);
            });
            el.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    if (e.target.closest('[data-stop], a, button')) return;
                    e.preventDefault();
                    const id = Number(el.dataset.supplierId);
                    if (id) viewSupplier(id);
                }
            });
        });
    }

    function bindActions(root) {
        root?.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openActionMenu(btn, Number(btn.dataset.menuToggle));
            });
        });
        root?.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = Number(btn.dataset.id);
                const action = btn.dataset.action;
                closeMenus();
                if (action === 'view') viewSupplier(id);
                else if (action === 'edit') editSupplier(id);
                else if (action === 'delete') deleteSupplier(id);
                else if (action === 'order') addOrderFromSupplier(id);
                else if (action === 'favorite') toggleFavorite(id, btn);
            });
        });
    }

    function updateSortHeaders() {
        document.querySelectorAll('#suppliers-table th[data-sort]').forEach((th) => {
            th.classList.remove('is-asc', 'is-desc');
            if (th.dataset.sort === state.sortKey) {
                th.classList.add(state.sortDir === 'asc' ? 'is-asc' : 'is-desc');
            }
        });
    }

    function renderTable(items) {
        if (!els.tableBody) return;
        els.tableBody.innerHTML = items.map((s) => `
            <tr data-supplier-id="${s.id}" tabindex="0">
                <td data-label="${escapeHtml(i18n.supplierCol)}">${supplierPrimaryHtml(s)}</td>
                <td data-label="${escapeHtml(i18n.contacts)}">${contactsHtml(s)}</td>
                <td class="hidden md:table-cell" data-label="${escapeHtml(i18n.city)}">${escapeHtml(s.city || '—')}</td>
                <td class="hidden lg:table-cell" data-label="${escapeHtml(i18n.direction)}">${directionHtml(s)}</td>
                <td data-label="${escapeHtml(i18n.rating)}">${ratingHtml(s.rating)}</td>
                <td data-label="${escapeHtml(i18n.status)}">${moderationBadge(s.moderation_status)}</td>
                <td data-label="">${actionsMenuHtml(s)}</td>
            </tr>
        `).join('');
        bindRowOpen(els.tableBody);
        bindActions(els.tableBody);
        updateSortHeaders();
    }

    function renderCards(items) {
        if (!els.cardsBody) return;
        els.cardsBody.innerHTML = items.map((s) => `
            <article class="crm-supplier-card" data-supplier-id="${s.id}" tabindex="0">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="crm-supplier-card-title">${escapeHtml(s.name || '')}</div>
                        <div class="mt-1">${ratingHtml(s.rating)}</div>
                    </div>
                    ${moderationBadge(s.moderation_status)}
                </div>
                <div class="crm-supplier-card-meta">
                    ${s.city ? `<div>${escapeHtml(s.city)}</div>` : ''}
                    ${(s.sphere_display || s.sphere) ? `<div>${escapeHtml(s.sphere_display || s.sphere)}</div>` : ''}
                    ${s.brand_display ? `<div>${escapeHtml(s.brand_display)}</div>` : ''}
                    ${s.phone ? `<div>${escapeHtml(s.phone)}</div>` : ''}
                </div>
                <div class="crm-supplier-card-foot" data-stop>
                    <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" data-action="view" data-id="${s.id}">${escapeHtml(i18n.open)}</button>
                    ${actionsMenuHtml(s)}
                </div>
            </article>
        `).join('');
        bindRowOpen(els.cardsBody);
        bindActions(els.cardsBody);
    }

    function renderEmpty(totalAll) {
        const filtered = hasActiveQuery();
        if (els.emptyBody) {
            els.emptyBody.textContent = filtered ? i18n.emptyFiltered : i18n.emptyBody;
        }
        if (els.emptyReset) {
            els.emptyReset.hidden = !filtered;
            els.emptyReset.classList.toggle('is-hidden', !filtered);
        }
        if (els.emptyCreate) {
            els.emptyCreate.hidden = filtered && totalAll > 0;
            els.emptyCreate.classList.toggle('is-hidden', filtered && totalAll > 0);
        }
    }

    function render() {
        const data = filteredSuppliers();
        const total = data.length;
        const totalPages = Math.max(1, Math.ceil(total / state.perPage));
        if (state.page > totalPages) state.page = 1;
        const start = (state.page - 1) * state.perPage;
        const pageItems = data.slice(start, start + state.perPage);

        updateHeaderCount((window.allSuppliers || []).length);
        renderChips();

        if (els.searchClear) {
            els.searchClear.classList.toggle('is-visible', !!state.search.trim());
        }

        const showEmpty = total === 0;
        if (els.empty) {
            els.empty.hidden = !showEmpty;
            els.empty.classList.toggle('is-hidden', !showEmpty);
        }
        if (showEmpty) {
            els.tablePanel?.classList.add('is-hidden');
            els.cardsPanel?.classList.add('is-hidden');
            if (els.tableBody) els.tableBody.innerHTML = '';
            if (els.cardsBody) els.cardsBody.innerHTML = '';
            renderEmpty((window.allSuppliers || []).length);
            return;
        }

        renderEmpty((window.allSuppliers || []).length);
        updateMeta(total, pageItems);

        if (state.view === 'cards') {
            els.tablePanel?.classList.add('is-hidden');
            els.cardsPanel?.classList.remove('is-hidden');
            if (els.tableBody) els.tableBody.innerHTML = '';
            renderCards(pageItems);
            renderPagination(els.paginationCards, totalPages);
            if (els.paginationTable) els.paginationTable.innerHTML = '';
        } else {
            els.cardsPanel?.classList.add('is-hidden');
            els.tablePanel?.classList.remove('is-hidden');
            if (els.cardsBody) els.cardsBody.innerHTML = '';
            renderTable(pageItems);
            renderPagination(els.paginationTable, totalPages);
            if (els.paginationCards) els.paginationCards.innerHTML = '';
        }
    }

    window.renderSuppliersActiveTab = render;

    // Events
    document.querySelectorAll('#crm-suppliers-workspace .crm-view-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (btn.disabled) return;
            setView(btn.dataset.view);
        });
    });

    const onSearch = debounce(() => {
        state.search = els.search?.value || '';
        state.page = 1;
        syncUrl();
        render();
    }, 400);
    els.search?.addEventListener('input', onSearch);
    els.searchClear?.addEventListener('click', () => {
        if (els.search) els.search.value = '';
        state.search = '';
        state.page = 1;
        syncUrl();
        render();
        els.search?.focus();
    });

    els.filtersBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = els.filtersPanel?.classList.contains('is-open');
        if (open) closeFilters();
        else openFilters();
    });
    els.filtersBackdrop?.addEventListener('click', closeFilters);
    els.filtersApply?.addEventListener('click', () => {
        state.draftFilters = {
            type: els.filterType?.value || 'all',
            city: els.filterCity?.value || '',
            sphere: els.filterSphere?.value || '',
            brand: els.filterBrand?.value || '',
        };
        applyFiltersFromDraft();
    });
    els.filtersReset?.addEventListener('click', resetFilters);

    els.chips?.addEventListener('click', (e) => {
        const clear = e.target.closest('[data-chip-clear]');
        if (clear) {
            resetFilters();
            return;
        }
        const btn = e.target.closest('[data-chip-remove]');
        if (!btn) return;
        const key = btn.dataset.chipRemove;
        if (key === 'type') state.filters.type = 'all';
        if (key === 'city') state.filters.city = '';
        if (key === 'sphere') state.filters.sphere = '';
        if (key === 'brand') state.filters.brand = '';
        state.draftFilters = { ...state.filters };
        localStorage.setItem(FILTERS_KEY, JSON.stringify(state.filters));
        state.page = 1;
        render();
    });

    els.perPage?.addEventListener('change', () => {
        state.perPage = parseInt(els.perPage.value, 10) || 10;
        localStorage.setItem(PER_PAGE_KEY, String(state.perPage));
        state.page = 1;
        render();
    });

    document.querySelectorAll('#suppliers-table th[data-sort]').forEach((th) => {
        th.addEventListener('click', () => {
            const key = th.dataset.sort;
            if (!key) return;
            if (state.sortKey === key) state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
            else { state.sortKey = key; state.sortDir = 'asc'; }
            state.page = 1;
            render();
        });
    });

    els.emptyCreate?.addEventListener('click', () => els.createBtn?.click());
    els.emptyReset?.addEventListener('click', resetFilters);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#crm-suppliers-workspace .crm-actions-wrap') && !e.target.closest('#crm-action-menu-panel')) {
            /* CrmActionMenu handles outside click itself */
        }
        if (!e.target.closest('#crm-suppliers-workspace .crm-filters-wrap')) closeFilters();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeFilters();
        }
    });

    const mq = window.matchMedia('(max-width: 768px)');
    const syncMobile = () => {
        state.mobileForceCards = mq.matches;
        setView(mq.matches ? 'cards' : state.preferredView, { persist: false });
        if (!mq.matches) syncUrl();
    };
    if (mq.addEventListener) mq.addEventListener('change', syncMobile);
    else mq.addListener(syncMobile);

    syncFilterDraftInputs();
    if (typeof window.__suppliersHeaderCountInit === 'number' && els.headerCount) {
        els.headerCount.textContent = String(window.__suppliersHeaderCountInit);
    }

    // If persisted filters hide all rows, clear them so data is visible again.
    if ((window.allSuppliers || []).length > 0 && filteredSuppliers().length === 0 && hasActiveQuery()) {
        state.search = '';
        if (els.search) els.search.value = '';
        state.draftFilters = { type: 'all', city: '', sphere: '', brand: '' };
        state.filters = { ...state.draftFilters };
        localStorage.setItem(FILTERS_KEY, JSON.stringify(state.filters));
        syncFilterDraftInputs();
    }
    syncMobile();
})();
</script>
