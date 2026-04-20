@extends('layouts.dashboard')

@section('title', __('supplier-orders.supplier_orders'))

@push('styles')
<style>
    .tab-btn {
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

    .tab-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .tab-btn.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .funnel-column {
        min-height: 400px;
        min-width: 180px;
        background: #f8fafc;
        border-radius: 8px;
        padding: 1rem;
        border: 2px dashed #7c8799;
    }

    .funnel-column.drag-over {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .funnel-card {
        background: white;
        border: 1px solid #7c8799;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        cursor: move;
        transition: all 0.3s;
        min-width: 0;
        overflow: hidden;
    }

    .funnel-card h4,
    .funnel-card p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .funnel-card .funnel-card-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .funnel-card .funnel-card-actions button {
        flex-shrink: 0;
        white-space: nowrap;
    }

    .funnel-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .funnel-card.dragging {
        opacity: 0.5;
    }

    .sortable-header {
        cursor: pointer;
        user-select: none;
        position: relative;
        padding-right: 20px;
    }

    .sortable-header:hover {
        color: #f59e0b;
    }

    .sortable-header::after {
        content: '↕';
        position: absolute;
        right: 0;
        opacity: 0.5;
    }

    .sortable-header.asc::after {
        content: '↑';
        opacity: 1;
    }

    .sortable-header.desc::after {
        content: '↓';
        opacity: 1;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
        margin-top: 1.5rem;
    }

    .pagination button {
        padding: 0.5rem 1rem;
        border: 1px solid #7c8799;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        color: #64748b;
        transition: all 0.3s;
    }

    .pagination button:hover:not(:disabled) {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .pagination button.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .tab-btn {
        background: #161615;
        border-color: #3E3E3A;
        color: #A1A09A;
    }

    .dark .tab-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .tab-btn.active {
        background: #0a0a0a;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .funnel-column {
        background: #0a0a0a;
        border-color: #3E3E3A;
    }

    .dark .funnel-column.drag-over {
        border-color: #f59e0b;
        background: #1D0002;
    }

    .dark .funnel-card {
        background: #161615;
        border-color: #3E3E3A;
    }

    .dark .pagination button {
        background: #161615;
        border-color: #3E3E3A;
        color: #A1A09A;
    }

    .dark .pagination button:hover:not(:disabled) {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .pagination button.active {
        background: #0a0a0a;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .chat-badge {
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

    .dark .chat-badge {
        border-color: #161615;
    }

    .chat-message-bubble-mine {
        background: #fef3c7;
        color: #92400e;
    }

    .chat-message-bubble-other {
        background: #f1f5f9;
        color: #334155;
    }

    .dark .chat-message-bubble-mine {
        background: #1D0002;
        color: #f59e0b;
    }

    .dark .chat-message-bubble-other {
        background: #0a0a0a;
        color: #EDEDEC;
    }
</style>
@endpush

@section('content')
<div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.supplier_orders') }}</h1>
    <button id="add-order-btn" class="add-btn">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('supplier-orders.add_supplier_order') }}
    </button>
</div>

<!-- Вкладки -->
<div class="mb-6 flex gap-2">
    <button data-tab="table" class="tab-btn active">{{ __('supplier-orders.table') }}</button>
    <button data-tab="list" class="tab-btn">{{ __('supplier-orders.list') }}</button>
    <button data-tab="funnel" class="tab-btn">{{ __('supplier-orders.funnel') }}</button>
</div>

<!-- Поиск и фильтры -->
<div class="mb-6 flex flex-col md:flex-row gap-4">
    <div class="flex-1">
        <input type="text" id="search-input" placeholder="{{ __('supplier-orders.search') }}"
               class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
    </div>
    <div class="w-full md:w-48">
        <select id="project-filter" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('supplier-orders.all_projects') }}</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" @if(isset($selectedProjectId) && $selectedProjectId == $project->id) selected @endif>{{ $project->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="w-full md:w-48">
        <select id="supplier-filter" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
            <option value="">{{ __('supplier-orders.all_suppliers') }}</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @if(isset($selectedSupplierId) && $selectedSupplierId == $supplier->id) selected @endif>{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="w-full md:w-48">
        <select id="status-filter" class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
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

<!-- Контент вкладок -->
<div id="table-view" class="tab-content">
    <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="number">{{ __('supplier-orders.number') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="created_date">{{ __('supplier-orders.created_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="supplier_name">{{ __('supplier-orders.supplier') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="project_name">{{ __('supplier-orders.project') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="status">{{ __('supplier-orders.status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="is_sent_to_supplier">{{ __('supplier-orders.send_status') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="amount">{{ __('supplier-orders.amount') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="planned_date">{{ __('supplier-orders.planned_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header" data-sort="actual_date">{{ __('supplier-orders.actual_date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.product_service') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.links') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.view') }}</th>
                    </tr>
                </thead>
                <tbody id="orders-table-body" class="divide-y divide-[#7c8799] dark:divide-[#3E3E3A]">
                    <!-- Данные загружаются через JavaScript -->
                </tbody>
            </table>
        </div>
        <!-- Пагинация -->
        <div class="pagination" id="pagination">
            <!-- Пагинация будет добавлена через JavaScript -->
        </div>
    </div>
</div>

<div id="list-view" class="tab-content hidden">
    <div class="space-y-4" id="orders-list-body">
        <!-- Данные будут загружены через JavaScript -->
    </div>
</div>

<div id="funnel-view" class="tab-content hidden">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 overflow-x-auto">
        <div class="funnel-column" data-status="order_created" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_order_created') }}</h3>
            <div id="funnel-order-created" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="order_sent" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_order_sent') }}</h3>
            <div id="funnel-order-sent" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="order_confirmed" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_order_confirmed') }}</h3>
            <div id="funnel-order-confirmed" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="advance_payment" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_advance_payment') }}</h3>
            <div id="funnel-advance-payment" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="full_payment" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_full_payment') }}</h3>
            <div id="funnel-full-payment" class="funnel-cards"></div>
        </div>
        <div class="funnel-column" data-status="delivery_completed" ondrop="drop(event)" ondragover="allowDrop(event)">
            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('supplier-orders.status_delivery_completed') }}</h3>
            <div id="funnel-delivery-completed" class="funnel-cards"></div>
        </div>
    </div>
</div>

<!-- Модалка просмотра заказа (справа) -->
<div id="view-order-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay" onmousedown="if(event.target === this) closeViewOrderModal()">
    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content" onclick="event.stopPropagation()">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between px-6 py-5 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.view') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('supplier-orders.supplier_order') }}</p>
                </div>
                <button onclick="closeViewOrderModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="view-order-content" class="flex-1 overflow-y-auto p-6 space-y-5"></div>
        </div>
    </div>
</div>

<!-- Модалка чата по поставке -->
<div id="order-chat-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay" onmousedown="if(event.target === this) closeOrderChatModal()">
    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content" onclick="event.stopPropagation()">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div>
                    <h3 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.chat_title') }}</h3>
                    <p id="order-chat-subtitle" class="text-sm text-[#64748b] dark:text-[#A1A09A]"></p>
                </div>
                <button type="button" onclick="closeOrderChatModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-3 border-b border-[#7c8799] dark:border-[#3E3E3A] flex items-center justify-between gap-2">
                <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_hint_manual_refresh') }}</p>
                <button type="button" onclick="refreshOrderChatMessages()" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b]">
                    {{ __('supplier-orders.chat_refresh') }}
                </button>
            </div>
            <div id="order-chat-messages" class="flex-1 overflow-y-auto p-6 space-y-3"></div>
            <form id="order-chat-form" class="border-t border-[#7c8799] dark:border-[#3E3E3A] p-4">
                <input type="hidden" id="order-chat-order-id" value="">
                <div class="flex items-end gap-2">
                    <textarea id="order-chat-input" rows="2" maxlength="5000" class="flex-1 px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]" placeholder="{{ __('supplier-orders.chat_placeholder') }}"></textarea>
                    <button type="submit" class="px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10">
                        {{ __('supplier-orders.chat_send') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модалка добавления/редактирования заказа -->
<div id="order-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4" onmousedown="if(event.target === this) closeOrderModal()">
    <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#7c8799] dark:border-[#3E3E3A]" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#7c8799] dark:border-[#3E3E3A] shrink-0">
            <div>
                <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="order-modal-title">{{ __('supplier-orders.add_supplier_order') }}</h2>
                <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('supplier-orders.order_info_subtitle') }}</p>
            </div>
            <button type="button" onclick="closeOrderModal()" class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e5e7eb] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="order-form" class="flex flex-col flex-1 min-h-0" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="order_id" id="order_id">
            <input type="hidden" name="send_to_supplier" id="send_to_supplier" value="0">
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label modal-label-required">{{ __('supplier-orders.select_project') }}</label>
                        <select name="project_id" id="order_project_id" required class="modal-input">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('supplier-orders.select_supplier') }}</label>
                        <select name="supplier_id" id="order_supplier_id" required class="modal-input">
                            <option value="">{{ __('supplier-orders.select_supplier') }}</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.status') }}</label>
                        <select name="status" id="order_status" class="modal-input">
                            <option value="order_created">{{ __('supplier-orders.status_order_created') }}</option>
                            <option value="order_sent">{{ __('supplier-orders.status_order_sent') }}</option>
                            <option value="order_confirmed">{{ __('supplier-orders.status_order_confirmed') }}</option>
                            <option value="advance_payment">{{ __('supplier-orders.status_advance_payment') }}</option>
                            <option value="full_payment">{{ __('supplier-orders.status_full_payment') }}</option>
                            <option value="delivery_completed">{{ __('supplier-orders.status_delivery_completed') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('supplier-orders.amount') }}</label>
                        <input type="number" name="summa" id="order_summa" min="0" step="1" required class="modal-input" placeholder="0">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.category') }}</label>
                        <select name="category" id="order_category" class="modal-input">
                            <option value="">{{ __('supplier-orders.select_category') }}</option>
                            @foreach (($categoryOptions ?? []) as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.mark_on_drawing') }}</label>
                        <input type="text" name="mark" id="order_mark" class="modal-input" placeholder="">
                    </div>
                </div>
                <div>
                    <label class="modal-label">{{ __('supplier-orders.room') }}</label>
                    <select name="room" id="order_room" class="modal-input">
                        <option value="">{{ __('supplier-orders.select_room') }}</option>
                        @foreach (($roomOptions ?? []) as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="order-steps-wrap">
                    <label class="modal-label">{{ __('supplier-orders.project_steps_for_supplier') }}</label>
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('supplier-orders.project_steps_hint') }}</p>
                    <div id="order-steps-container" class="max-h-52 overflow-y-auto rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] p-3 space-y-3 bg-[#f8fafc] dark:bg-[#0a0a0a] text-sm min-h-[3rem]"></div>
                    <p id="order-steps-loading" class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-2 hidden">{{ __('supplier-orders.project_steps_loading') }}</p>
                    <p id="order-steps-empty" class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-2 hidden">{{ __('supplier-orders.project_steps_empty') }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label modal-label-required">{{ __('supplier-orders.planned_delivery_date') }}</label>
                        <input type="date" name="date_planned" id="order_date_planned" required class="modal-input">
                    </div>
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.actual_delivery_date') }}</label>
                        <input type="date" name="date_actual" id="order_date_actual" class="modal-input">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.advance_date') }}</label>
                        <input type="date" name="prepayment_date" id="order_prepayment_date" class="modal-input">
                    </div>
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.balance_date') }}</label>
                        <input type="date" name="payment_date" id="order_payment_date" class="modal-input">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.advance_amount') }}</label>
                        <input type="number" name="prepayment_amount" id="order_prepayment_amount" min="0" step="1" class="modal-input" placeholder="0">
                    </div>
                    <div>
                        <label class="modal-label">{{ __('supplier-orders.balance_amount') }}</label>
                        <input type="number" name="payment_amount" id="order_payment_amount" min="0" step="1" class="modal-input" placeholder="0">
                    </div>
                </div>
                <div>
                    <label class="modal-label">{{ __('supplier-orders.links') }}</label>
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('supplier-orders.links_desc') }}</p>
                    <div id="order-links-container" class="space-y-2">
                        <input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input">
                    </div>
                    <button type="button" onclick="addOrderLinkField()" class="mt-2 text-sm font-medium modal-accent-link flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('supplier-orders.add_link') }}
                    </button>
                </div>
                <div>
                    <label class="modal-label">{{ __('supplier-orders.files') }}</label>
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('supplier-orders.upload_files_desc') }}</p>
                    <div class="flex gap-2">
                        <input type="file" name="files[]" id="order_files" multiple class="modal-input flex-1 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                        <label for="order_files" class="px-4 py-2 rounded-lg border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 cursor-pointer text-sm font-medium shrink-0">{{ __('supplier-orders.select_files') }}</label>
                    </div>
                </div>
                <div>
                    <label class="modal-label">{{ __('supplier-orders.product_service') }}</label>
                    <input type="text" name="comment" id="order_comment" class="modal-input" placeholder="{{ __('supplier-orders.product_service_placeholder') }}">
                </div>
            </div>
            <div class="modal-footer flex flex-col sm:flex-row gap-2">
                <button type="submit" name="action" value="save" class="btn-primary flex-1">{{ __('supplier-orders.save_without_send') }}</button>
                <button type="submit" name="action" value="send" class="px-6 py-2.5 rounded-lg border-2 border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 transition-colors font-medium flex-1">{{ __('supplier-orders.send_to_supplier') }}</button>
                <button type="button" onclick="closeOrderModal()" class="btn-secondary">{{ __('supplier-orders.close') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
window.allOrders = @json($orders);
window.supplierOrderProjectJsonBase = @json(url('/projects'));
window.orderChatBaseUrl = @json(url('/supplier-orders'));
window.orderChatUnreadMapUrl = @json(route('supplier-orders.chat.unread_map'));
function escapeHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function escapeAttr(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;');
}

function getChatButtonHtml(order, compact = false) {
    const unread = Math.max(0, parseInt(order.unread_chat_count || 0, 10));
    const badge = unread > 0 ? `<span class="chat-badge">${unread > 99 ? '99+' : unread}</span>` : '';
    if (compact) {
        return `<button onclick="openOrderChat(${order.id})" class="relative p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('supplier-orders.chat_open') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-7 7l-3-3a2 2 0 01-.586-1.414V6a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2H8z"/>
            </svg>
            ${badge}
        </button>`;
    }

    return `<button onclick="openOrderChat(${order.id})" class="relative filter-btn">{{ __('supplier-orders.chat_open') }}${unread > 0 ? ` <span class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full text-[10px] bg-red-500 text-white">${unread > 99 ? '99+' : unread}</span>` : ''}</button>`;
}

function setOrderUnreadCount(orderId, count) {
    const idx = (window.allOrders || []).findIndex(o => parseInt(o.id, 10) === parseInt(orderId, 10));
    if (idx >= 0) {
        window.allOrders[idx].unread_chat_count = Math.max(0, parseInt(count || 0, 10));
    }
}

async function refreshOrderProjectSteps(projectId, preselectedIds) {
    const container = document.getElementById('order-steps-container');
    const loading = document.getElementById('order-steps-loading');
    const empty = document.getElementById('order-steps-empty');
    if (!container) return;
    if (!projectId) {
        container.innerHTML = '';
        loading?.classList.add('hidden');
        empty?.classList.add('hidden');
        return;
    }
    loading?.classList.remove('hidden');
    empty?.classList.add('hidden');
    container.innerHTML = '';
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const r = await fetch(window.supplierOrderProjectJsonBase + '/' + encodeURIComponent(projectId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            credentials: 'same-origin',
        });
        if (!r.ok) throw new Error('fail');
        const data = await r.json();
        const stages = data.stages || [];
        const pre = new Set((preselectedIds || []).map(function (x) { return parseInt(x, 10); }));
        loading?.classList.add('hidden');
        let hasSteps = false;
        let html = '';
        stages.forEach(function (st) {
            const steps = (st.steps || []).filter(function (s) { return s.result_comment != null && s.result_comment != '' && s.id != null ; });
            if (!steps.length) return;
            hasSteps = true;
            const stLabel = String(st.stage_type_label || st.stage_type || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            html += '<div class="mb-3 last:mb-0"><div class="text-xs font-semibold text-[#64748b] dark:text-[#A1A09A] mb-2">' + stLabel + '</div><div class="space-y-2 pl-1">';
            steps.forEach(function (step) {
                const sid = parseInt(step.id, 10);
                const title = String(step.title || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                const checked = pre.has(sid) ? ' checked' : '';
                html += '<label class="flex items-start gap-2 cursor-pointer"><input type="checkbox" class="order-step-cb mt-0.5 rounded border-[#7c8799] dark:border-[#3E3E3A]" value="' + sid + '"' + checked + '><span class="text-[#0f172a] dark:text-[#EDEDEC]">' + title + '</span></label>';
            });
            html += '</div></div>';
        });
        container.innerHTML = html;
        if (!hasSteps) empty?.classList.remove('hidden');
    } catch (err) {
        loading?.classList.add('hidden');
        container.innerHTML = '<p class="text-xs text-red-600 dark:text-red-400">{{ __('supplier-orders.error') }}</p>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    const itemsPerPage = 10;
    let sortColumn = null;
    let sortDirection = 'asc';
    let currentView = 'table';

    // Переключение вкладок
    document.querySelectorAll('[data-tab]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            currentView = tab;

            document.querySelectorAll('[data-tab]').forEach(b => {
                b.classList.remove('active');
            });
            this.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tab + '-view').classList.remove('hidden');

            if (tab === 'table') {
                renderTable();
            } else if (tab === 'list') {
                renderList();
            } else if (tab === 'funnel') {
                renderFunnel();
            }
        });
    });

    // Получение отфильтрованных заказов
    function getFilteredOrders() {
        const search = document.getElementById('search-input').value.toLowerCase();
        const projectFilter = document.getElementById('project-filter').value;
        const supplierFilter = document.getElementById('supplier-filter').value;
        const statusFilter = document.getElementById('status-filter').value;

        return (window.allOrders || []).filter(order => {
            const searchStr = Object.values(order).join(' ').toLowerCase();
            const matchSearch = !search || searchStr.includes(search);
            const matchProject = !projectFilter || order.project_id == projectFilter;
            const matchSupplier = !supplierFilter || order.supplier_id == supplierFilter;
            const matchStatus = !statusFilter || order.status === statusFilter;
            return matchSearch && matchProject && matchSupplier && matchStatus;
        });
    }

    // Сортировка
    function sortOrders(column) {
        if (sortColumn === column) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            sortColumn = column;
            sortDirection = 'asc';
        }

        (window.allOrders || []).sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        currentPage = 1;
        renderTable();
        updateSortHeaders();
    }

    function updateSortHeaders() {
        document.querySelectorAll('.sortable-header').forEach(header => {
            header.classList.remove('asc', 'desc');
            if (header.dataset.sort === sortColumn) {
                header.classList.add(sortDirection);
            }
        });
    }

    // Рендеринг таблицы
    window.renderTable = function() {
        let filtered = getFilteredOrders();

        const start = (currentPage - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        const paginated = filtered.slice(start, end);

        const tbody = document.getElementById('orders-table-body');
        if (!tbody) return;

        if (paginated.length === 0) {
            tbody.innerHTML = '<tr><td colspan="12" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.no_orders') }}</td></tr>';
            renderPagination();
            return;
        }

        tbody.innerHTML = paginated.map(order => {
            const statusClass = order.status === 'order_created' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' :
                               order.status === 'order_sent' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200' :
                               order.status === 'order_confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' :
                               order.status === 'advance_payment' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' :
                               order.status === 'full_payment' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200' :
                               'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-200';
            const statusText = order.status === 'order_created' ? '{{ __('supplier-orders.status_order_created') }}' :
                              order.status === 'order_sent' ? '{{ __('supplier-orders.status_order_sent') }}' :
                              order.status === 'order_confirmed' ? '{{ __('supplier-orders.status_order_confirmed') }}' :
                              order.status === 'advance_payment' ? '{{ __('supplier-orders.status_advance_payment') }}' :
                              order.status === 'full_payment' ? '{{ __('supplier-orders.status_full_payment') }}' :
                              '{{ __('supplier-orders.status_delivery_completed') }}';
            const sendStatusClass = order.is_sent_to_supplier
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                : 'bg-slate-100 text-slate-700 dark:bg-slate-900/20 dark:text-slate-300';
            const sendStatusText = order.is_sent_to_supplier
                ? '{{ __('supplier-orders.sent_to_supplier') }}'
                : '{{ __('supplier-orders.not_sent_to_supplier') }}';

            const createdDate = new Date(order.created_date).toLocaleDateString('kk-KZ');
            console.log("createdDate", createdDate);
            const plannedDate = new Date(order.date_planned).toLocaleDateString('kk-KZ');
            const actualDate = order.date_actual ? new Date(order.date_actual).toLocaleDateString('kk-KZ') : '';

            return `
                <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-order-id="${order.id}" data-order='${JSON.stringify(order).replace(/'/g, "&#39;")}'>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${order.id}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${createdDate}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${order.supplier_name}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${order.project_name}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="order-status-badge px-2 py-1 rounded text-xs font-medium ${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs font-medium ${sendStatusClass}">${sendStatusText}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${parseInt(order.summa).toLocaleString('kk-KZ')} ₸</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${plannedDate}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${actualDate || '-'}</td>
                    <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${order.comment || '-'}</td>
                    <td class="px-4 py-3 text-sm">
                        ${order.links && order.links.length > 0 ? order.links.map(link => `<a href="${link}" target="_blank" class="text-[#f59e0b] hover:underline text-xs">{{ __('supplier-orders.links') }}</a>`).join(' ') : '<span class="text-[#64748b] dark:text-[#A1A09A]">-</span>'}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex items-center gap-2">
                            <button onclick="viewOrder(${order.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('supplier-orders.view') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                            ${getChatButtonHtml(order, true)}
                            <button onclick="editOrder(${order.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors" title="{{ __('supplier-orders.edit') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteOrder(${order.id})" class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-500 transition-colors" title="{{ __('supplier-orders.delete') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        renderPagination();
    };

    // Рендеринг списка
    window.renderList = function() {
        let filtered = getFilteredOrders();

        const listBody = document.getElementById('orders-list-body');
        if (!listBody) return;

        listBody.innerHTML = filtered.map(order => {
            const statusClass = order.status === 'order_created' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' :
                               order.status === 'order_sent' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200' :
                               order.status === 'order_confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200' :
                               order.status === 'advance_payment' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-200' :
                               order.status === 'full_payment' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-200' :
                               'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/20 dark:text-cyan-200';
            const statusText = order.status === 'order_created' ? '{{ __('supplier-orders.status_order_created') }}' :
                              order.status === 'order_sent' ? '{{ __('supplier-orders.status_order_sent') }}' :
                              order.status === 'order_confirmed' ? '{{ __('supplier-orders.status_order_confirmed') }}' :
                              order.status === 'advance_payment' ? '{{ __('supplier-orders.status_advance_payment') }}' :
                              order.status === 'full_payment' ? '{{ __('supplier-orders.status_full_payment') }}' :
                              '{{ __('supplier-orders.status_delivery_completed') }}';
            const sendStatusClass = order.is_sent_to_supplier
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200'
                : 'bg-slate-100 text-slate-700 dark:bg-slate-900/20 dark:text-slate-300';
            const sendStatusText = order.is_sent_to_supplier
                ? '{{ __('supplier-orders.sent_to_supplier') }}'
                : '{{ __('supplier-orders.not_sent_to_supplier') }}';

            const createdDate = new Date(order.created_date).toLocaleDateString('kk-KZ');
            
            const plannedDate = new Date(order.planned_date).toLocaleDateString('kk-KZ');
            const actualDate = order.actual_date ? new Date(order.actual_date).toLocaleDateString('kk-KZ') : '';

            return `
                <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6" data-order-id="${order.id}" data-order='${JSON.stringify(order).replace(/'/g, "&#39;")}'>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${order.number}</h3>
                            <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                <p>${order.supplier_name}</p>
                                <p>${order.project_name}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="order-status-badge px-2 py-1 rounded text-xs font-medium ${statusClass}">${statusText}</span>
                            <span class="px-2 py-1 rounded text-xs font-medium ${sendStatusClass}">${sendStatusText}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.created_date') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${createdDate}</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.amount') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${parseInt(order.amount).toLocaleString('kk-KZ')} ₸</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.planned_date') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${plannedDate}</span>
                        </div>
                        ${actualDate ? `
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.actual_date') }}:</span>
                            <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${actualDate}</span>
                        </div>
                        ` : ''}
                    </div>
                    ${order.product_service ? `<p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">${order.product_service}</p>` : ''}
                    <div class="flex items-center gap-2">
                        <button onclick="viewOrder(${order.id})" class="filter-btn">{{ __('supplier-orders.view') }}</button>
                        ${getChatButtonHtml(order)}
                        <button onclick="editOrder(${order.id})" class="filter-btn">{{ __('supplier-orders.edit') }}</button>
                        <button onclick="deleteOrder(${order.id})" class="filter-btn text-red-500 hover:text-red-600">{{ __('supplier-orders.delete') }}</button>
                    </div>
                </div>
            `;
        }).join('');
    };

    window.renderFunnel = function() {
        const statuses = ['order_created', 'order_sent', 'order_confirmed', 'advance_payment', 'full_payment', 'delivery_completed'];
        const viewLabel = '{{ __('supplier-orders.view') }}';
        const editLabel = '{{ __('supplier-orders.edit') }}';
        const deleteLabel = '{{ __('supplier-orders.delete') }}';
        statuses.forEach(status => {
            const container = document.getElementById(`funnel-${status.replace(/_/g, '-')}`);
            if (!container) return;

            const filtered = getFilteredOrders().filter(o => o.status === status);
            container.innerHTML = filtered.map(order => `
                <div class="funnel-card" draggable="true"
                    ondragstart="if(event.target.closest('button')){event.preventDefault();return false;}drag(event)"
                    data-order-id="${order.id}" data-order='${JSON.stringify(order).replace(/'/g, "&#39;")}'>
                    <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1 truncate" title="${(order.number || order.id || '').replace(/"/g,'&quot;')}">${order.number || order.id}</h4>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] truncate" title="${(order.supplier_name || '-').replace(/"/g,'&quot;')}">${order.supplier_name || '-'}</p>
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1 truncate" title="${(order.project_name || '-').replace(/"/g,'&quot;')}">${order.project_name || '-'}</p>
                    <div class="funnel-card-actions" onclick="event.stopPropagation()">
                        <button type="button" onclick="event.stopPropagation();viewOrder(${order.id})"
                            class="text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors shrink-0">${viewLabel}</button>
                        <button type="button" onclick="event.stopPropagation();openOrderChat(${order.id})"
                            class="relative text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors shrink-0">{{ __('supplier-orders.chat_open') }}${order.unread_chat_count > 0 ? ` <span class="ml-1 inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full text-[10px] bg-red-500 text-white">${order.unread_chat_count > 99 ? '99+' : order.unread_chat_count}</span>` : ''}</button>
                        <button type="button" onclick="event.stopPropagation();editOrder(${order.id})"
                            class="text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors shrink-0">${editLabel}</button>
                        <button type="button" onclick="event.stopPropagation();deleteOrder(${order.id})"
                            class="text-xs px-2 py-1 rounded border border-[#7c8799] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 transition-colors shrink-0">${deleteLabel}</button>
                    </div>
                </div>
            `).join('');
        });
    };

    // Пагинация
    function renderPagination() {
        const filtered = getFilteredOrders();
        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        const pagination = document.getElementById('pagination');
        if (!pagination) return;
        pagination.innerHTML = '';

        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '←';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        };
        pagination.appendChild(prevBtn);

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = i === currentPage ? 'active' : '';
                pageBtn.onclick = () => {
                    currentPage = i;
                    renderTable();
                };
                pagination.appendChild(pageBtn);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                const ellipsis = document.createElement('span');
                ellipsis.textContent = '...';
                ellipsis.className = 'px-2 text-[#64748b] dark:text-[#A1A09A]';
                pagination.appendChild(ellipsis);
            }
        }

        const nextBtn = document.createElement('button');
        nextBtn.textContent = '→';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        };
        pagination.appendChild(nextBtn);
    }

    // Обработчики событий
    document.querySelectorAll('.sortable-header').forEach(header => {
        header.addEventListener('click', () => {
            sortOrders(header.dataset.sort);
        });
    });

    // Поиск
    document.getElementById('search-input').addEventListener('input', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    // Фильтры
    document.getElementById('project-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    document.getElementById('supplier-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    document.getElementById('status-filter').addEventListener('change', () => {
        currentPage = 1;
        if (currentView === 'table') {
            renderTable();
        } else if (currentView === 'list') {
            renderList();
        } else if (currentView === 'funnel') {
            renderFunnel();
        }
    });

    // Кнопка добавления заказа
    document.getElementById('add-order-btn').addEventListener('click', () => {
        document.getElementById('order-modal').classList.remove('hidden');
        document.getElementById('order-modal').classList.add('flex');
        document.getElementById('order-form').reset();
        document.getElementById('order_id').value = '';
        document.getElementById('send_to_supplier').value = '0';
        document.getElementById('order-modal-title').textContent = '{{ __('supplier-orders.add_supplier_order') }}';
        const linksContainer = document.getElementById('order-links-container');
        if (linksContainer) linksContainer.innerHTML = `<input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input">`;
        @if(isset($selectedProjectId))
        document.getElementById('order_project_id').value = '{{ $selectedProjectId }}';
        @endif
        @if(isset($selectedSupplierId))
        document.getElementById('order_supplier_id').value = '{{ $selectedSupplierId }}';
        @endif
        const pidNew = document.getElementById('order_project_id')?.value;
        refreshOrderProjectSteps(pidNew || '', []);
    });

    document.getElementById('order_project_id')?.addEventListener('change', function () {
        refreshOrderProjectSteps(this.value, []);
    });

    document.getElementById('order-chat-form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const orderId = parseInt(document.getElementById('order-chat-order-id')?.value || '0', 10);
        const input = document.getElementById('order-chat-input');
        const message = (input?.value || '').trim();
        if (!orderId || !message) return;
        try {
            const r = await fetch(`${window.orderChatBaseUrl}/${orderId}/chat/messages`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message })
            });
            const data = await r.json();
            if (!r.ok || !data.success) {
                throw new Error('chat_send_failed');
            }
            input.value = '';
            await refreshOrderChatMessages();
        } catch (_) {
            projectAlert('error', '{{ __('supplier-orders.error') }}', '', 3000);
        }
    });

    // Инициализация
    renderTable();
    refreshOrderChatUnreadMap();
});

