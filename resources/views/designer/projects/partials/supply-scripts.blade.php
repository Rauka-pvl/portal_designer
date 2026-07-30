<style>
.crm-supply-toolbar {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    flex: 0 0 auto;
}
.crm-supply-toolbar-left,
.crm-supply-toolbar-right {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}
.crm-supply-toolbar-right {
    margin-left: auto;
}
.crm-supply-toolbar-right .crm-toolbar-search {
    width: 12rem;
    max-width: 100%;
    min-width: 8rem;
}
.ov-panel[data-panel="supplies"]:not(.hidden) {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}
.crm-supply-board {
    display: flex;
    align-items: stretch;
    gap: 0.75rem;
    overflow-x: auto;
    overflow-y: hidden;
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    padding-bottom: 0.25rem;
    scrollbar-gutter: stable;
}
.crm-supply-board.is-hidden,
.crm-supply-list.is-hidden {
    display: none !important;
}
.crm-supply-board .crm-board-col {
    flex: 0 0 300px;
    width: 300px;
    max-width: 300px;
    height: 100%;
    min-height: 0;
}
.crm-supply-board .crm-board-col-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}
.crm-supply-list {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
}
.crm-supply-card-hint {
    font-size: 0.65rem;
    color: var(--crm-accent);
    margin-top: 0.25rem;
}
.crm-supply-list table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}
.crm-supply-list th,
.crm-supply-list td {
    padding: 0.45rem 0.55rem;
    border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 35%, transparent);
    text-align: left;
}
.crm-supply-list tbody tr {
    cursor: pointer;
}
.crm-supply-list tbody tr:hover {
    background: color-mix(in srgb, var(--crm-surface-2) 65%, transparent);
}
.crm-supply-modal {
    width: 90vw;
    max-width: 1140px;
    height: 90dvh;
}
.crm-catalog-product {
    display: flex;
    gap: 0.65rem;
    align-items: flex-start;
    padding: 0.55rem;
    border: 1px solid color-mix(in srgb, var(--crm-border) 35%, transparent);
    border-radius: 0.55rem;
    margin-bottom: 0.45rem;
}
.crm-catalog-product.is-selected {
    border-color: var(--crm-accent);
    background: color-mix(in srgb, var(--crm-accent) 8%, transparent);
}
.crm-supply-detail-section {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 25%, transparent);
}
.crm-supply-detail-section:last-child {
    border-bottom: 0;
    margin-bottom: 0;
}
.crm-supply-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.5rem 0.75rem;
    font-size: 0.8125rem;
}
.crm-supply-detail-grid dt {
    color: var(--crm-muted);
    font-size: 0.7rem;
}
.crm-supply-detail-grid dd {
    margin: 0;
    font-weight: 500;
}
.crm-supply-product-row {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.35rem 0;
    border-bottom: 1px solid color-mix(in srgb, var(--crm-border) 20%, transparent);
    font-size: 0.8125rem;
}
.crm-supply-qty-stepper {
    display: inline-flex;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--crm-border) 45%, transparent);
    border-radius: 0.4rem;
    overflow: hidden;
}
.crm-supply-qty-stepper button {
    width: 1.65rem;
    height: 1.65rem;
    border: 0;
    background: var(--crm-surface-2);
    cursor: pointer;
}
.crm-supply-qty-stepper input {
    width: 2.5rem;
    border: 0;
    text-align: center;
    font-size: 0.75rem;
    background: transparent;
}
.crm-supply-board .crm-board-empty {
    font-size: 0.75rem;
    color: var(--crm-muted);
    padding: 0.75rem 0.35rem;
    text-align: center;
    pointer-events: none;
    user-select: none;
}
@media (max-width: 1024px) {
    .crm-supply-list .crm-table th:nth-child(6),
    .crm-supply-list .crm-table td:nth-child(6) {
        display: none;
    }
}
@media (max-width: 768px) {
    .crm-supply-toolbar {
        flex-wrap: wrap;
    }
    .crm-supply-toolbar-left {
        flex: 1 1 auto;
    }
    .crm-supply-toolbar-right {
        flex: 1 1 100%;
        margin-left: 0;
        width: 100%;
    }
    .crm-supply-toolbar-right .crm-toolbar-search {
        flex: 1 1 auto;
        width: auto;
        min-width: 0;
    }
    .crm-supply-board .crm-board-col {
        flex: 0 0 min(300px, 85vw);
        width: min(300px, 85vw);
        max-width: min(300px, 85vw);
    }
    .crm-supply-list .crm-table th:nth-child(4),
    .crm-supply-list .crm-table td:nth-child(4),
    .crm-supply-list .crm-table th:nth-child(5),
    .crm-supply-list .crm-table td:nth-child(5) {
        display: none;
    }
}
</style>

