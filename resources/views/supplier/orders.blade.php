@extends('layouts.supplier')

@section('title', __('supplier-portal.page_orders_title'))

@section('header_title', __('supplier-portal.page_orders_title'))

@push('styles')
<style>
    .supplier-so-tab-btn {
        background: #ffffff;
        border: 1px solid #7c8799;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        cursor: pointer;
        color: #64748b;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 0.875rem;
    }
    .supplier-so-tab-btn:hover { border-color: #f59e0b; color: #f59e0b; }
    .supplier-so-tab-btn.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }
    .supplier-funnel-column {
        min-height: 360px;
        min-width: 160px;
        background: #f8fafc;
        border-radius: 8px;
        padding: 1rem;
        border: 2px dashed #7c8799;
    }
    .supplier-funnel-column.drag-over { border-color: #f59e0b; background: #fef3c7; }
    .supplier-funnel-card {
        background: white;
        border: 1px solid #7c8799;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        cursor: move;
        transition: all 0.2s;
        min-width: 0;
    }
    .supplier-funnel-card.dragging { opacity: 0.5; }
    .supplier-funnel-card h4, .supplier-funnel-card p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .sortable-hdr {
        cursor: pointer;
        user-select: none;
        padding-right: 1rem;
        position: relative;
    }
    .sortable-hdr:hover { color: #f59e0b; }
    .sortable-hdr::after { content: '↕'; position: absolute; right: 0; opacity: 0.5; }
    .sortable-hdr.asc::after { content: '↑'; opacity: 1; }
    .sortable-hdr.desc::after { content: '↓'; opacity: 1; }
    .supplier-so-pagination { display: flex; gap: 0.5rem; align-items: center; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap; }
    .supplier-so-pagination button {
        padding: 0.5rem 1rem;
        border: 1px solid #7c8799;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        color: #64748b;
    }
    .supplier-so-pagination button:hover:not(:disabled) { border-color: #f59e0b; color: #f59e0b; }
    .supplier-so-pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
    .supplier-so-pagination button.active { background: #f1f5f9; border-color: #f59e0b; color: #f59e0b; }
    .dark .supplier-so-tab-btn { background: #161615; border-color: #3E3E3A; color: #A1A09A; }
    .dark .supplier-so-tab-btn.active { background: #0a0a0a; border-color: #f59e0b; color: #f59e0b; }
    .dark .supplier-funnel-column { background: #0a0a0a; border-color: #3E3E3A; }
    .dark .supplier-funnel-column.drag-over { background: #1D0002; }
    .dark .supplier-funnel-card { background: #161615; border-color: #3E3E3A; }
    .dark .supplier-so-pagination button { background: #161615; border-color: #3E3E3A; color: #A1A09A; }
    .supplier-chat-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 16px;
        height: 16px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        border: 1px solid #fff;
    }
    .dark .supplier-chat-badge { border-color: #161615; }
    .supplier-chat-mine { background: #fef3c7; color: #92400e; }
    .supplier-chat-other { background: #f1f5f9; color: #334155; }
    .dark .supplier-chat-mine { background: #1D0002; color: #f59e0b; }
    .dark .supplier-chat-other { background: #0a0a0a; color: #EDEDEC; }
</style>
@endpush

@section('content')
    @unless($supplier)
        <div class="rounded-xl border border-dashed border-[#f59e0b]/50 bg-amber-50/50 dark:bg-amber-950/20 px-6 py-8 text-center">
            <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ __('supplier-portal.orders_need_company') }}</p>
            <a href="{{ route('supplier.company') }}" class="inline-flex mt-4 px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706]">
                {{ __('supplier-portal.nav_company') }}
            </a>
        </div>
    @else
        <div class="mb-6 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.orders_intro') }}</p>
        </div>

        <div class="mb-6 flex flex-wrap gap-2">
            <button type="button" data-so-tab="table" class="supplier-so-tab-btn active">{{ __('supplier-orders.table') }}</button>
            <button type="button" data-so-tab="list" class="supplier-so-tab-btn">{{ __('supplier-orders.list') }}</button>
            <button type="button" data-so-tab="funnel" class="supplier-so-tab-btn">{{ __('supplier-orders.funnel') }}</button>
        </div>

        <div class="mb-6 flex flex-col lg:flex-row gap-4">
            <div class="flex-1 min-w-0">
                <input type="search" id="supplier-so-search" placeholder="{{ __('supplier-orders.search') }}"
                    class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            </div>
            <div class="w-full lg:w-52">
                <select id="supplier-so-project" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    <option value="">{{ __('supplier-orders.all_projects') }}</option>
                    @foreach ($filterProjects as $p)
                        <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-52">
                <select id="supplier-so-status" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    <option value="">{{ __('supplier-orders.all_statuses') }}</option>
                    <option value="order_created">{{ __('supplier-orders.status_order_created') }}</option>
                    <option value="order_sent">{{ __('supplier-orders.status_order_sent') }}</option>
                    <option value="order_confirmed">{{ __('supplier-orders.status_order_confirmed') }}</option>
                    <option value="advance_payment">{{ __('supplier-orders.status_advance_payment') }}</option>
                    <option value="full_payment">{{ __('supplier-orders.status_full_payment') }}</option>
                    <option value="delivery_completed">{{ __('supplier-orders.status_delivery_completed') }}</option>
                </select>
            </div>
        </div>

        <div id="supplier-so-table-view" class="supplier-so-tab-panel">
            <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px]">
                        <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                            <tr>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="number">{{ __('supplier-orders.number') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="created_date">{{ __('supplier-orders.created_date') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="designer_name">{{ __('supplier-orders.designer') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="project_name">{{ __('supplier-orders.project') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="status">{{ __('supplier-orders.status') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="is_sent_to_supplier">{{ __('supplier-orders.send_status') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="amount">{{ __('supplier-orders.amount') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="planned_date">{{ __('supplier-orders.planned_date') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-hdr" data-sort="actual_date">{{ __('supplier-orders.actual_date') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.product_service') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.links') }}</th>
                                <th class="px-3 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.view') }}</th>
                            </tr>
                        </thead>
                        <tbody id="supplier-so-tbody" class="divide-y divide-[#7c8799] dark:divide-[#3E3E3A]"></tbody>
                    </table>
                </div>
                <div class="supplier-so-pagination" id="supplier-so-pagination"></div>
            </div>
        </div>

        <div id="supplier-so-list-view" class="supplier-so-tab-panel hidden">
            <div class="space-y-4" id="supplier-so-list-body"></div>
        </div>

        <div id="supplier-so-funnel-view" class="supplier-so-tab-panel hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 overflow-x-auto pb-2">
                @foreach ([
                    'order_created',
                    'order_sent',
                    'order_confirmed',
                    'advance_payment',
                    'full_payment',
                    'delivery_completed',
                ] as $st)
                    <div class="supplier-funnel-column" data-status="{{ $st }}" ondrop="supplierSoDrop(event)" ondragover="supplierSoAllowDrop(event)">
                        <h3 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC] mb-3">@switch($st)
                            @case('order_created') {{ __('supplier-orders.status_order_created') }} @break
                            @case('order_sent') {{ __('supplier-orders.status_order_sent') }} @break
                            @case('order_confirmed') {{ __('supplier-orders.status_order_confirmed') }} @break
                            @case('advance_payment') {{ __('supplier-orders.status_advance_payment') }} @break
                            @case('full_payment') {{ __('supplier-orders.status_full_payment') }} @break
                            @case('delivery_completed') {{ __('supplier-orders.status_delivery_completed') }} @break
                        @endswitch</h3>
                        <div id="supplier-funnel-{{ str_replace('_', '-', $st) }}" class="funnel-cards min-h-[120px]"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div id="supplier-so-view-modal" class="fixed inset-0 bg-black/50 z-50 hidden" onmousedown="if(event.target === this) supplierSoCloseView()">
            <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl flex flex-col supplier-so-modal-panel translate-x-full transition-transform duration-300">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A]">
                    <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.view') }}</h2>
                    <button type="button" onclick="supplierSoCloseView()" class="p-2 rounded-lg text-[#64748b] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="supplier-so-view-content" class="flex-1 overflow-y-auto p-5 space-y-4"></div>
            </div>
        </div>
        <div id="supplier-so-chat-modal" class="fixed inset-0 bg-black/50 z-50 hidden" onmousedown="if(event.target === this) supplierSoCloseChat()">
            <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl flex flex-col supplier-so-chat-panel translate-x-full transition-transform duration-300">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.chat_title') }}</h2>
                        <p id="supplier-so-chat-subtitle" class="text-sm text-[#64748b] dark:text-[#A1A09A]"></p>
                    </div>
                    <button type="button" onclick="supplierSoCloseChat()" class="p-2 rounded-lg text-[#64748b] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="px-5 py-3 border-b border-[#7c8799] dark:border-[#3E3E3A] flex items-center justify-between gap-2">
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_hint_manual_refresh') }}</p>
                    <button type="button" onclick="supplierSoRefreshChatMessages()" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]">{{ __('supplier-orders.chat_refresh') }}</button>
                </div>
                <div id="supplier-so-chat-messages" class="flex-1 overflow-y-auto p-5 space-y-3"></div>
                <form id="supplier-so-chat-form" class="border-t border-[#7c8799] dark:border-[#3E3E3A] p-4">
                    <input type="hidden" id="supplier-so-chat-order-id" value="">
                    <div class="flex items-end gap-2">
                        <textarea id="supplier-so-chat-input" rows="2" maxlength="5000" class="flex-1 px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]" placeholder="{{ __('supplier-orders.chat_placeholder') }}"></textarea>
                        <button type="submit" class="px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10">{{ __('supplier-orders.chat_send') }}</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        @php
            $supplierSoDateLocale = match (app()->getLocale()) {
                'kk' => 'kk-KZ',
                'en' => 'en-US',
                default => 'ru-RU',
            };
            $supplierSoLabels = [
                'status_order_created' => __('supplier-orders.status_order_created'),
                'status_order_sent' => __('supplier-orders.status_order_sent'),
                'status_order_confirmed' => __('supplier-orders.status_order_confirmed'),
                'status_advance_payment' => __('supplier-orders.status_advance_payment'),
                'status_full_payment' => __('supplier-orders.status_full_payment'),
                'status_delivery_completed' => __('supplier-orders.status_delivery_completed'),
                'project_steps_section' => __('supplier-orders.project_steps_section'),
                'step_link' => __('supplier-orders.step_link'),
            ];
        @endphp
        <script>
window.supplierSoOrders = @json($orders);
window.supplierSoDateLocale = @json($supplierSoDateLocale);
window.supplierSoStatusUrl = @json(url('/supplier/orders'));
window.supplierSoChatBaseUrl = @json(url('/supplier-orders'));
window.supplierSoChatUnreadMapUrl = @json(route('supplier-orders.chat.unread_map'));
window.supplierSoLabels = @json($supplierSoLabels);

(function() {
    const allOrders = window.supplierSoOrders || [];
    let currentPage = 1;
    const itemsPerPage = 10;
    let sortColumn = 'number';
    let sortDirection = 'desc';
    let currentTab = 'table';
    let draggedEl = null;

    const dateLocale = window.supplierSoDateLocale || 'ru-RU';
    const statusUrlBase = window.supplierSoStatusUrl || '';
    const L = window.supplierSoLabels || {};

    function statusLabel(s) {
        const map = {
            order_created: L.status_order_created,
            order_sent: L.status_order_sent,
            order_confirmed: L.status_order_confirmed,
            advance_payment: L.status_advance_payment,
            full_payment: L.status_full_payment,
            delivery_completed: L.status_delivery_completed,
        };
        return map[s] || s;
    }

    function statusBadgeClass(s) {
        const map = {
            order_created: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
            order_sent: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
            order_confirmed: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200',
            advance_payment: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200',
            full_payment: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200',
            delivery_completed: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-200',
        };
        return map[s] || 'bg-slate-100 text-slate-800';
    }

    function getFilteredOrders() {
        const search = (document.getElementById('supplier-so-search')?.value || '').toLowerCase();
        const project = document.getElementById('supplier-so-project')?.value || '';
        const status = document.getElementById('supplier-so-status')?.value || '';
        return allOrders.filter(o => {
            const hay = [o.number, o.project_name, o.designer_name, o.comment, o.status].join(' ').toLowerCase();
            const okSearch = !search || hay.includes(search);
            const okProj = !project || String(o.project_id) === project;
            const okSt = !status || o.status === status;
            return okSearch && okProj && okSt;
        });
    }

    function compareVal(a, b, col) {
        let av = a[col];
        let bv = b[col];
        if (col === 'amount' || col === 'number') {
            av = Number(av) || 0;
            bv = Number(bv) || 0;
            return av - bv;
        }
        if (col === 'created_date' || col === 'planned_date' || col === 'actual_date') {
            av = av || '';
            bv = bv || '';
            return String(av).localeCompare(String(bv));
        }
        av = String(av || '').toLowerCase();
        bv = String(bv || '').toLowerCase();
        return av.localeCompare(bv);
    }

    function getSortedFiltered() {
        const filtered = getFilteredOrders();
        const col = sortColumn;
        if (!col) return filtered;
        const copy = [...filtered];
        copy.sort((a, b) => {
            const c = compareVal(a, b, col);
            return sortDirection === 'asc' ? c : -c;
        });
        return copy;
    }

    function fmtDate(iso) {
        if (!iso) return '';
        try { return new Date(iso).toLocaleDateString(dateLocale); } catch (_) { return iso; }
    }

    function getChatBtnHtml(order, compact = false) {
        const unread = Math.max(0, parseInt(order.unread_chat_count || 0, 10));
        const badge = unread > 0 ? `<span class="supplier-chat-badge">${unread > 99 ? '99+' : unread}</span>` : '';
        if (compact) {
            return `<button type="button" onclick="supplierSoOpenChat(${order.id})" class="relative p-1.5 rounded text-[#64748b] hover:text-[#f59e0b] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]" title="{{ __('supplier-orders.chat_open') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 7l-3-3a2 2 0 01-.586-1.414V6a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2H8z"/></svg>
                ${badge}
            </button>`;
        }
        return `<button type="button" onclick="supplierSoOpenChat(${order.id})" class="relative supplier-so-tab-btn">{{ __('supplier-orders.chat_open') }}${unread > 0 ? ` <span class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full text-[10px] bg-red-500 text-white">${unread > 99 ? '99+' : unread}</span>` : ''}</button>`;
    }

    function setOrderUnread(orderId, count) {
        const idx = allOrders.findIndex(o => String(o.id) === String(orderId));
        if (idx >= 0) allOrders[idx].unread_chat_count = Math.max(0, parseInt(count || 0, 10));
    }

    function renderTable() {
        const tbody = document.getElementById('supplier-so-tbody');
        const pag = document.getElementById('supplier-so-pagination');
        if (!tbody) return;
        const sorted = getSortedFiltered();
        const totalPages = Math.max(1, Math.ceil(sorted.length / itemsPerPage));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * itemsPerPage;
        const pageRows = sorted.slice(start, start + itemsPerPage);

        if (sorted.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.no_orders') }}</td></tr>';
            if (pag) pag.innerHTML = '';
            return;
        }

        tbody.innerHTML = pageRows.map(o => {
            const st = o.status;
            const badge = statusBadgeClass(st);
            const stText = statusLabel(st);
            const sendStatusClass = o.is_sent_to_supplier
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                : 'bg-slate-100 text-slate-700 dark:bg-slate-900/20 dark:text-slate-300';
            const sendStatusText = o.is_sent_to_supplier
                ? '{{ __('supplier-orders.sent_to_supplier') }}'
                : '{{ __('supplier-orders.not_sent_to_supplier') }}';
            return `<tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-order-id="${o.id}">
                <td class="px-3 py-2.5 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${o.id}</td>
                <td class="px-3 py-2.5 text-sm">${fmtDate(o.created_date)}</td>
                <td class="px-3 py-2.5 text-sm">${escapeHtml(o.designer_name || '—')}</td>
                <td class="px-3 py-2.5 text-sm">${escapeHtml(o.project_name || '—')}</td>
                <td class="px-3 py-2.5 text-sm"><span class="px-2 py-0.5 rounded text-xs font-medium ${badge} order-status-badge">${escapeHtml(stText)}</span></td>
                <td class="px-3 py-2.5 text-sm"><span class="px-2 py-0.5 rounded text-xs font-medium ${sendStatusClass}">${sendStatusText}</span></td>
                <td class="px-3 py-2.5 text-sm">${Number(o.summa).toLocaleString(dateLocale)} ₸</td>
                <td class="px-3 py-2.5 text-sm">${fmtDate(o.date_planned)}</td>
                <td class="px-3 py-2.5 text-sm">${o.date_actual ? fmtDate(o.date_actual) : '—'}</td>
                <td class="px-3 py-2.5 text-sm max-w-[200px] truncate" title="${escapeHtml(o.comment || '')}">${escapeHtml(o.comment || '—')}</td>
                <td class="px-3 py-2.5 text-sm">${(o.links && o.links.length) ? o.links.map(l => `<a href="${escapeAttr(l)}" target="_blank" rel="noopener" class="text-[#f59e0b] hover:underline text-xs mr-1">{{ __('supplier-orders.links') }}</a>`).join('') : '—'}</td>
                <td class="px-3 py-2.5 text-sm">
                    <button type="button" onclick="supplierSoView(${o.id})" class="p-1.5 rounded text-[#64748b] hover:text-[#f59e0b] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]" title="{{ __('supplier-orders.view') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    ${getChatBtnHtml(o, true)}
                </td>
            </tr>`;
        }).join('');

        renderPagination(totalPages);
        document.querySelectorAll('.sortable-hdr').forEach(h => {
            h.classList.remove('asc', 'desc');
            if (h.dataset.sort === sortColumn) h.classList.add(sortDirection);
        });
    }

    function renderPagination(totalPages) {
        const pag = document.getElementById('supplier-so-pagination');
        if (!pag) return;
        pag.innerHTML = '';
        if (totalPages <= 1) return;
        const prev = document.createElement('button');
        prev.type = 'button';
        prev.textContent = '←';
        prev.disabled = currentPage <= 1;
        prev.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderTable(); } });
        pag.appendChild(prev);
        for (let i = 1; i <= totalPages; i++) {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = String(i);
            if (i === currentPage) b.classList.add('active');
            b.addEventListener('click', () => { currentPage = i; renderTable(); });
            pag.appendChild(b);
        }
        const next = document.createElement('button');
        next.type = 'button';
        next.textContent = '→';
        next.disabled = currentPage >= totalPages;
        next.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; renderTable(); } });
        pag.appendChild(next);
    }

    function renderList() {
        const el = document.getElementById('supplier-so-list-body');
        if (!el) return;
        const rows = getFilteredOrders();
        if (!rows.length) {
            el.innerHTML = '<p class="text-center text-[#64748b] py-8">{{ __('supplier-orders.no_orders') }}</p>';
            return;
        }
        el.innerHTML = rows.map(o => {
            const st = o.status;
            const sendStatusClass = o.is_sent_to_supplier
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                : 'bg-slate-100 text-slate-700 dark:bg-slate-900/20 dark:text-slate-300';
            const sendStatusText = o.is_sent_to_supplier
                ? '{{ __('supplier-orders.sent_to_supplier') }}'
                : '{{ __('supplier-orders.not_sent_to_supplier') }}';
            return `<div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-5" data-order-id="${o.id}">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">#${o.id}</h3>
                        <p class="text-sm text-[#64748b] mt-1">${escapeHtml(o.designer_name || '—')} · ${escapeHtml(o.project_name || '—')}</p>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="px-2 py-1 rounded text-xs font-medium ${statusBadgeClass(st)}">${escapeHtml(statusLabel(st))}</span>
                        <span class="px-2 py-1 rounded text-xs font-medium ${sendStatusClass}">${sendStatusText}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <div><span class="text-[#64748b]">{{ __('supplier-orders.created_date') }}:</span> <span class="font-medium">${fmtDate(o.created_date)}</span></div>
                    <div><span class="text-[#64748b]">{{ __('supplier-orders.amount') }}:</span> <span class="font-medium">${Number(o.summa).toLocaleString(dateLocale)} ₸</span></div>
                    <div><span class="text-[#64748b]">{{ __('supplier-orders.planned_date') }}:</span> <span class="font-medium">${fmtDate(o.date_planned)}</span></div>
                    <div><span class="text-[#64748b]">{{ __('supplier-orders.actual_date') }}:</span> <span class="font-medium">${o.date_actual ? fmtDate(o.date_actual) : '—'}</span></div>
                </div>
                ${o.comment ? `<p class="text-sm mt-3 text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(o.comment)}</p>` : ''}
                <div class="mt-4 flex items-center gap-2">
                    <button type="button" onclick="supplierSoView(${o.id})" class="supplier-so-tab-btn">{{ __('supplier-orders.view') }}</button>
                    ${getChatBtnHtml(o)}
                </div>
            </div>`;
        }).join('');
    }

    function renderFunnel() {
        const statuses = ['order_created','order_sent','order_confirmed','advance_payment','full_payment','delivery_completed'];
        const filtered = getFilteredOrders();
        statuses.forEach(st => {
            const box = document.getElementById('supplier-funnel-' + st.replace(/_/g, '-'));
            if (!box) return;
            const rows = filtered.filter(o => o.status === st);
            box.innerHTML = rows.map(o => `
                <div class="supplier-funnel-card" draggable="true"
                    ondragstart="supplierSoDrag(event)"
                    data-order-id="${o.id}">
                    <h4 class="font-medium text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-1">#${o.id}</h4>
                    <p class="text-xs text-[#64748b]">${escapeHtml(o.designer_name || '—')}</p>
                    <p class="text-xs text-[#64748b] mt-0.5">${escapeHtml(o.project_name || '—')}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <button type="button" onclick="event.stopPropagation(); supplierSoView(${o.id})" class="text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b]">{{ __('supplier-orders.view') }}</button>
                        <button type="button" onclick="event.stopPropagation(); supplierSoOpenChat(${o.id})" class="relative text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]">{{ __('supplier-orders.chat_open') }}${o.unread_chat_count > 0 ? ` <span class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full text-[10px] bg-red-500 text-white">${o.unread_chat_count > 99 ? '99+' : o.unread_chat_count}</span>` : ''}</button>
                    </div>
                </div>
            `).join('');
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    function escapeAttr(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
    }

    function renderIncludedStepsHtml(o) {
        const steps = o.included_steps || [];
        if (!steps.length) return '';
        const title = L.project_steps_section || '';
        let html = '<div class="border-t border-[#7c8799] dark:border-[#3E3E3A] pt-4 mt-2"><h3 class="text-sm font-semibold text-[#0f172a] dark:text-[#EDEDEC] mb-3">' + escapeHtml(title) + '</h3>';
        let lastStage = null;
        steps.forEach(function (s) {
            const stl = s.stage_type_label || s.stage_type || '';
            if (stl !== lastStage) {
                lastStage = stl;
                html += '<div class="text-xs font-medium text-[#64748b] dark:text-[#A1A09A] mt-2 mb-1">' + escapeHtml(stl) + '</div>';
            }
            html += '<div class="mb-3 pl-2 border-l-2 border-[#f59e0b]/50"><p class="font-medium text-sm text-[#0f172a] dark:text-[#EDEDEC]">' + escapeHtml(s.title || '') + '</p>';
            if (s.result_comment) {
                html += '<p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1 whitespace-pre-wrap">' + escapeHtml(s.result_comment) + '</p>';
            }
            if (s.link) {
                html += '<p class="text-sm mt-1"><a href="' + escapeAttr(s.link) + '" target="_blank" rel="noopener noreferrer" class="text-[#f59e0b] hover:underline">' + escapeHtml(L.step_link || '') + '</a></p>';
            }
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    document.querySelectorAll('[data-so-tab]').forEach(btn => {
        btn.addEventListener('click', function() {
            currentTab = this.dataset.soTab;
            document.querySelectorAll('[data-so-tab]').forEach(b => b.classList.toggle('active', b === this));
            document.getElementById('supplier-so-table-view').classList.toggle('hidden', currentTab !== 'table');
            document.getElementById('supplier-so-list-view').classList.toggle('hidden', currentTab !== 'list');
            document.getElementById('supplier-so-funnel-view').classList.toggle('hidden', currentTab !== 'funnel');
            if (currentTab === 'table') renderTable();
            if (currentTab === 'list') renderList();
            if (currentTab === 'funnel') renderFunnel();
        });
    });

    function refreshViews() {
        currentPage = 1;
        if (currentTab === 'table') renderTable();
        if (currentTab === 'list') renderList();
        if (currentTab === 'funnel') renderFunnel();
    }
    document.getElementById('supplier-so-search')?.addEventListener('input', refreshViews);
    document.getElementById('supplier-so-project')?.addEventListener('change', refreshViews);
    document.getElementById('supplier-so-status')?.addEventListener('change', refreshViews);

    document.querySelectorAll('.sortable-hdr').forEach(h => {
        h.addEventListener('click', () => {
            const col = h.dataset.sort;
            if (sortColumn === col) sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            else { sortColumn = col; sortDirection = col === 'number' ? 'desc' : 'asc'; }
            currentPage = 1;
            renderTable();
        });
    });

    renderTable();

    async function refreshUnreadMap() {
        try {
            const r = await fetch(window.supplierSoChatUnreadMapUrl, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (!r.ok || !data.success) return;
            const map = data.unread || {};
            allOrders.forEach((o) => {
                o.unread_chat_count = parseInt(map[o.id] || 0, 10);
            });
            if (currentTab === 'table') renderTable();
            if (currentTab === 'list') renderList();
            if (currentTab === 'funnel') renderFunnel();
        } catch (_) {}
    }

    function renderChatMessages(items) {
        const wrap = document.getElementById('supplier-so-chat-messages');
        if (!wrap) return;
        if (!Array.isArray(items) || items.length === 0) {
            wrap.innerHTML = `<p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_empty') }}</p>`;
            return;
        }
        wrap.innerHTML = items.map((m) => {
            const mine = !!m.is_mine;
            const alignClass = mine ? 'justify-end' : 'justify-start';
            const bubbleClass = mine ? 'supplier-chat-mine' : 'supplier-chat-other';
            const sender = mine ? '{{ __('supplier-orders.chat_you') }}' : (m.sender_name || '-');
            const ts = m.created_at ? new Date(m.created_at).toLocaleString(dateLocale) : '';
            const safe = escapeHtml(m.message || '');
            return `<div class="flex ${alignClass}">
                <div class="max-w-[80%] rounded-xl px-3 py-2 ${bubbleClass}">
                    <div class="text-xs opacity-80 mb-1">${sender}</div>
                    <div class="text-sm whitespace-pre-wrap break-words">${safe}</div>
                    <div class="text-[10px] opacity-70 mt-1">${ts}</div>
                </div>
            </div>`;
        }).join('');
        wrap.scrollTop = wrap.scrollHeight;
    }

    window.supplierSoOpenChat = async function(orderId) {
        const ord = allOrders.find(o => String(o.id) === String(orderId));
        document.getElementById('supplier-so-chat-order-id').value = String(orderId);
        document.getElementById('supplier-so-chat-subtitle').textContent = ord
            ? `#${ord.number || ord.id} • ${ord.designer_name || '—'}`
            : `#${orderId}`;
        const modal = document.getElementById('supplier-so-chat-modal');
        const panel = modal.querySelector('.supplier-so-chat-panel');
        modal.classList.remove('hidden');
        requestAnimationFrame(() => { panel.classList.remove('translate-x-full'); });
        await window.supplierSoRefreshChatMessages();
    };

    window.supplierSoCloseChat = function() {
        const modal = document.getElementById('supplier-so-chat-modal');
        const panel = modal.querySelector('.supplier-so-chat-panel');
        panel.classList.add('translate-x-full');
        setTimeout(() => modal.classList.add('hidden'), 280);
    };

    window.supplierSoRefreshChatMessages = async function() {
        const orderId = parseInt(document.getElementById('supplier-so-chat-order-id')?.value || '0', 10);
        if (!orderId) return;
        const wrap = document.getElementById('supplier-so-chat-messages');
        if (wrap) wrap.innerHTML = `<p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_loading') }}</p>`;
        try {
            const r = await fetch(`${window.supplierSoChatBaseUrl}/${orderId}/chat/messages`, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (!r.ok || !data.success) throw new Error('chat_load_failed');
            renderChatMessages(data.messages || []);
            await fetch(`${window.supplierSoChatBaseUrl}/${orderId}/chat/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
            });
            setOrderUnread(orderId, 0);
            await refreshUnreadMap();
        } catch (_) {
            if (wrap) wrap.innerHTML = `<p class="text-sm text-red-500">{{ __('supplier-orders.error') }}</p>`;
        }
    };

    document.getElementById('supplier-so-chat-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const orderId = parseInt(document.getElementById('supplier-so-chat-order-id')?.value || '0', 10);
        const input = document.getElementById('supplier-so-chat-input');
        const message = (input?.value || '').trim();
        if (!orderId || !message) return;
        try {
            const r = await fetch(`${window.supplierSoChatBaseUrl}/${orderId}/chat/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message }),
            });
            const data = await r.json();
            if (!r.ok || !data.success) throw new Error('chat_send_failed');
            input.value = '';
            await window.supplierSoRefreshChatMessages();
        } catch (_) {
            projectAlert('error', '{{ __("supplier-orders.error") }}', '', 3000);
        }
    });
    refreshUnreadMap();

    window.supplierSoView = function(id) {
        const o = allOrders.find(x => x.id === id);
        if (!o) return;
        const html = `
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.number') }}</span><p class="font-medium">#${o.id}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.designer') }}</span><p>${escapeHtml(o.designer_name || '—')}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.project') }}</span><p>${escapeHtml(o.project_name || '—')}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.status') }}</span><p>${escapeHtml(statusLabel(o.status))}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.send_status') }}</span><p>${o.is_sent_to_supplier ? '{{ __('supplier-orders.sent_to_supplier') }}' : '{{ __('supplier-orders.not_sent_to_supplier') }}'}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.amount') }}</span><p>${Number(o.summa).toLocaleString(dateLocale)} ₸</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.planned_date') }}</span><p>${fmtDate(o.date_planned)}</p></div>
            <div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.actual_date') }}</span><p>${o.date_actual ? fmtDate(o.date_actual) : '—'}</p></div>
            ${o.comment ? `<div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.product_service') }}</span><p>${escapeHtml(o.comment)}</p></div>` : ''}
            ${(o.file_urls && o.file_urls.length) ? `<div><span class="text-sm text-[#64748b]">{{ __('supplier-orders.files') }}</span><ul class="list-disc pl-5 mt-1">${o.file_urls.map(u => `<li><a href="${escapeAttr(u)}" target="_blank" class="text-[#f59e0b] hover:underline text-sm">${escapeHtml(u.split('/').pop() || u)}</a></li>`).join('')}</ul></div>` : ''}
        ` + renderIncludedStepsHtml(o);
        document.getElementById('supplier-so-view-content').innerHTML = html;
        const modal = document.getElementById('supplier-so-view-modal');
        const panel = modal.querySelector('.supplier-so-modal-panel');
        modal.classList.remove('hidden');
        requestAnimationFrame(() => { panel.classList.remove('translate-x-full'); });
    };

    window.supplierSoCloseView = function() {
        const modal = document.getElementById('supplier-so-view-modal');
        const panel = modal.querySelector('.supplier-so-modal-panel');
        panel.classList.add('translate-x-full');
        setTimeout(() => modal.classList.add('hidden'), 280);
    };

    window.supplierSoDrag = function(ev) {
        draggedEl = ev.target.closest('.supplier-funnel-card');
        if (draggedEl) draggedEl.classList.add('dragging');
    };

    window.supplierSoAllowDrop = function(ev) {
        ev.preventDefault();
        ev.currentTarget.classList.add('drag-over');
    };

    window.supplierSoDrop = function(ev) {
        ev.preventDefault();
        ev.currentTarget.classList.remove('drag-over');
        const col = ev.currentTarget;
        const newStatus = col.dataset.status;
        const targetBox = col.querySelector('.funnel-cards');
        if (!draggedEl || !targetBox || !newStatus) { draggedEl = null; return; }

        const orderId = draggedEl.dataset.orderId;
        targetBox.appendChild(draggedEl);
        draggedEl.classList.remove('dragging');

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(statusUrlBase + '/' + orderId + '/status', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: newStatus }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (ok && d.success) {
                const ord = allOrders.find(x => String(x.id) === String(orderId));
                if (ord) ord.status = newStatus;
                renderFunnel();
                if (currentTab === 'table') renderTable();
                if (currentTab === 'list') renderList();
            } else {
                projectAlert('error', d.message || '{{ __("supplier-orders.error") }}', '', 3200);
                renderFunnel();
            }
            draggedEl = null;
        })
        .catch(() => {
            draggedEl = null;
            projectAlert('error', '{{ __("supplier-orders.error") }}', '', 3200);
            renderFunnel();
        });
    };

    document.querySelectorAll('.supplier-funnel-column').forEach(column => {
        column.addEventListener('dragleave', function(e) {
            if (!column.contains(e.relatedTarget)) column.classList.remove('drag-over');
        });
    });
})();
</script>
        @endpush
    @endunless
@endsection