// Функции для работы с модалками
function closeViewOrderModal() {
    const modal = document.getElementById('view-order-modal');
    const panel = modal.querySelector('div[class*="absolute"]');
    modal.classList.add('hidden');
    if (panel) {
        panel.classList.add('translate-x-full');
        panel.classList.remove('translate-x-0');
    }
}

function viewOrder(id) {
    const rows = document.querySelectorAll(`tr[data-order], div[data-order]`);
    let order = null;
    rows.forEach(row => {
        const o = JSON.parse(row.getAttribute('data-order'));
        if (o.id === id) {
            order = o;
        }
    });
    if (order) {
        const content = document.getElementById('view-order-content');
        const fileItems = Array.isArray(order.file_items) ? order.file_items : [];
        const statusText = order.status === 'order_created' ? '{{ __('supplier-orders.status_order_created') }}' :
                          order.status === 'order_sent' ? '{{ __('supplier-orders.status_order_sent') }}' :
                          order.status === 'order_confirmed' ? '{{ __('supplier-orders.status_order_confirmed') }}' :
                          order.status === 'advance_payment' ? '{{ __('supplier-orders.status_advance_payment') }}' :
                          order.status === 'full_payment' ? '{{ __('supplier-orders.status_full_payment') }}' :
                          '{{ __('supplier-orders.status_delivery_completed') }}';
        const sendStatusText = order.is_sent_to_supplier
            ? '{{ __('supplier-orders.sent_to_supplier') }}'
            : '{{ __('supplier-orders.not_sent_to_supplier') }}';

        const createdDate = new Date(order.created_date).toLocaleDateString('kk-KZ');
        const plannedDate = new Date(order.planned_date).toLocaleDateString('kk-KZ');
        const actualDate = order.actual_date ? new Date(order.actual_date).toLocaleDateString('kk-KZ') : '';
        const filesHtml = fileItems.length ? `
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('supplier-orders.files') }}</label>
                <div class="space-y-2">
                    ${fileItems.map((file, index) => `
                        <div class="flex items-center gap-3 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white/60 dark:bg-[#0a0a0a] px-3 py-2">
                            <div class="shrink-0 text-[#64748b] dark:text-[#A1A09A]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7V6a2 2 0 012-2h6a2 2 0 012 2v1m-9 4h8m-8 4h5m-7 5h10a2 2 0 002-2V7H6v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1 truncate text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(file.name || '')}</div>
                            <div class="flex items-center gap-1.5">
                                <a href="${escapeAttr(file.url || '')}" target="_blank" rel="noopener" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors" title="{{ __('supplier-orders.view') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                                <a href="${escapeAttr(file.url || '')}" download="${escapeAttr(file.name || '')}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors" title="{{ __('objects.download') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" /></svg>
                                </a>
                                <button type="button" onclick="deleteOrderFileFromIndex(${order.id}, ${index})" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors" title="{{ __('objects.delete_file') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '';

        content.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.number') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${order.number}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.created_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${createdDate}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.supplier') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${order.supplier_name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.project') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${order.project_name}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.status') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${statusText}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.send_status') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${sendStatusText}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.amount') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${parseInt(order.amount).toLocaleString('kk-KZ')} ₸</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.planned_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${plannedDate}</p>
            </div>
            ${actualDate ? `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.actual_date') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${actualDate}</p>
            </div>
            ` : ''}
            ${order.product_service ? `
            <div>
                <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('supplier-orders.product_service') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC]">${order.product_service}</p>
            </div>
            ` : ''}
            ${filesHtml}
            <div class="pt-4">
                <a href="/supplier-orders/${order.id}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                    title="{{ __('supplier-orders.details') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('supplier-orders.details') }}
                </a>
            </div>
        `;
        const modal = document.getElementById('view-order-modal');
        const panel = modal.querySelector('div[class*="absolute"]');
        modal.classList.remove('hidden');
        setTimeout(() => {
            if (panel) {
                panel.classList.remove('translate-x-full');
                panel.classList.add('translate-x-0');
            }
        }, 10);
    }
}

async function deleteOrderFileFromIndex(orderId, fileIndex) {
    if (!confirm('{{ __('objects.delete_file_confirm') }}')) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const r = await fetch(`/supplier-orders/${orderId}/files/${fileIndex}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data.success) {
            projectAlert('error', data.message || '{{ __('supplier-orders.error') }}', '', 3200);
            return;
        }
        location.reload();
    } catch (e) {
        console.error(e);
        projectAlert('error', '{{ __('supplier-orders.error') }}', '', 3200);
    }
}

function closeOrderChatModal() {
    const modal = document.getElementById('order-chat-modal');
    const panel = modal?.querySelector('div[class*="absolute"]');
    modal?.classList.add('hidden');
    if (panel) {
        panel.classList.add('translate-x-full');
        panel.classList.remove('translate-x-0');
    }
}

function renderOrderChatMessages(items) {
    const wrap = document.getElementById('order-chat-messages');
    if (!wrap) return;
    if (!Array.isArray(items) || items.length === 0) {
        wrap.innerHTML = `<p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_empty') }}</p>`;
        return;
    }

    wrap.innerHTML = items.map((m) => {
        const mine = !!m.is_mine;
        const alignClass = mine ? 'justify-end' : 'justify-start';
        const bubbleClass = mine ? 'chat-message-bubble-mine' : 'chat-message-bubble-other';
        const sender = mine ? '{{ __('supplier-orders.chat_you') }}' : (m.sender_name || '-');
        const ts = m.created_at ? new Date(m.created_at).toLocaleString('ru-RU') : '';
        const safeText = String(m.message || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
        return `<div class="flex ${alignClass}">
            <div class="max-w-[80%] rounded-xl px-3 py-2 ${bubbleClass}">
                <div class="text-xs opacity-80 mb-1">${sender}</div>
                <div class="text-sm whitespace-pre-wrap break-words">${safeText}</div>
                <div class="text-[10px] opacity-70 mt-1">${ts}</div>
            </div>
        </div>`;
    }).join('');
    wrap.scrollTop = wrap.scrollHeight;
}

async function refreshOrderChatUnreadMap() {
    try {
        const r = await fetch(window.orderChatUnreadMapUrl, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await r.json();
        if (!r.ok || !data.success) return;
        const map = data.unread || {};
        (window.allOrders || []).forEach((o) => {
            o.unread_chat_count = parseInt(map[o.id] || 0, 10);
        });
        window.renderTable?.();
        window.renderList?.();
        window.renderFunnel?.();
    } catch (_) {}
}

async function openOrderChat(orderId) {
    const order = (window.allOrders || []).find(o => parseInt(o.id, 10) === parseInt(orderId, 10));
    document.getElementById('order-chat-order-id').value = String(orderId);
    document.getElementById('order-chat-subtitle').textContent = order
        ? `#${order.number || order.id} • ${order.supplier_name || '-'}`
        : `#${orderId}`;

    const modal = document.getElementById('order-chat-modal');
    const panel = modal?.querySelector('div[class*="absolute"]');
    modal?.classList.remove('hidden');
    setTimeout(() => {
        if (panel) {
            panel.classList.remove('translate-x-full');
            panel.classList.add('translate-x-0');
        }
    }, 10);

    await refreshOrderChatMessages();
}

async function refreshOrderChatMessages() {
    const orderId = parseInt(document.getElementById('order-chat-order-id')?.value || '0', 10);
    if (!orderId) return;
    const wrap = document.getElementById('order-chat-messages');
    if (wrap) {
        wrap.innerHTML = `<p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.chat_loading') }}</p>`;
    }

    try {
        const r = await fetch(`${window.orderChatBaseUrl}/${orderId}/chat/messages`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await r.json();
        if (!r.ok || !data.success) {
            throw new Error('chat_load_failed');
        }
        renderOrderChatMessages(data.messages || []);
        await fetch(`${window.orderChatBaseUrl}/${orderId}/chat/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            }
        });
        setOrderUnreadCount(orderId, 0);
        await refreshOrderChatUnreadMap();
    } catch (e) {
        if (wrap) {
            wrap.innerHTML = `<p class="text-sm text-red-500">{{ __('supplier-orders.error') }}</p>`;
        }
    }
}

function formatDateForInput(val) {
    if (!val) return '';
    const s = String(val);
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? m[0] : '';
}

function editOrder(id) {
    const rows = document.querySelectorAll(`tr[data-order], div[data-order], .funnel-card[data-order]`);
    let order = null;
    rows.forEach(row => {
        try {
            const o = JSON.parse(row.getAttribute('data-order') || '{}');
            if (o.id === id) order = o;
        } catch(_) {}
    });
    if (order) {
        document.getElementById('order-modal').classList.remove('hidden');
        document.getElementById('order-modal').classList.add('flex');
        document.getElementById('order-modal-title').textContent = '{{ __('supplier-orders.edit') }}';
        document.getElementById('order_id').value = order.id;
        document.getElementById('order_project_id').value = order.project_id || '';
        document.getElementById('order_supplier_id').value = order.supplier_id || '';
        document.getElementById('order_status').value = order.status || 'order_created';
        document.getElementById('order_summa').value = order.amount ?? order.summa ?? '';
        document.getElementById('order_category').value = order.category || '';
        document.getElementById('order_mark').value = order.mark || '';
        document.getElementById('order_room').value = order.room || '';
        document.getElementById('order_date_planned').value = formatDateForInput(order.date_planned || order.planned_date);
        document.getElementById('order_date_actual').value = formatDateForInput(order.date_actual || order.actual_date);
        document.getElementById('order_prepayment_date').value = formatDateForInput(order.prepayment_date);
        document.getElementById('order_payment_date').value = formatDateForInput(order.payment_date);
        document.getElementById('order_prepayment_amount').value = order.prepayment_amount ?? '';
        document.getElementById('order_payment_amount').value = order.payment_amount ?? '';
        document.getElementById('order_comment').value = order.product_service || order.comment || '';
        const linksContainer = document.getElementById('order-links-container');
        const links = order.links || [];
        linksContainer.innerHTML = links.length ? links.map(l => {
            const v = (l||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;');
            return `<div class="flex gap-2"><input type="url" name="links[]" value="${v}" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input flex-1"><button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500">×</button></div>`;
        }).join('') + `<div class="flex gap-2"><input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input flex-1"><button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500">×</button></div>` :
            `<input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input">`;
        refreshOrderProjectSteps(order.project_id, order.included_step_ids || []);
    }
}

function deleteOrder(id) {
    if (!confirm('{{ __("supplier-orders.delete_confirm") }}')) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
    fetch('{{ url("supplier-orders") }}/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
    }).then(r => {
        if (r.ok) {
            window.allOrders = (window.allOrders || []).filter(o => o.id != id);
            document.querySelectorAll('tr[data-order], #orders-list-body > div[data-order], .funnel-card[data-order-id]').forEach(el => {
                const oid = el.dataset.orderId || (() => { try { return JSON.parse(el.getAttribute('data-order')||'{}').id; } catch(_) { return null; } })();
                if (oid == id) el.remove();
            });
            if (typeof window.renderTable === 'function') window.renderTable();
            if (typeof window.renderList === 'function') window.renderList();
            if (typeof window.renderFunnel === 'function') window.renderFunnel();
        } else throw new Error('Delete failed');
    }).catch(() => projectAlert('error', '{{ __("supplier-orders.error") }}', '', 3200));
}

function closeOrderModal() {
    document.getElementById('order-modal').classList.add('hidden');
    document.getElementById('order-modal').classList.remove('flex');
    document.getElementById('order-form').reset();
    document.getElementById('order_id').value = '';
    document.getElementById('send_to_supplier').value = '0';
    const linksContainer = document.getElementById('order-links-container');
    linksContainer.innerHTML = `<input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input">`;
    const sc = document.getElementById('order-steps-container');
    if (sc) sc.innerHTML = '';
    document.getElementById('order-steps-loading')?.classList.add('hidden');
    document.getElementById('order-steps-empty')?.classList.add('hidden');
}

function addOrderLinkField() {
    const container = document.getElementById('order-links-container');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="url" name="links[]" placeholder="{{ __('supplier-orders.paste_link') }}" class="modal-input flex-1">
        <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 hover:border-red-300 transition-colors">×</button>
    `;
    container.appendChild(div);
}

document.getElementById('order-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const sendBtn = e.submitter && e.submitter.value === 'send';
    document.getElementById('send_to_supplier').value = sendBtn ? '1' : '0';

    const orderId = document.getElementById('order_id').value;
    const url = orderId ? '{{ url("supplier-orders") }}/' + orderId : '{{ route("supplier-orders.store") }}';
    const formData = new FormData();
    for (const pair of new FormData(form).entries()) {
        if (pair[0] === 'included_step_ids[]') continue;
        formData.append(pair[0], pair[1]);
    }
    formData.delete('included_step_ids[]');
    const picked = document.querySelectorAll('#order-steps-container input.order-step-cb:checked');
    if (picked.length === 0) {
        formData.append('included_step_ids[]', '');
    } else {
        picked.forEach(function (cb) { formData.append('included_step_ids[]', cb.value); });
    }
    if (orderId) formData.append('_method', 'PUT');

    const token = document.querySelector('meta[name="csrf-token"]')?.content || form.querySelector('input[name="_token"]')?.value;
    formData.append('_token', token);

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: formData
    })
    .then(r => {
        if (r.status === 422) {
            return r.json().then(d => {
                throw { validation: true, errors: d.errors, message: d.message };
            });
        }
        if (!r.ok) throw new Error('Request failed');
        return r.json();
    })
    .then(data => {
        if (data.success && data.order) {
            const idx = (window.allOrders || []).findIndex(o => o.id == data.order.id);
            if (idx >= 0) window.allOrders[idx] = data.order;
            else window.allOrders.unshift(data.order);
            if (typeof window.renderTable === 'function') window.renderTable();
            if (typeof window.renderList === 'function') window.renderList();
            if (typeof window.renderFunnel === 'function') window.renderFunnel();
            closeOrderModal();
        }
    })
    .catch(err => {
        if (err.validation) {
            const errs = err.errors && typeof err.errors === 'object' ? err.errors : {};
            const lines = Object.values(errs).flat().filter(Boolean);
            const msg = lines.length ? lines.join('\n') : (typeof err.message === 'string' ? err.message : '');
            if (msg) {
                projectAlert('error', msg, '', 4000);
            } else {
                projectAlert('error', '{{ __("supplier-orders.error") }}', '', 3200);
            }
        } else {
            console.error(err);
            projectAlert('error', '{{ __("supplier-orders.error") }}', '', 3200);
        }
    });
});

// Drag & Drop для воронок
let draggedOrderElement = null;

function allowDrop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.add('drag-over');
}

function drag(ev) {
    draggedOrderElement = ev.target.closest('.funnel-card');
    if (draggedOrderElement) {
        draggedOrderElement.classList.add('dragging');
        ev.dataTransfer.effectAllowed = 'move';
    }
}

function drop(ev) {
    ev.preventDefault();
    ev.currentTarget.classList.remove('drag-over');

    if (draggedOrderElement) {
        const newStatus = ev.currentTarget.dataset.status;
        const orderId = draggedOrderElement.dataset.orderId;

        ev.currentTarget.querySelector('.funnel-cards').appendChild(draggedOrderElement);
        draggedOrderElement.classList.remove('dragging');

        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch(`{{ url("supplier-orders") }}/${orderId}/status`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const order = (window.allOrders || []).find(o => o.id == orderId);
                if (order) order.status = newStatus;
                document.querySelectorAll(`[data-order-id="${orderId}"]`).forEach(el => {
                    const badge = el.querySelector('.order-status-badge');
                    if (badge) {
                        const labels = { order_created: '{{ __("supplier-orders.status_order_created") }}', order_sent: '{{ __("supplier-orders.status_order_sent") }}', order_confirmed: '{{ __("supplier-orders.status_order_confirmed") }}', advance_payment: '{{ __("supplier-orders.status_advance_payment") }}', full_payment: '{{ __("supplier-orders.status_full_payment") }}', delivery_completed: '{{ __("supplier-orders.status_delivery_completed") }}' };
                        badge.textContent = labels[newStatus] || newStatus;
                    }
                    try {
                        const o = JSON.parse(el.getAttribute('data-order')||'{}');
                        o.status = newStatus;
                        el.setAttribute('data-order', JSON.stringify(o));
                    } catch(_) {}
                });
            }
            draggedOrderElement = null;
        })
        .catch(() => { draggedOrderElement = null; if (typeof window.renderFunnel === 'function') window.renderFunnel(); });
    }
}

document.querySelectorAll('.funnel-column').forEach(column => {
    column.addEventListener('dragleave', function(e) {
        if (!column.contains(e.relatedTarget)) {
            column.classList.remove('drag-over');
        }
    });
});
</script>
@endsection