<script>
(function () {
    'use strict';

    const bridge = window.CrmProjectBridge || {};
    const csrf = bridge.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const locale = document.getElementById('crm-workspace')?.dataset.locale || 'ru-RU';
    const storageBase = @json(asset('storage'));

    const suppliers = @json($suppliersData ?? []);
    const categoryOptions = @json($categoryOptions ?? []);
    const roomOptions = @json($roomOptions ?? []);
    const supplyPipeline = @json($supplyPipeline ?? ['stages' => []]);

    const routes = {
        store: @json(route('supplier-orders.store')),
        update: (id) => @json(url('/supplier-orders')) + '/' + id,
        show: (id) => @json(url('/supplier-orders')) + '/' + id,
        status: (id) => @json(url('/supplier-orders')) + '/' + id + '/status',
        offerAccept: (id) => @json(url('/supplier-orders')) + '/' + id + '/offer/accept',
        offerReject: (id) => @json(url('/supplier-orders')) + '/' + id + '/offer/reject',
        offerCounter: (id) => @json(url('/supplier-orders')) + '/' + id + '/offer/counter',
        productsJson: (supplierId) => @json(url('/suppliers')) + '/' + supplierId + '/products.json',
    };

    const i18n = {
        notSpecified: @json(__('projects.not_specified')),
        currency: @json(__('projects.currency_symbol')),
        draftColumn: @json(__('projects.draft_column')),
        noOrdersTitle: @json(__('projects.supplies_empty_title')),
        noOrdersBody: @json(__('projects.supplies_empty_body')),
        noOrdersLegacy: @json(__('supplier-orders.no_orders')),
        noSuppliesInColumn: @json(__('projects.supplies_column_empty')),
        productsLabel: @json(__('projects.supply_products')),
        bonusLabel: @json(__('supplier-orders.offer_bonus_label')),
        actionsLabel: @json(__('projects.actions')),
        openLabel: @json(__('projects.open_project')),
        viewKey: 'project_supplies_view_mode',
        searchKey: 'project_supplies_search',
        statusKey: 'project_supplies_status_filter',
        newSupplyTitle: @json(__('projects.new_supply_title')),
        newSupplySubtitle: @json(__('projects.new_supply_subtitle')),
        editSupply: @json(__('supplier-orders.edit')),
        supplyDetailTitle: @json(__('projects.supply_detail_title')),
        selectSupplier: @json(__('supplier-orders.select_supplier')),
        selectCategory: @json(__('supplier-orders.select_category')),
        selectRoom: @json(__('supplier-orders.select_room')),
        filterStatus: @json(__('projects.supply_filter_status')),
        supplyTotal: @json(__('projects.supply_total')),
        supplyQty: @json(__('projects.supply_qty')),
        noProducts: @json(__('projects.supply_no_products')),
        noSupplierProducts: @json(__('projects.supply_no_supplier_products')),
        selectSupplierFirst: @json(__('projects.supply_select_supplier_first')),
        changeSupplierWarn: @json(__('projects.supply_change_supplier_warn')),
        bonusHint: @json(__('supplier-orders.bonus_percent_hint')),
        stepsEmpty: @json(__('supplier-orders.project_steps_empty')),
        stepsSection: @json(__('supplier-orders.project_steps_for_supplier')),
        sectionMain: @json(__('supplier-orders.section_main')),
        sectionFinance: @json(__('supplier-orders.section_finance')),
        sectionDates: @json(__('supplier-orders.section_dates')),
        paymentSection: @json(__('projects.supply_payment')),
        productsSection: @json(__('projects.supply_products')),
        historySection: @json(__('projects.supply_history')),
        termsSection: @json(__('projects.supply_terms')),
        filesSection: @json(__('projects.supply_files')),
        linksSection: @json(__('supplier-orders.links')),
        pasteLink: @json(__('supplier-orders.paste_link')),
        offerPendingSupplier: @json(__('supplier-orders.offer_pending_supplier')),
        offerPendingDesigner: @json(__('supplier-orders.offer_pending_designer')),
        offerAccepted: @json(__('supplier-orders.offer_accepted')),
        offerRejected: @json(__('supplier-orders.offer_rejected')),
        awaitingAdvance: @json(__('projects.supply_awaiting_advance')),
        awaitingPayment: @json(__('projects.supply_awaiting_payment')),
        offerAccept: @json(__('supplier-orders.offer_accept')),
        offerReject: @json(__('supplier-orders.offer_reject')),
        offerCounter: @json(__('supplier-orders.offer_counter')),
        offerCounterSend: @json(__('supplier-orders.offer_counter_send')),
        offerHistory: @json(__('supplier-orders.offer_history')),
        offerBySupplier: @json(__('supplier-orders.offer_by_supplier')),
        offerByDesigner: @json(__('supplier-orders.offer_by_designer')),
        offerBonusLabel: @json(__('supplier-orders.offer_bonus_label')),
        selectedProducts: @json(__('projects.supply_selected_products')),
        catalogApply: @json(__('projects.supply_add_selected')),
        saving: @json(__('projects.supply_saving')),
        sending: @json(__('projects.supply_sending')),
        error: @json(__('supplier-orders.error')),
        created: @json(__('supplier-orders.created')),
        saved: @json(__('supplier-orders.saved')),
        supplier: @json(__('supplier-orders.supplier')),
        amount: @json(__('supplier-orders.amount')),
        status: @json(__('supplier-orders.status')),
        plannedDate: @json(__('supplier-orders.planned_date')),
        productService: @json(__('supplier-orders.product_service')),
        statusLabels: {
            draft: @json(__('supplier-orders.status_draft')),
            order_created: @json(__('supplier-orders.status_order_created')),
            order_confirmed: @json(__('supplier-orders.status_order_confirmed')),
            advance_payment: @json(__('supplier-orders.status_advance_payment')),
            full_payment: @json(__('supplier-orders.status_full_payment')),
            delivery_completed: @json(__('supplier-orders.status_delivery_completed')),
        },
        listHeaders: {
            supplier: @json(__('supplier-orders.supplier')),
            amount: @json(__('supplier-orders.amount')),
            status: @json(__('supplier-orders.status')),
            planned: @json(__('supplier-orders.planned_date')),
            products: @json(__('projects.supply_products')),
            hint: @json(__('projects.supply_terms')),
        },
    };

    const toast = typeof bridge.toast === 'function'
        ? bridge.toast
        : (msg, type) => {
            if (type === 'success') return;
            if (typeof window.projectAlert === 'function') window.projectAlert(type === 'error' ? 'error' : type, msg);
            else if (window.showAppToast) window.showAppToast(msg, type);
        };

    const escapeHtml = typeof bridge.escapeHtml === 'function'
        ? bridge.escapeHtml
        : (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const money = typeof bridge.money === 'function'
        ? bridge.money
        : (n) => {
            if (n === null || n === undefined || n === '' || Number.isNaN(Number(n))) return i18n.notSpecified;
            return Number(n).toLocaleString(locale, { maximumFractionDigits: 0 }) + ' ' + i18n.currency;
        };

    const formatDate = typeof bridge.formatDate === 'function'
        ? bridge.formatDate
        : (iso) => {
            if (!iso) return i18n.notSpecified;
            const d = new Date(iso + (String(iso).length <= 10 ? 'T00:00:00' : ''));
            if (Number.isNaN(d.getTime())) return i18n.notSpecified;
            return d.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
        };

    const getCurrentProject = typeof bridge.getCurrentProject === 'function' ? bridge.getCurrentProject : () => null;
    const refreshCurrentProject = typeof bridge.refreshCurrentProject === 'function'
        ? bridge.refreshCurrentProject
        : async () => getCurrentProject();

    const debounce = (fn, ms = 350) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    const state = {
        view: (localStorage.getItem('project_supplies_view_mode') === 'list' ? 'list' : 'kanban'),
        search: localStorage.getItem('project_supplies_search') || '',
        statusFilter: localStorage.getItem('project_supplies_status_filter') || '',
        formDirty: false,
        formSnapshot: null,
        editingOrderId: null,
        detailOrderId: null,
        catalogCart: {},
        catalogSupplierId: null,
        catalogPage: 1,
        catalogMeta: null,
        formProducts: [],
        lastSupplierId: '',
        existingFiles: [],
    };

    const els = {
        kanban: () => document.getElementById('ov-supplies-kanban'),
        list: () => document.getElementById('ov-supplies-list'),
        count: () => document.getElementById('ov-supplies-count'),
        search: () => document.getElementById('ov-supply-search'),
        statusFilter: () => document.getElementById('ov-supply-status-filter'),
        addBtn: () => document.getElementById('ov-add-supply'),
        supplyRoot: () => document.getElementById('supply-modal-root'),
        catalogRoot: () => document.getElementById('supply-catalog-root'),
        detailRoot: () => document.getElementById('supply-detail-root'),
        unsavedRoot: () => document.getElementById('supply-unsaved-modal'),
        form: () => document.getElementById('supply-form'),
    };

    function statusLabel(key) {
        return i18n.statusLabels[key] || key || i18n.notSpecified;
    }

    function effectiveStatus(order) {
        return order.workflow_status || order.status || 'draft';
    }

    function cardHint(order) {
        const os = order.offer_status;
        if (os === 'pending_supplier') return i18n.offerPendingSupplier;
        if (os === 'pending_designer') return i18n.offerPendingDesigner;
        if (os === 'rejected') return i18n.offerRejected;
        if (os === 'accepted' && order.status === 'advance_payment') return i18n.awaitingAdvance;
        if (os === 'accepted' && order.status === 'full_payment') return i18n.awaitingPayment;
        if (!order.is_sent_to_supplier) return i18n.statusLabels.draft;
        return '';
    }

    function filteredOrders(project) {
        const orders = project?.supplier_orders || [];
        const q = state.search.trim().toLowerCase();
        return orders.filter((o) => {
            const st = effectiveStatus(o);
            if (state.statusFilter && st !== state.statusFilter) return false;
            if (!q) return true;
            const hay = [
                o.supplier_name,
                o.comment,
                o.id,
                st,
                statusLabel(st),
                cardHint(o),
            ].join(' ').toLowerCase();
            return hay.includes(q);
        });
    }

    function buildColumns() {
        const cols = [{
            system_key: 'draft',
            name: i18n.draftColumn,
            color: '#94a3b8',
        }];
        (supplyPipeline.stages || []).forEach((s) => cols.push(s));
        return cols;
    }

    function populateStatusFilter() {
        const sel = els.statusFilter();
        if (!sel || sel.dataset.supplyInit === '1') return;
        sel.dataset.supplyInit = '1';
        sel.innerHTML = `<option value="">${escapeHtml(i18n.filterStatus)}</option>` +
            buildColumns().map((c) => `<option value="${escapeHtml(c.system_key)}">${escapeHtml(c.name)}</option>`).join('');
    }

    function fillSelectOptions() {
        const supSel = document.getElementById('supply-supplier-id');
        if (supSel && !supSel.dataset.supplyInit) {
            supSel.dataset.supplyInit = '1';
            supSel.innerHTML = `<option value="">${escapeHtml(i18n.selectSupplier)}</option>` +
                suppliers.map((s) => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
        }
        const catSel = document.getElementById('supply-category');
        if (catSel && !catSel.dataset.supplyInit) {
            catSel.dataset.supplyInit = '1';
            catSel.innerHTML = `<option value="">${escapeHtml(i18n.selectCategory)}</option>` +
                Object.entries(categoryOptions).map(([k, v]) => `<option value="${escapeHtml(k)}">${escapeHtml(v)}</option>`).join('');
        }
        const roomSel = document.getElementById('supply-room');
        if (roomSel && !roomSel.dataset.supplyInit) {
            roomSel.dataset.supplyInit = '1';
            roomSel.innerHTML = `<option value="">${escapeHtml(i18n.selectRoom)}</option>` +
                Object.entries(roomOptions).map(([k, v]) => `<option value="${escapeHtml(k)}">${escapeHtml(v)}</option>`).join('');
        }
    }

    function renderEmptyState(container) {
        container.innerHTML = `<div class="crm-empty-inline">
            <div class="font-medium text-sm">${escapeHtml(i18n.noOrdersTitle)}</div>
            <div class="text-xs text-[var(--crm-muted)] mt-1">${escapeHtml(i18n.noOrdersBody)}</div>
        </div>`;
    }

    function productsCount(order) {
        const items = order?.product_items;
        if (Array.isArray(items)) return items.length;
        if (typeof order?.products_count === 'number') return order.products_count;
        return 0;
    }

    function setSupplyView(view, { persist = true, rerender = true } = {}) {
        state.view = view === 'list' ? 'list' : 'kanban';
        document.querySelectorAll('.crm-supply-view-btn').forEach((btn) => {
            const on = btn.dataset.sview === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        const kanban = els.kanban();
        const list = els.list();
        if (state.view === 'kanban') {
            if (list) { list.classList.add('is-hidden'); list.innerHTML = ''; }
            kanban?.classList.remove('is-hidden');
        } else {
            if (kanban) { kanban.classList.add('is-hidden'); kanban.innerHTML = ''; }
            list?.classList.remove('is-hidden');
        }
        if (persist) localStorage.setItem('project_supplies_view_mode', state.view);
        if (rerender) renderSupplies(getCurrentProject());
    }

    function supplyCardEl(order) {
        const el = document.createElement('div');
        el.className = 'crm-board-card';
        el.dataset.orderId = String(order.id);
        if (order.is_in_funnel) el.draggable = true;

        const bonus = order.bonus_percent != null && order.bonus_percent !== ''
            ? `${Number(order.bonus_percent)}%`
            : i18n.notSpecified;
        const hint = cardHint(order);
        const count = productsCount(order);
        el.innerHTML = `
            <div class="crm-board-card-title truncate" title="${escapeHtml(order.supplier_name || ('#' + order.id))}">${escapeHtml(order.supplier_name || ('#' + order.id))}</div>
            <div class="crm-board-card-meta text-xs">
                <div>${escapeHtml(i18n.productsLabel)}: <strong>${count}</strong></div>
                <div>${escapeHtml(i18n.amount)}: <strong>${escapeHtml(money(order.summa ?? order.amount))}</strong></div>
                <div>${escapeHtml(i18n.bonusLabel)}: <strong>${escapeHtml(bonus)}</strong></div>
            </div>
            ${hint ? `<div class="crm-supply-card-hint">${escapeHtml(hint)}</div>` : ''}`;

        if (order.is_in_funnel) {
            el.addEventListener('dragstart', (e) => {
                el.classList.add('is-dragging');
                e.dataTransfer.setData('text/plain', String(order.id));
                e.dataTransfer.effectAllowed = 'move';
            });
            el.addEventListener('dragend', () => el.classList.remove('is-dragging'));
        }

        el.addEventListener('click', () => openDetail(order.id));
        return el;
    }

    async function moveSupplyStatus(orderId, status) {
        const project = getCurrentProject();
        const order = (project?.supplier_orders || []).find((o) => Number(o.id) === Number(orderId));
        if (!order || effectiveStatus(order) === status) return;
        const prev = order.status;
        order.status = status;
        order.workflow_status = status;
        renderSupplies(project);
        try {
            const res = await fetch(routes.status(orderId), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ status }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            const refreshed = await refreshCurrentProject();
            if (refreshed) renderSupplies(refreshed);
        } catch (e) {
            order.status = prev;
            order.workflow_status = prev;
            renderSupplies(getCurrentProject());
            toast(e.message || i18n.error, 'error');
        }
    }

    function renderKanban(project) {
        const wrap = els.kanban();
        const listWrap = els.list();
        if (!wrap) return;
        if (listWrap) { listWrap.classList.add('is-hidden'); listWrap.innerHTML = ''; }
        wrap.classList.remove('is-hidden');

        const orders = filteredOrders(project);
        wrap.innerHTML = '';
        buildColumns().forEach((stage) => {
            const col = document.createElement('div');
            col.className = 'crm-board-col';
            col.style.setProperty('--col-color', stage.color || '#64748b');
            const cards = orders.filter((o) => effectiveStatus(o) === stage.system_key);
            col.innerHTML = `
                <div class="crm-board-col-head">
                    <span class="crm-board-col-title" title="${escapeHtml(stage.name)}">${escapeHtml(stage.name)}</span>
                    <span class="crm-board-col-count">${cards.length}</span>
                </div>
                <div class="crm-board-col-body" data-drop="${escapeHtml(stage.system_key)}"></div>`;
            const body = col.querySelector('[data-drop]');
            const highlight = (on) => col.classList.toggle('is-drop-target', on);
            const allowDrop = stage.system_key !== 'draft';

            if (allowDrop) {
                body.addEventListener('dragover', (e) => { e.preventDefault(); highlight(true); });
                body.addEventListener('dragleave', (e) => { if (!body.contains(e.relatedTarget)) highlight(false); });
                col.addEventListener('dragover', (e) => { e.preventDefault(); highlight(true); });
                col.addEventListener('dragleave', (e) => { if (!col.contains(e.relatedTarget)) highlight(false); });
                const onDrop = async (e) => {
                    e.preventDefault();
                    highlight(false);
                    const id = Number(e.dataTransfer.getData('text/plain'));
                    const o = orders.find((x) => Number(x.id) === id)
                        || (project?.supplier_orders || []).find((x) => Number(x.id) === id);
                    if (!o?.is_in_funnel) return;
                    await moveSupplyStatus(id, stage.system_key);
                };
                body.addEventListener('drop', onDrop);
                col.addEventListener('drop', onDrop);
            }

            if (!cards.length) {
                body.innerHTML = `<div class="crm-board-empty">${escapeHtml(i18n.noSuppliesInColumn)}</div>`;
            } else {
                cards.forEach((o) => body.appendChild(supplyCardEl(o)));
            }
            wrap.appendChild(col);
        });
    }

    function renderListView(project) {
        const wrap = els.list();
        const kanban = els.kanban();
        if (!wrap) return;
        if (kanban) { kanban.classList.add('is-hidden'); kanban.innerHTML = ''; }
        wrap.classList.remove('is-hidden');

        const orders = filteredOrders(project);
        if (!orders.length) {
            renderEmptyState(wrap);
            return;
        }

        wrap.innerHTML = `<table class="crm-table"><thead><tr>
            <th>${escapeHtml(i18n.listHeaders.supplier)}</th>
            <th>${escapeHtml(i18n.listHeaders.amount)}</th>
            <th>${escapeHtml(i18n.listHeaders.status)}</th>
            <th class="hidden md:table-cell">${escapeHtml(i18n.listHeaders.planned)}</th>
            <th class="hidden md:table-cell">${escapeHtml(i18n.listHeaders.products)}</th>
            <th class="hidden lg:table-cell">${escapeHtml(i18n.listHeaders.hint)}</th>
            <th>${escapeHtml(i18n.actionsLabel)}</th>
        </tr></thead><tbody>${orders.map((o) => {
            const st = effectiveStatus(o);
            const hint = cardHint(o) || i18n.notSpecified;
            return `<tr data-order-id="${o.id}">
                <td data-label="${escapeHtml(i18n.listHeaders.supplier)}">${escapeHtml(o.supplier_name || ('#' + o.id))}</td>
                <td data-label="${escapeHtml(i18n.listHeaders.amount)}">${escapeHtml(money(o.summa ?? o.amount))}</td>
                <td data-label="${escapeHtml(i18n.listHeaders.status)}"><span class="crm-status-badge">${escapeHtml(statusLabel(st))}</span></td>
                <td class="hidden md:table-cell" data-label="${escapeHtml(i18n.listHeaders.planned)}">${escapeHtml(formatDate(o.date_planned))}</td>
                <td class="hidden md:table-cell" data-label="${escapeHtml(i18n.listHeaders.products)}">${productsCount(o)}</td>
                <td class="hidden lg:table-cell text-[var(--crm-accent)] text-xs" data-label="${escapeHtml(i18n.listHeaders.hint)}">${escapeHtml(hint)}</td>
                <td data-label=""><button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-open-supply="${o.id}">${escapeHtml(i18n.openLabel)}</button></td>
            </tr>`;
        }).join('')}</tbody></table>`;

        wrap.querySelectorAll('tbody tr').forEach((tr) => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                openDetail(Number(tr.dataset.orderId));
            });
        });
        wrap.querySelectorAll('[data-open-supply]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openDetail(Number(btn.dataset.openSupply));
            });
        });
    }

    function renderSupplies(project) {
        populateStatusFilter();
        const p = project || getCurrentProject();
        const orders = p?.supplier_orders || [];
        const countEl = els.count();
        if (countEl) countEl.textContent = String(orders.length);

        // Sync toggle UI without recursive re-render
        document.querySelectorAll('.crm-supply-view-btn').forEach((btn) => {
            const on = btn.dataset.sview === state.view;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });

        if (state.view === 'list') renderListView(p);
        else renderKanban(p);
    }

    function clearSupplyFieldErrors() {
        document.querySelectorAll('#supply-form [data-error]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showSupplyFieldErrors(errors) {
        clearSupplyFieldErrors();
        Object.entries(errors || {}).forEach(([key, msgs]) => {
            const el = document.querySelector(`#supply-form [data-error="${key}"]`);
            if (el) {
                el.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
                el.classList.remove('hidden');
            }
        });
    }

    function readFormSnapshot() {
        const links = [...document.querySelectorAll('#supply-links input[data-link-url]')].map((i) => i.value.trim()).filter(Boolean);
        const stepIds = [...document.querySelectorAll('#supply-steps-box input[type=checkbox]:checked')].map((cb) => cb.value);
        return JSON.stringify({
            supplier_id: document.getElementById('supply-supplier-id')?.value || '',
            summa: document.getElementById('supply-summa')?.value || '',
            bonus: document.getElementById('supply-bonus')?.value || '',
            category: document.getElementById('supply-category')?.value || '',
            mark: document.getElementById('supply-mark')?.value || '',
            room: document.getElementById('supply-room')?.value || '',
            date_planned: document.getElementById('supply-date-planned')?.value || '',
            date_actual: document.getElementById('supply-date-actual')?.value || '',
            prepay_date: document.getElementById('supply-prepay-date')?.value || '',
            prepay_amount: document.getElementById('supply-prepay-amount')?.value || '',
            pay_date: document.getElementById('supply-pay-date')?.value || '',
            pay_amount: document.getElementById('supply-pay-amount')?.value || '',
            comment: document.getElementById('supply-comment')?.value || '',
            links,
            stepIds,
            products: state.formProducts,
        });
    }

    function markFormDirty() {
        state.formDirty = true;
    }

    function resetFormDirty() {
        state.formDirty = false;
        state.formSnapshot = readFormSnapshot();
    }

    function isFormDirty() {
        if (!state.formSnapshot) return state.formDirty;
        return state.formDirty || readFormSnapshot() !== state.formSnapshot;
    }

    function updateBonusHint() {
        const hint = document.getElementById('supply-bonus-hint');
        if (!hint) return;
        const summa = parseFloat(document.getElementById('supply-summa')?.value) || 0;
        const percent = parseFloat(String(document.getElementById('supply-bonus')?.value || '').replace(',', '.')) || 0;
        const bonus = Math.round(summa * percent / 100);
        hint.textContent = i18n.bonusHint + ' ' + bonus.toLocaleString(locale, { maximumFractionDigits: 0 }) + ' ' + i18n.currency;
    }

    function renderProjectSteps(project, preselected) {
        const box = document.getElementById('supply-steps-box');
        if (!box) return;
        const pre = new Set((preselected || []).map((x) => Number(x)));
        const stages = project?.stages || [];
        let html = '';
        let has = false;
        stages.forEach((st) => {
            const steps = (st.steps || []).filter((s) => s.result_comment != null && String(s.result_comment).trim() !== '' && s.id != null);
            if (!steps.length) return;
            has = true;
            html += `<div class="mb-2"><div class="text-xs font-semibold text-[var(--crm-muted)] mb-1">${escapeHtml(st.stage_type_label || st.stage_type || '')}</div>`;
            steps.forEach((step) => {
                const checked = pre.has(Number(step.id)) ? ' checked' : '';
                html += `<label class="flex items-start gap-2 cursor-pointer mb-1">
                    <input type="checkbox" class="mt-0.5" name="included_step_ids[]" value="${step.id}"${checked}>
                    <span>${escapeHtml(step.title || '')}</span>
                </label>`;
            });
            html += '</div>';
        });
        box.innerHTML = has ? html : `<div class="text-xs text-[var(--crm-muted)]">${escapeHtml(i18n.stepsEmpty)}</div>`;
        box.querySelectorAll('input[type=checkbox]').forEach((cb) => cb.addEventListener('change', markFormDirty));
    }

    function renderLinks(links) {
        const box = document.getElementById('supply-links');
        if (!box) return;
        const rows = (links && links.length) ? links : [''];
        box.innerHTML = rows.map((url) => `
            <div class="flex gap-2">
                <input type="url" data-link-url class="crm-input flex-1" placeholder="${escapeHtml(i18n.pasteLink)}" value="${escapeHtml(typeof url === 'string' ? url : (url?.url || ''))}">
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-rm-link>×</button>
            </div>`).join('');
        box.querySelectorAll('[data-rm-link]').forEach((btn) => btn.addEventListener('click', () => {
            btn.closest('.flex')?.remove();
            if (!box.children.length) renderLinks(['']);
            markFormDirty();
        }));
        box.querySelectorAll('input').forEach((inp) => {
            inp.addEventListener('input', markFormDirty);
        });
    }

    function renderFiles(items) {
        const box = document.getElementById('supply-files-list');
        if (!box) return;
        state.existingFiles = (items || []).map((f) => (typeof f === 'string' ? f : f.path)).filter(Boolean);
        const list = items || [];
        if (!list.length) { box.innerHTML = ''; return; }
        box.innerHTML = list.map((f) => {
            const name = typeof f === 'string' ? f.split('/').pop() : (f.name || f.path);
            const url = typeof f === 'string' ? (storageBase + '/' + f) : f.url;
            const path = typeof f === 'string' ? f : f.path;
            return `<div class="flex items-center justify-between gap-2" data-path="${escapeHtml(path)}">
                <a class="text-[var(--crm-accent)] truncate" href="${escapeHtml(url)}" target="_blank">${escapeHtml(name)}</a>
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-rm-file>×</button>
            </div>`;
        }).join('');
        box.querySelectorAll('[data-rm-file]').forEach((btn) => btn.addEventListener('click', () => {
            const path = btn.closest('[data-path]').dataset.path;
            state.existingFiles = (state.existingFiles || []).filter((p) => p !== path);
            btn.closest('[data-path]').remove();
            markFormDirty();
        }));
    }

    function recalcProducts() {
        let total = 0;
        const parts = [];
        state.formProducts.forEach((p) => {
            const qty = Math.max(1, Number(p.qty) || 1);
            const price = Number(p.price) || 0;
            total += qty * price;
            parts.push(`${p.name} × ${qty}${p.unit ? ' ' + p.unit : ''}`);
        });

        const list = document.getElementById('supply-products-list');
        const totalEl = document.getElementById('supply-products-total');
        const itemsField = document.getElementById('supply-product-items');
        const summaEl = document.getElementById('supply-summa');
        const commentEl = document.getElementById('supply-comment');

        if (list) {
            if (!state.formProducts.length) {
                list.innerHTML = `<div class="text-xs text-[var(--crm-muted)] py-2">${escapeHtml(i18n.noProducts)}</div>`;
            } else {
                list.innerHTML = state.formProducts.map((p, idx) => `
                    <div class="crm-supply-product-row" data-idx="${idx}">
                        <div class="min-w-0">
                            <div class="truncate">${escapeHtml(p.name)}</div>
                            <div class="text-xs text-[var(--crm-muted)]">${Number(p.price || 0).toLocaleString(locale)} ${escapeHtml(i18n.currency)}${p.unit ? ' / ' + escapeHtml(p.unit) : ''}</div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="crm-supply-qty-stepper">
                                <button type="button" data-dec>−</button>
                                <input type="number" min="1" value="${Math.max(1, Number(p.qty) || 1)}" data-qty>
                                <button type="button" data-inc>+</button>
                            </div>
                            <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-rm>×</button>
                        </div>
                    </div>`).join('');

                list.querySelectorAll('[data-idx]').forEach((row) => {
                    const idx = Number(row.dataset.idx);
                    const setQty = (q) => {
                        state.formProducts[idx].qty = Math.max(1, q);
                        recalcProducts();
                        markFormDirty();
                    };
                    row.querySelector('[data-dec]')?.addEventListener('click', () => setQty((Number(row.querySelector('[data-qty]').value) || 1) - 1));
                    row.querySelector('[data-inc]')?.addEventListener('click', () => setQty((Number(row.querySelector('[data-qty]').value) || 1) + 1));
                    row.querySelector('[data-qty]')?.addEventListener('change', (e) => setQty(Number(e.target.value) || 1));
                    row.querySelector('[data-rm]')?.addEventListener('click', () => {
                        state.formProducts.splice(idx, 1);
                        recalcProducts();
                        markFormDirty();
                    });
                });
            }
        }

        if (totalEl) totalEl.textContent = Math.round(total).toLocaleString(locale) + ' ' + i18n.currency;
        if (itemsField) itemsField.value = state.formProducts.length
            ? JSON.stringify(state.formProducts.map((p) => ({ id: p.id, qty: Math.max(1, Number(p.qty) || 1) })))
            : '';
        if (state.formProducts.length && summaEl) summaEl.value = String(Math.round(total));
        if (state.formProducts.length && commentEl && !state.editingOrderId) commentEl.value = parts.join('; ');
        updateBonusHint();
    }

    function resetSupplyForm() {
        state.editingOrderId = null;
        state.formProducts = [];
        state.existingFiles = [];
        state.lastSupplierId = '';
        const form = els.form();
        form?.reset();
        document.getElementById('supply-order-id').value = '';
        document.getElementById('supply-send-flag').value = '0';
        document.getElementById('supply-product-items').value = '';
        document.getElementById('supply-products-section')?.classList.remove('is-hidden');
        clearSupplyFieldErrors();
        renderLinks(['']);
        renderFiles([]);
        recalcProducts();
    }

    function openSupplyModalRoot() {
        const root = els.supplyRoot();
        root?.classList.add('open');
        root?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeSupplyModalRoot(force) {
        if (!force && isFormDirty()) {
            const unsaved = els.unsavedRoot();
            unsaved?.classList.add('open');
            unsaved?.setAttribute('aria-hidden', 'false');
            return;
        }
        els.unsavedRoot()?.classList.remove('open');
        els.supplyRoot()?.classList.remove('open');
        els.supplyRoot()?.setAttribute('aria-hidden', 'true');
        if (!els.detailRoot()?.classList.contains('open') && !els.catalogRoot()?.classList.contains('open')) {
            document.body.style.overflow = '';
        }
        state.formDirty = false;
        state.formSnapshot = null;
    }

    function openSupplyCreate(prefill) {
        const project = getCurrentProject();
        if (!project?.id) {
            toast(i18n.error, 'error');
            return;
        }
        resetSupplyForm();
        document.getElementById('supply-project-id').value = String(project.id);
        document.getElementById('supply-ctx-project').textContent = project.name || '—';
        document.getElementById('supply-ctx-client').textContent = project.client_name || i18n.notSpecified;
        document.getElementById('supply-modal-title').textContent = i18n.newSupplyTitle;
        document.getElementById('supply-modal-subtitle').textContent = i18n.newSupplySubtitle.replace(':name', project.name || '');

        renderProjectSteps(project, []);
        if (prefill) applyOrderToForm(prefill, { isEdit: false });
        resetFormDirty();
        openSupplyModalRoot();
    }

    function applyOrderToForm(order, opts = {}) {
        const isEdit = !!opts.isEdit;
        state.editingOrderId = isEdit ? order.id : null;
        document.getElementById('supply-order-id').value = isEdit ? String(order.id) : '';
        document.getElementById('supply-supplier-id').value = order.supplier_id || '';
        state.lastSupplierId = String(order.supplier_id || '');
        document.getElementById('supply-summa').value = order.summa ?? order.amount ?? '';
        document.getElementById('supply-bonus').value = order.bonus_percent != null ? order.bonus_percent : '';
        document.getElementById('supply-category').value = order.category || '';
        document.getElementById('supply-mark').value = order.mark || '';
        document.getElementById('supply-room').value = order.room || '';
        document.getElementById('supply-date-planned').value = (order.date_planned || order.planned_date || '').slice(0, 10);
        document.getElementById('supply-date-actual').value = (order.date_actual || order.actual_date || '').slice(0, 10);
        document.getElementById('supply-prepay-date').value = (order.prepayment_date || '').slice(0, 10);
        document.getElementById('supply-prepay-amount').value = order.prepayment_amount ?? '';
        document.getElementById('supply-pay-date').value = (order.payment_date || '').slice(0, 10);
        document.getElementById('supply-pay-amount').value = order.payment_amount ?? '';
        document.getElementById('supply-comment').value = order.comment || order.product_service || '';

        const productsSection = document.getElementById('supply-products-section');
        if (isEdit) {
            productsSection?.classList.add('is-hidden');
            state.formProducts = [];
        } else {
            productsSection?.classList.remove('is-hidden');
            state.formProducts = (order.product_items || []).map((p) => ({
                id: p.product_id || p.id,
                name: p.name || '',
                qty: p.qty || 1,
                price: p.price || 0,
                unit: p.unit || '',
            }));
        }

        renderProjectSteps(getCurrentProject(), order.included_step_ids || []);
        renderLinks(order.links || []);
        renderFiles(order.file_items || (order.files || []).map((f) => (typeof f === 'string' ? { path: f, name: f.split('/').pop(), url: storageBase + '/' + f } : f)));
        recalcProducts();
        updateBonusHint();

        document.getElementById('supply-modal-title').textContent = isEdit ? i18n.editSupply : i18n.newSupplyTitle;
        document.getElementById('supply-modal-subtitle').textContent = `#${order.id} · ${order.supplier_name || ''}`;
    }

    async function openSupplyEdit(orderId) {
        closeDetailModal();
        try {
            const res = await fetch(routes.show(orderId), { headers: { Accept: 'application/json' } });
            const order = await res.json();
            if (!res.ok || !order?.id) throw new Error(i18n.error);
            const project = getCurrentProject();
            document.getElementById('supply-project-id').value = String(project?.id || order.project_id || '');
            document.getElementById('supply-ctx-project').textContent = project?.name || order.project_name || '—';
            document.getElementById('supply-ctx-client').textContent = project?.client_name || i18n.notSpecified;
            applyOrderToForm(order, { isEdit: true });
            resetFormDirty();
            openSupplyModalRoot();
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    async function submitSupplyForm(action) {
        const form = els.form();
        if (!form) return;
        clearSupplyFieldErrors();
        recalcProducts();
        document.getElementById('supply-send-flag').value = action === 'send' ? '1' : '0';

        const saveBtn = document.getElementById('supply-save-btn');
        const sendBtn = document.getElementById('supply-send-btn');
        saveBtn.disabled = true;
        sendBtn.disabled = true;

        const fd = new FormData(form);
        fd.set('send_to_supplier', action === 'send' ? '1' : '0');
        fd.delete('action');

        [...document.querySelectorAll('#supply-steps-box input[type=checkbox]:checked')].forEach((cb) => {
            fd.append('included_step_ids[]', cb.value);
        });

        (state.existingFiles || []).forEach((f, i) => fd.append(`existing_files[${i}]`, f));

        const links = [...document.querySelectorAll('#supply-links input[data-link-url]')].map((i) => i.value.trim()).filter(Boolean);
        fd.delete('links[]');
        links.forEach((url) => fd.append('links[]', url));

        let url = routes.store;
        if (state.editingOrderId) {
            url = routes.update(state.editingOrderId);
            fd.append('_method', 'PUT');
        }
        fd.append('_token', csrf);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                if (data.errors) showSupplyFieldErrors(data.errors);
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || i18n.error);
            }
            closeSupplyModalRoot(true);
            const refreshed = await refreshCurrentProject();
            if (refreshed) renderSupplies(refreshed);
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        } finally {
            saveBtn.disabled = false;
            sendBtn.disabled = false;
        }
    }

    /* ---- Catalog ---- */
    function openCatalogModal() {
        const sid = parseInt(document.getElementById('supply-supplier-id')?.value || '0', 10);
        if (!sid) {
            toast(i18n.selectSupplierFirst, 'error');
            return;
        }
        state.catalogSupplierId = sid;
        state.catalogCart = {};
        state.catalogPage = 1;
        const supplier = suppliers.find((s) => Number(s.id) === sid);
        document.getElementById('catalog-supplier-name').textContent = supplier?.name || '';
        document.getElementById('catalog-search').value = '';
        document.getElementById('catalog-category').innerHTML = `<option value="">${escapeHtml(i18n.selectCategory)}</option>`;
        const root = els.catalogRoot();
        root?.classList.add('open');
        root?.setAttribute('aria-hidden', 'false');
        loadCatalogProducts();
    }

    function closeCatalogModal() {
        els.catalogRoot()?.classList.remove('open');
        els.catalogRoot()?.setAttribute('aria-hidden', 'true');
        if (!els.supplyRoot()?.classList.contains('open') && !els.detailRoot()?.classList.contains('open')) {
            document.body.style.overflow = '';
        }
    }

    function renderCatalogCart() {
        const cartEl = document.getElementById('catalog-cart');
        const totalEl = document.getElementById('catalog-cart-total');
        const countEl = document.getElementById('catalog-selected-count');
        const items = Object.values(state.catalogCart);
        if (countEl) countEl.textContent = i18n.selectedProducts.replace(':count', String(items.length));
        let total = 0;
        if (!cartEl) return;
        if (!items.length) {
            cartEl.innerHTML = `<div class="text-xs text-[var(--crm-muted)]">${escapeHtml(i18n.noProducts)}</div>`;
        } else {
            cartEl.innerHTML = items.map((p) => {
                total += (Number(p.qty) || 1) * (Number(p.price) || 0);
                return `<div class="flex justify-between gap-2 text-xs">
                    <span class="truncate">${escapeHtml(p.name)} × ${p.qty}</span>
                    <strong>${Math.round((Number(p.qty) || 1) * (Number(p.price) || 0)).toLocaleString(locale)} ${escapeHtml(i18n.currency)}</strong>
                </div>`;
            }).join('');
        }
        if (totalEl) totalEl.textContent = Math.round(total).toLocaleString(locale) + ' ' + i18n.currency;
    }

    async function loadCatalogProducts(page) {
        const sid = state.catalogSupplierId;
        if (!sid) return;
        state.catalogPage = page || 1;
        const q = document.getElementById('catalog-search')?.value.trim() || '';
        const category = document.getElementById('catalog-category')?.value || '';
        const grid = document.getElementById('catalog-grid');
        if (grid) grid.innerHTML = '<div class="crm-skeleton h-24"></div>';

        try {
            const params = new URLSearchParams({ q, category, page: String(state.catalogPage) });
            const res = await fetch(routes.productsJson(sid) + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(i18n.error);

            const catSel = document.getElementById('catalog-category');
            if (catSel && catSel.options.length <= 1 && Array.isArray(data.categories)) {
                catSel.innerHTML = `<option value="">${escapeHtml(i18n.selectCategory)}</option>` +
                    data.categories.map((c) => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
            }

            state.catalogMeta = data.meta || null;
            const products = data.data || [];
            if (!grid) return;
            if (!products.length) {
                grid.innerHTML = `<div class="crm-empty-inline">${escapeHtml(i18n.noSupplierProducts)}</div>`;
            } else {
                grid.innerHTML = products.map((p) => {
                    const inCart = !!state.catalogCart[p.id];
                    return `<div class="crm-catalog-product ${inCart ? 'is-selected' : ''}" data-pid="${p.id}">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate">${escapeHtml(p.name)}</div>
                            <div class="text-xs text-[var(--crm-muted)]">${escapeHtml(p.sku || '')}${p.category ? ' · ' + escapeHtml(p.category) : ''}</div>
                            <div class="text-sm mt-1">${Number(p.price || 0).toLocaleString(locale)} ${escapeHtml(i18n.currency)}${p.unit ? ' / ' + escapeHtml(p.unit) : ''}</div>
                        </div>
                        <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" data-add-product="${p.id}">${inCart ? '+' : '+'}</button>
                    </div>`;
                }).join('');

                if (state.catalogMeta && state.catalogMeta.last_page > 1) {
                    grid.innerHTML += `<div class="flex gap-2 mt-2">
                        <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-cat-page="prev" ${state.catalogPage <= 1 ? 'disabled' : ''}>←</button>
                        <span class="text-xs self-center">${state.catalogPage} / ${state.catalogMeta.last_page}</span>
                        <button type="button" class="crm-btn crm-btn-sm crm-btn-secondary" data-cat-page="next" ${state.catalogPage >= state.catalogMeta.last_page ? 'disabled' : ''}>→</button>
                    </div>`;
                }

                grid.querySelectorAll('[data-add-product]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const pid = Number(btn.dataset.addProduct);
                        const prod = products.find((x) => Number(x.id) === pid);
                        if (!prod) return;
                        if (state.catalogCart[pid]) state.catalogCart[pid].qty += 1;
                        else state.catalogCart[pid] = { id: pid, name: prod.name, price: prod.price, unit: prod.unit, qty: 1 };
                        renderCatalogCart();
                        loadCatalogProducts(state.catalogPage);
                    });
                });
                grid.querySelector('[data-cat-page="prev"]')?.addEventListener('click', () => loadCatalogProducts(state.catalogPage - 1));
                grid.querySelector('[data-cat-page="next"]')?.addEventListener('click', () => loadCatalogProducts(state.catalogPage + 1));
            }
            renderCatalogCart();
        } catch (e) {
            if (grid) grid.innerHTML = `<div class="crm-empty-inline">${escapeHtml(e.message || i18n.error)}</div>`;
        }
    }

    function applyCatalogToForm() {
        const merged = {};
        state.formProducts.forEach((p) => { merged[p.id] = { ...p }; });
        Object.values(state.catalogCart).forEach((p) => {
            if (merged[p.id]) merged[p.id].qty = (Number(merged[p.id].qty) || 1) + (Number(p.qty) || 1);
            else merged[p.id] = { ...p };
        });
        state.formProducts = Object.values(merged);
        recalcProducts();
        markFormDirty();
        closeCatalogModal();
    }

    /* ---- Detail ---- */
    function closeDetailModal() {
        els.detailRoot()?.classList.remove('open');
        els.detailRoot()?.setAttribute('aria-hidden', 'true');
        state.detailOrderId = null;
        if (!els.supplyRoot()?.classList.contains('open') && !els.catalogRoot()?.classList.contains('open')) {
            document.body.style.overflow = '';
        }
    }

    function renderDetailBody(order) {
        const body = document.getElementById('supply-detail-body');
        if (!body) return;

        const st = effectiveStatus(order);
        const products = order.product_items || [];
        const steps = order.included_steps || [];
        const history = Array.isArray(order.offer_history) ? order.offer_history : [];

        const productsHtml = products.length
            ? products.map((p) => `<div class="crm-supply-product-row">
                <span>${escapeHtml(p.name || '')}</span>
                <span>${escapeHtml(String(p.qty || 1))} × ${escapeHtml(money(p.price))}</span>
            </div>`).join('')
            : `<div class="text-xs text-[var(--crm-muted)]">${escapeHtml(order.comment || i18n.noProducts)}</div>`;

        const stepsHtml = steps.length
            ? steps.map((s) => `<div class="text-sm">• ${escapeHtml(s.title || s.name || '')}</div>`).join('')
            : `<div class="text-xs text-[var(--crm-muted)]">${escapeHtml(i18n.stepsEmpty)}</div>`;

        const files = order.file_items || [];
        const filesHtml = files.length
            ? files.map((f) => `<a class="block text-[var(--crm-accent)] text-sm truncate" href="${escapeHtml(f.url)}" target="_blank">${escapeHtml(f.name)}</a>`).join('')
            : `<div class="text-xs text-[var(--crm-muted)]">—</div>`;

        const links = order.links || [];
        const linksHtml = links.length
            ? links.map((l) => `<a class="block text-[var(--crm-accent)] text-sm truncate" href="${escapeHtml(typeof l === 'string' ? l : l.url)}" target="_blank">${escapeHtml(typeof l === 'string' ? l : (l.url || ''))}</a>`).join('')
            : `<div class="text-xs text-[var(--crm-muted)]">—</div>`;

        let offerHtml = '';
        if (order.has_offer_ui || order.offer_status) {
            const hint = cardHint(order) || statusLabel(st);
            offerHtml = `<div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.termsSection)}</div>
                <p class="text-sm text-[var(--crm-accent)] mb-2">${escapeHtml(hint)}</p>
                <dl class="crm-supply-detail-grid">
                    <div><dt>${escapeHtml(i18n.offerBonusLabel)}</dt><dd>${order.bonus_percent != null ? escapeHtml(String(order.bonus_percent)) + '%' : '—'}</dd></div>
                    <div><dt>${escapeHtml(i18n.amount)}</dt><dd>${escapeHtml(money(order.summa ?? order.amount))}</dd></div>
                </dl>
                ${order.offer_message ? `<p class="text-sm mt-2">${escapeHtml(order.offer_message)}</p>` : ''}
                ${order.can_respond_to_offer ? `<div class="flex flex-wrap gap-2 mt-3">
                    <button type="button" class="crm-btn crm-btn-primary crm-btn-sm" data-offer-accept>${escapeHtml(i18n.offerAccept)}</button>
                    <button type="button" class="crm-btn crm-btn-secondary crm-btn-sm" data-offer-reject>${escapeHtml(i18n.offerReject)}</button>
                    <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-offer-counter>${escapeHtml(i18n.offerCounter)}</button>
                </div>` : ''}
            </div>`;
        }

        const historyHtml = history.length ? `<div class="crm-supply-detail-section">
            <div class="crm-section-title">${escapeHtml(i18n.historySection)}</div>
            <div class="space-y-1">${history.slice().reverse().map((h) => `
                <p class="text-xs text-[var(--crm-muted)]">
                    ${h.by === 'supplier' ? escapeHtml(i18n.offerBySupplier) : escapeHtml(i18n.offerByDesigner)}
                    · ${h.percent != null ? escapeHtml(String(h.percent)) + '%' : '—'}
                    ${h.message ? ' · ' + escapeHtml(h.message) : ''}
                </p>`).join('')}
            </div>
        </div>` : '';

        body.innerHTML = `
            <div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.sectionMain)}</div>
                <dl class="crm-supply-detail-grid">
                    <div><dt>${escapeHtml(i18n.supplier)}</dt><dd>${escapeHtml(order.supplier_name || '—')}</dd></div>
                    <div><dt>${escapeHtml(i18n.status)}</dt><dd>${escapeHtml(statusLabel(st))}</dd></div>
                    <div><dt>${escapeHtml(i18n.amount)}</dt><dd>${escapeHtml(money(order.summa ?? order.amount))}</dd></div>
                    <div><dt>${escapeHtml(i18n.offerBonusLabel)}</dt><dd>${order.bonus_percent != null ? escapeHtml(String(order.bonus_percent)) + '%' : '—'}</dd></div>
                </dl>
            </div>
            <div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.productsSection)}</div>
                ${productsHtml}
            </div>
            <div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.stepsSection)}</div>
                ${stepsHtml}
            </div>
            ${offerHtml}
            <div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.paymentSection)}</div>
                <dl class="crm-supply-detail-grid">
                    <div><dt>${escapeHtml(i18n.plannedDate)}</dt><dd>${escapeHtml(formatDate(order.date_planned || order.planned_date))}</dd></div>
                    <div><dt>${escapeHtml(i18n.sectionDates)}</dt><dd>${escapeHtml(formatDate(order.date_actual || order.actual_date))}</dd></div>
                    <div><dt>${escapeHtml(i18n.sectionFinance)}</dt><dd>${escapeHtml(money(order.prepayment_amount))} / ${escapeHtml(money(order.payment_amount))}</dd></div>
                </dl>
            </div>
            <div class="crm-supply-detail-section">
                <div class="crm-section-title">${escapeHtml(i18n.filesSection)} / ${escapeHtml(i18n.linksSection)}</div>
                ${filesHtml}
                <div class="mt-2">${linksHtml}</div>
            </div>
            ${historyHtml}`;

        body.querySelector('[data-offer-accept]')?.addEventListener('click', () => runOfferAction(order.id, 'accept'));
        body.querySelector('[data-offer-reject]')?.addEventListener('click', () => runOfferAction(order.id, 'reject'));
        body.querySelector('[data-offer-counter]')?.addEventListener('click', () => runOfferAction(order.id, 'counter'));
    }

    async function runOfferAction(orderId, kind) {
        let url = routes.offerAccept(orderId);
        let body = null;
        if (kind === 'reject') url = routes.offerReject(orderId);
        if (kind === 'counter') {
            url = routes.offerCounter(orderId);
            const val = window.prompt(i18n.offerCounter + ' (%)', '');
            if (val === null) return;
            const percent = parseFloat(String(val).replace(',', '.'));
            if (Number.isNaN(percent)) { toast(i18n.error, 'error'); return; }
            body = JSON.stringify({ bonus_percent: percent });
        }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body,
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || i18n.error);
            const refreshed = await refreshCurrentProject();
            if (refreshed) renderSupplies(refreshed);
            await openDetail(orderId);
        } catch (e) {
            toast(e.message || i18n.error, 'error');
        }
    }

    async function openDetail(orderId) {
        state.detailOrderId = orderId;
        const root = els.detailRoot();
        root?.classList.add('open');
        root?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        document.getElementById('supply-detail-title').textContent = i18n.supplyDetailTitle.replace(':id', String(orderId));
        document.getElementById('supply-detail-meta').textContent = '';
        document.getElementById('supply-detail-body').innerHTML = '<div class="crm-skeleton h-20"></div>';

        try {
            const res = await fetch(routes.show(orderId), { headers: { Accept: 'application/json' } });
            const order = await res.json();
            if (!res.ok || !order?.id) throw new Error(i18n.error);

            const project = getCurrentProject();
            const local = (project?.supplier_orders || []).find((o) => Number(o.id) === Number(orderId));
            if (local?.product_items?.length && !order.product_items?.length) order.product_items = local.product_items;

            document.getElementById('supply-detail-title').textContent = `#${order.id} · ${order.supplier_name || ''}`;
            document.getElementById('supply-detail-meta').textContent = `${statusLabel(effectiveStatus(order))} · ${money(order.summa ?? order.amount)}`;
            renderDetailBody(order);
        } catch (e) {
            document.getElementById('supply-detail-body').innerHTML = `<div class="crm-empty-inline">${escapeHtml(e.message || i18n.error)}</div>`;
        }
    }

    function onProjectOpened(project) {
        populateStatusFilter();
        fillSelectOptions();
        const addBtn = els.addBtn();
        if (addBtn) {
            if (project?.id) addBtn.classList.remove('pointer-events-none', 'opacity-50');
            else addBtn.classList.add('pointer-events-none', 'opacity-50');
        }
        setSupplyView(state.view, { persist: false, rerender: false });
        if (project) renderSupplies(project);
        else {
            els.kanban() && (els.kanban().innerHTML = '');
            els.list() && (els.list().innerHTML = '');
            if (els.count()) els.count().textContent = '0';
        }
    }

    function portalModals() {
        ['supply-modal-root', 'supply-catalog-root', 'supply-detail-root', 'supply-unsaved-modal'].forEach((id) => {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) document.body.appendChild(el);
        });
    }

    function bindEvents() {
        els.addBtn()?.addEventListener('click', (e) => {
            e.preventDefault();
            openSupplyCreate();
        });

        document.querySelectorAll('.crm-supply-view-btn').forEach((btn) => {
            btn.addEventListener('click', () => setSupplyView(btn.dataset.sview));
        });

        const searchEl = els.search();
        if (searchEl) {
            searchEl.value = state.search || '';
            searchEl.addEventListener('input', debounce(() => {
                state.search = searchEl.value || '';
                localStorage.setItem('project_supplies_search', state.search);
                renderSupplies(getCurrentProject());
            }, 350));
        }

        const statusEl = els.statusFilter();
        if (statusEl) {
            statusEl.value = state.statusFilter || '';
            statusEl.addEventListener('change', () => {
                state.statusFilter = statusEl.value || '';
                localStorage.setItem('project_supplies_status_filter', state.statusFilter);
                renderSupplies(getCurrentProject());
            });
        }

        document.getElementById('supply-close')?.addEventListener('click', () => closeSupplyModalRoot(false));
        document.getElementById('supply-cancel')?.addEventListener('click', () => closeSupplyModalRoot(false));
        els.supplyRoot()?.querySelector('[data-supply-close-backdrop]')?.addEventListener('click', () => closeSupplyModalRoot(false));

        document.getElementById('supply-unsaved-continue')?.addEventListener('click', () => {
            els.unsavedRoot()?.classList.remove('open');
        });
        document.getElementById('supply-unsaved-leave')?.addEventListener('click', () => closeSupplyModalRoot(true));

        els.form()?.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitter = e.submitter;
            const action = submitter?.value === 'send' ? 'send' : 'save';
            submitSupplyForm(action);
        });

        document.getElementById('supply-add-link')?.addEventListener('click', () => {
            const box = document.getElementById('supply-links');
            const row = document.createElement('div');
            row.className = 'flex gap-2';
            row.innerHTML = `<input type="url" data-link-url class="crm-input flex-1" placeholder="${escapeHtml(i18n.pasteLink)}">
                <button type="button" class="crm-btn crm-btn-ghost crm-btn-sm" data-rm-link>×</button>`;
            box?.appendChild(row);
            row.querySelector('[data-rm-link]')?.addEventListener('click', () => { row.remove(); markFormDirty(); });
            row.querySelector('input')?.addEventListener('input', markFormDirty);
            markFormDirty();
        });

        document.getElementById('supply-open-catalog')?.addEventListener('click', openCatalogModal);
        document.getElementById('catalog-close')?.addEventListener('click', closeCatalogModal);
        els.catalogRoot()?.querySelector('[data-catalog-close]')?.addEventListener('click', closeCatalogModal);
        document.getElementById('catalog-apply')?.addEventListener('click', applyCatalogToForm);
        document.getElementById('catalog-search')?.addEventListener('input', debounce(() => loadCatalogProducts(1)));
        document.getElementById('catalog-category')?.addEventListener('change', () => loadCatalogProducts(1));

        document.getElementById('supply-supplier-id')?.addEventListener('change', (e) => {
            const newId = e.target.value;
            if (state.formProducts.length && state.lastSupplierId && newId !== state.lastSupplierId) {
                if (!window.confirm(i18n.changeSupplierWarn)) {
                    e.target.value = state.lastSupplierId;
                    return;
                }
                state.formProducts = [];
                recalcProducts();
            }
            state.lastSupplierId = newId;
            markFormDirty();
        });

        ['supply-summa', 'supply-bonus', 'supply-category', 'supply-mark', 'supply-room',
            'supply-date-planned', 'supply-date-actual', 'supply-prepay-date', 'supply-prepay-amount',
            'supply-pay-date', 'supply-pay-amount', 'supply-comment', 'supply-files'].forEach((id) => {
            const el = document.getElementById(id);
            el?.addEventListener('input', () => { markFormDirty(); if (id === 'supply-summa' || id === 'supply-bonus') updateBonusHint(); });
            el?.addEventListener('change', markFormDirty);
        });

        document.getElementById('supply-detail-close')?.addEventListener('click', closeDetailModal);
        document.getElementById('supply-detail-close-2')?.addEventListener('click', closeDetailModal);
        els.detailRoot()?.querySelector('[data-detail-close]')?.addEventListener('click', closeDetailModal);
        document.getElementById('supply-detail-edit')?.addEventListener('click', () => {
            if (state.detailOrderId) openSupplyEdit(state.detailOrderId);
        });
    }

    portalModals();
    fillSelectOptions();
    populateStatusFilter();
    bindEvents();
    setSupplyView(state.view, { persist: false, rerender: false });

    window.CrmSupplies = {
        renderSupplies,
        openCreate: openSupplyCreate,
        openDetail,
        onProjectOpened,
        setView: setSupplyView,
    };
})();
</script>
