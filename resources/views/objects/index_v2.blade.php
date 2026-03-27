@extends('layouts.dashboard')

@section('title', __('objects.object_passport'))

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }

        .sortable-header::before,
        .sortable-header::after {
            content: '';
            position: absolute;
            right: 0;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            pointer-events: none;
            opacity: 0.5;
        }

        .sortable-header::before {
            top: calc(50% - 8px);
            border-bottom: 6px solid currentColor;
        }

        .sortable-header::after {
            top: calc(50% + 2px);
            border-top: 6px solid currentColor;
        }

        .sortable-header.asc::before {
            opacity: 1;
        }

        .sortable-header.asc::after {
            opacity: 0;
        }

        .sortable-header.desc::before {
            opacity: 0;
        }

        .sortable-header.desc::after {
            opacity: 1;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 0 0.2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
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

        .funnel-column {
            min-height: 360px;
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            border: 2px dashed #e2e8f0;
        }

        .funnel-column.drag-over {
            border-color: #f59e0b;
            background: #fef3c7;
        }

        .funnel-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.95rem;
            margin-bottom: 0.5rem;
            cursor: move;
            transition: all 0.2s;
        }

        .funnel-card.dragging {
            opacity: 0.5;
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

        .tab-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .tab-btn.active {
            background: #f1f5f9;
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .dark .tab-btn {
            background: #161615;
            border-color: #3E3E3A;
            color: #A1A09A;
        }

        .dark .tab-btn.active {
            background: #0a0a0a;
            border-color: #f59e0b;
            color: #f59e0b;
        }

        .object-map {
            height: 250px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .dark .object-map {
            border-color: #3E3E3A;
        }

        .address-suggest {
            position: relative;
        }

        .address-suggest-list {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            z-index: 80;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            max-height: 220px;
            overflow-y: auto;
        }

        .dark .address-suggest-list {
            background: #161615;
            border-color: #3E3E3A;
        }

        .address-suggest-item {
            width: 100%;
            text-align: left;
            padding: 0.55rem 0.75rem;
            font-size: 0.875rem;
            color: #0f172a;
            border: 0;
            background: transparent;
        }

        .address-suggest-item:hover {
            background: #f8fafc;
        }

        .dark .address-suggest-item {
            color: #EDEDEC;
        }

        .dark .address-suggest-item:hover {
            background: #0a0a0a;
        }
    </style>
@endpush

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">
            {{ __('objects.object_passport') }}
        </h1>
        <button id="add-object-btn" class="add-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('objects.add_object') }}
        </button>
    </div>

    <div class="mb-6 flex gap-2">
        <button data-tab="table" class="tab-btn active">{{ __('objects.table') }}</button>
        <button data-tab="list" class="tab-btn">{{ __('objects.list') }}</button>
        <button data-tab="funnel" class="tab-btn">{{ __('objects.funnel') }}</button>
    </div>

    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" id="search-input" placeholder="{{ __('objects.search') }}"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
        </div>

        <div class="w-full md:w-48">
            <select id="type-filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('objects.all_types') }}</option>
                <option value="apartment">{{ __('objects.apartment') }}</option>
                <option value="house">{{ __('objects.house') }}</option>
                <option value="commercial">{{ __('objects.commercial') }}</option>
                <option value="other">{{ __('objects.other') }}</option>
            </select>
        </div>

        <div class="w-full md:w-52">
            <select id="client-filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('objects.all_clients') }}</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full md:w-48">
            <select id="status-filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('objects.all_statuses') }}</option>
                <option value="new">{{ __('objects.new') }}</option>
                <option value="in_work">{{ __('objects.in_work') }}</option>
                <option value="not_working">{{ __('objects.not_working') }}</option>
            </select>
        </div>
    </div>

    <div id="table-view" class="tab-content">
        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="address">{{ __('objects.address') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="type">{{ __('objects.type') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="status">{{ __('objects.status') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="client_name">{{ __('objects.client') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="area">{{ __('objects.area') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="repair_budget_planned">{{ __('objects.planned') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="repair_budget_per_m2_planned">{{ __('objects.repair_budget_per_m2') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="comment">{{ __('objects.comment') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.view') }}</th>
                        </tr>
                    </thead>
                    <tbody id="objects-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]"></tbody>
                </table>
            </div>
            <div class="pagination" id="objects-pagination-table"></div>
        </div>

        <div class="mt-3 flex justify-end">
            <div class="w-full md:w-56 shrink-0">
                <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.per_page') }}</div>
                <div class="relative">
                    <button id="objects-per-page-button" type="button"
                        class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                        <span id="objects-per-page-label">10</span>
                        <svg class="w-4 h-4 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="objects-per-page-menu"
                        class="hidden absolute left-0 right-0 mt-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg overflow-hidden z-[60]">
                        @foreach ([10, 30, 50, 100] as $v)
                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left objects-per-page-option"
                                data-value="{{ $v }}">
                                <span class="objects-per-page-check hidden mr-2 items-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </span>
                                {{ $v }}
                            </button>
                        @endforeach
                    </div>

                    <select id="objects-per-page" class="hidden">
                        <option value="10" selected>10</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div id="list-view" class="tab-content hidden">
        <div class="space-y-4" id="objects-list-body"></div>
        <div class="pagination" id="objects-pagination-list"></div>
    </div>

    <div id="funnel-view" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="funnel-column" data-status="new" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.new') }}</h3>
                <div id="funnel-new" class="funnel-cards"></div>
            </div>
            <div class="funnel-column" data-status="in_work" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.in_work') }}</h3>
                <div id="funnel-in-work" class="funnel-cards"></div>
            </div>
            <div class="funnel-column" data-status="not_working" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.not_working') }}
                </h3>
                <div id="funnel-not-working" class="funnel-cards"></div>
            </div>
        </div>
    </div>

    <!-- View modal -->
    <div id="view-object-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay"
        onmousedown="if(event.target === this) closeViewObjectModal()">
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#e2e8f0] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-200 translate-x-full modal-content"
            onclick="event.stopPropagation()">
            <div class="flex flex-col h-full">
                <div
                    class="flex items-center justify-between px-6 py-5 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('objects.view') }}</h2>
                        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('objects.view') }}</p>
                    </div>
                    <button onclick="closeViewObjectModal()"
                        class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-object-content" class="flex-1 overflow-y-auto p-6 space-y-4"></div>
            </div>
        </div>
    </div>

    <!-- Add/Edit modal -->
    <div id="object-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeObjectModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] shrink-0">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="object-modal-title">
                        {{ __('objects.new_object') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('objects.modal_object_subtitle') }}
                    </p>
                </div>
                <button type="button" onclick="closeObjectModal()"
                    class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="object-form" method="POST" action="{{ route('objects.add_object') }}"
                enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0" autocomplete="off">
                @csrf
                <input type="hidden" name="object_id" id="object_id">

                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.city') }}</label>
                            <select name="city" required id="object_city" class="modal-input">
                                <option value="">{{ __('objects.select_city') }}</option>
                                @foreach (['Алматы', 'Астана', 'Шымкент', 'Караганда', 'Актобе', 'Тараз', 'Павлодар', 'Усть-Каменогорск', 'Семей', 'Атырау', 'Костанай', 'Кызылорда', 'Уральск', 'Петропавловск', 'Актау', 'Темиртау', 'Туркестан', 'Кокшетау', 'Талдыкорган', 'Экибастуз'] as $city)
                                    <option value="{{ $city }}">{{ $city }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.select_client') }}</label>
                            <select name="client_id" required id="object_client_id" class="modal-input">
                                <option value="">{{ __('objects.select_client_placeholder') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.type') }}</label>
                            <select name="type" required id="object_type" class="modal-input">
                                <option value="apartment">{{ __('objects.apartment') }}</option>
                                <option value="house">{{ __('objects.house') }}</option>
                                <option value="commercial">{{ __('objects.commercial') }}</option>
                                <option value="other">{{ __('objects.other') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="address-suggest">
                        <label class="modal-label modal-label-required">{{ __('objects.address') }}</label>
                        <input type="text" name="address" id="object_address" required
                            placeholder="{{ __('objects.address_placeholder') }}" class="modal-input" autocomplete="off"
                            autocorrect="off" spellcheck="false">
                        <div id="object-address-suggest-list" class="address-suggest-list hidden"></div>
                    </div>

                    <div id="object-floor-wrap" class="object-apartment-field hidden">
                        <label class="modal-label modal-label-required">{{ __('objects.apartment_floor') }}</label>
                        <input type="text" name="apartment_floor" id="object_apartment_floor"
                            placeholder="{{ __('objects.apartment_floor') }}" class="modal-input" autocomplete="off"
                            autocorrect="off" spellcheck="false">
                    </div>

                    <div id="object-entrance-wrap" class="object-apartment-field hidden">
                        <label class="modal-label modal-label-required">{{ __('objects.apartment_entrance') }}</label>
                        <input type="text" name="apartment_entrance" id="object_apartment_entrance"
                            placeholder="{{ __('objects.apartment_entrance') }}" class="modal-input" autocomplete="off"
                            autocorrect="off" spellcheck="false">
                    </div>

                    <div id="object-apartment-wrap" class="object-apartment-field hidden">
                        <label class="modal-label modal-label-required">{{ __('objects.apartment_number') }}</label>
                        <input type="text" name="apartment" id="object_apartment"
                            placeholder="{{ __('objects.apartment_placeholder') }}" class="modal-input"
                            autocomplete="off" autocorrect="off" spellcheck="false">
                    </div>

                    <div>
                        <label class="modal-label">{{ __('objects.map_point') }}</label>
                        <p class="modal-helper mb-2">{{ __('objects.map_hint') }}</p>
                        <div id="object-map" class="object-map"></div>
                        <input type="hidden" name="latitude" id="object_latitude">
                        <input type="hidden" name="longitude" id="object_longitude">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.status') }}</label>
                            <select name="status" required id="object_status" class="modal-input">
                                <option value="new">{{ __('objects.new') }}</option>
                                <option value="in_work">{{ __('objects.in_work') }}</option>
                                <option value="not_working">{{ __('objects.not_working') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.area') }}
                                ({{ __('objects.area_m2') }})</label>
                            <input type="number" step="0.01" name="area" id="object_area" required
                                placeholder="{{ __('objects.area_placeholder') }}" class="modal-input">
                        </div>
                    </div>

                    <div>
                        <h3 class="modal-section-title">{{ __('objects.repair_budget') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <input type="number" step="0.01" name="repair_budget_planned"
                                    id="repair_budget_planned" placeholder="{{ __('objects.planned') }}"
                                    class="modal-input">
                            </div>
                            <div>
                                <input type="number" step="0.01" name="repair_budget_actual"
                                    id="repair_budget_actual" placeholder="{{ __('objects.actual') }}"
                                    class="modal-input">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="modal-section-title">{{ __('objects.repair_budget_per_m2') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <input type="number" step="0.01" name="repair_budget_per_m2_planned"
                                    id="repair_budget_per_m2_planned" placeholder="{{ __('objects.planned') }}"
                                    class="modal-input">
                            </div>
                            <div>
                                <input type="number" step="0.01" name="repair_budget_per_m2_actual"
                                    id="repair_budget_per_m2_actual" placeholder="{{ __('objects.actual') }}"
                                    class="modal-input">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="modal-label">{{ __('objects.links') }}</label>
                        <p class="modal-helper">{{ __('objects.links_subtitle') }}</p>
                        <textarea name="links_text" id="object_links_text" rows="3" placeholder="{{ __('objects.paste_link') }}"
                            class="modal-input resize-none"></textarea>
                    </div>

                    <div>
                        <label class="modal-label">{{ __('objects.files') }}</label>
                        <input type="file" name="files[]" multiple
                            class="modal-input file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                    </div>

                    <div>
                        <label class="modal-label">{{ __('objects.comment') }}</label>
                        <textarea name="comment" id="object_comment" rows="3" placeholder="{{ __('objects.comment') }}"
                            class="modal-input resize-none"></textarea>
                    </div>
                </div>

                <div class="px-6 pb-5 mt-auto flex items-center justify-between">
                    <div class="flex gap-3">
                        <button id="btn-save-object" type="submit" class="btn btn-primary">
                            {{ __('objects.save') }}
                        </button>
                        <button type="button" onclick="closeObjectModal()" class="btn btn-secondary">
                            {{ __('objects.cancel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentPage = 1;
            let itemsPerPage = 10;
            let sortColumn = null;
            let sortDirection = 'asc';

            window.allObjects = @json($objects);
            let allObjects = window.allObjects;

            const searchEl = document.getElementById('search-input');
            const typeFilterEl = document.getElementById('type-filter');
            const clientFilterEl = document.getElementById('client-filter');
            const statusFilterEl = document.getElementById('status-filter');

            const tbody = document.getElementById('objects-table-body');
            const listBody = document.getElementById('objects-list-body');

            const perPageSelect = document.getElementById('objects-per-page');
            const perPageButton = document.getElementById('objects-per-page-button');
            const perPageLabel = document.getElementById('objects-per-page-label');
            const perPageMenu = document.getElementById('objects-per-page-menu');

            if (perPageSelect) {
                itemsPerPage = parseInt(perPageSelect.value, 10) || 10;
            }
            if (perPageLabel) {
                perPageLabel.textContent = String(itemsPerPage);
            }

            const setActivePerPage = (value) => {
                if (!perPageMenu) return;
                perPageMenu.querySelectorAll('.objects-per-page-option').forEach(btn => {
                    const isActive = parseInt(btn.dataset.value, 10) === value;
                    btn.classList.toggle('bg-[#fef3c7]', isActive);
                    btn.classList.toggle('dark:bg-[#1D0002]', isActive);
                    btn.classList.toggle('text-[#f59e0b]', isActive);
                    btn.classList.toggle('dark:text-[#f59e0b]', isActive);

                    const check = btn.querySelector('.objects-per-page-check');
                    if (check) {
                        check.classList.toggle('hidden', !isActive);
                        check.classList.toggle('inline-flex', isActive);
                    }
                });
            };

            setActivePerPage(itemsPerPage);

            // Toggle objects-per-page menu
            if (perPageButton && perPageMenu) {
                const toggleMenu = () => perPageMenu.classList.toggle('hidden');
                perPageButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenu();
                });
                document.addEventListener('click', function() {
                    if (perPageMenu && !perPageMenu.classList.contains('hidden')) perPageMenu.classList.add(
                        'hidden');
                });

                perPageMenu.querySelectorAll('.objects-per-page-option').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const value = parseInt(this.dataset.value, 10);
                        if (!value) return;
                        itemsPerPage = value;
                        currentPage = 1;
                        if (perPageSelect) perPageSelect.value = String(value);
                        if (perPageLabel) perPageLabel.textContent = String(value);
                        setActivePerPage(value);
                        if (perPageMenu) perPageMenu.classList.add('hidden');
                        window.renderActiveTab?.();
                    });
                });
            }

            const objectTypeEl = document.getElementById('object_type');
            const objectApartmentFields = document.querySelectorAll('.object-apartment-field');
            const objectApartmentEl = document.getElementById('object_apartment');
            const objectFloorEl = document.getElementById('object_apartment_floor');
            const objectEntranceEl = document.getElementById('object_apartment_entrance');
            const objectLatEl = document.getElementById('object_latitude');
            const objectLngEl = document.getElementById('object_longitude');
            const objectCityEl = document.getElementById('object_city');
            const objectAddressEl = document.getElementById('object_address');
            const objectSuggestListEl = document.getElementById('object-address-suggest-list');

            function syncApartmentVisibility() {
                const show = objectTypeEl?.value === 'apartment';
                objectApartmentFields.forEach(el => el.classList.toggle('hidden', !show));
                if (objectApartmentEl) {
                    objectApartmentEl.required = !!show;
                    if (!show) objectApartmentEl.value = '';
                }
                if (objectFloorEl) {
                    objectFloorEl.required = !!show;
                    if (!show) objectFloorEl.value = '';
                }
                if (objectEntranceEl) {
                    objectEntranceEl.required = !!show;
                    if (!show) objectEntranceEl.value = '';
                }
            }

            objectTypeEl?.addEventListener('change', syncApartmentVisibility);

            let objectMap = null;
            let objectMapMarker = null;
            const defaultMapCenter = [48.0196, 66.9237]; // Kazakhstan center
            const defaultMapZoom = 5;

            function ensureObjectMap() {
                if (objectMap || typeof L === 'undefined') return;
                const mapEl = document.getElementById('object-map');
                if (!mapEl) return;

                objectMap = L.map(mapEl).setView(defaultMapCenter, defaultMapZoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(objectMap);

                objectMap.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    if (!objectMapMarker) {
                        objectMapMarker = L.marker([lat, lng]).addTo(objectMap);
                    } else {
                        objectMapMarker.setLatLng([lat, lng]);
                    }
                    if (objectLatEl) objectLatEl.value = String(lat);
                    if (objectLngEl) objectLngEl.value = String(lng);
                    reverseGeocodeAndFillAddress(lat, lng).catch(() => {});
                    hideAddressSuggestions();
                });
            }

            function updateObjectMapMarker(lat, lng) {
                if (!objectMap) return;
                const hasPoint = Number.isFinite(lat) && Number.isFinite(lng);
                if (hasPoint) {
                    if (!objectMapMarker) {
                        objectMapMarker = L.marker([lat, lng]).addTo(objectMap);
                    } else {
                        objectMapMarker.setLatLng([lat, lng]);
                    }
                    objectMap.setView([lat, lng], 15);
                } else if (objectMapMarker) {
                    objectMap.removeLayer(objectMapMarker);
                    objectMapMarker = null;
                    objectMap.setView(defaultMapCenter, defaultMapZoom);
                }
            }

            function hideAddressSuggestions() {
                if (!objectSuggestListEl) return;
                objectSuggestListEl.classList.add('hidden');
                objectSuggestListEl.innerHTML = '';
            }

            /** true пока адрес выставлен из карты/подсказки (не ручной ввод). */
            let addressFieldInternalUpdate = false;

            function setAddressValue(v) {
                if (!objectAddressEl) return;
                addressFieldInternalUpdate = true;
                objectAddressEl.value = String(v || '').slice(0, 255);
                queueMicrotask(() => {
                    addressFieldInternalUpdate = false;
                });
            }

            function clearMapCoords() {
                if (objectLatEl) objectLatEl.value = '';
                if (objectLngEl) objectLngEl.value = '';
                updateObjectMapMarker(NaN, NaN);
            }

            function applyAddressPickFromGeocoder(lat, lon, displayName) {
                ensureObjectMap();
                setAddressValue(displayName);
                if (objectLatEl) objectLatEl.value = Number.isFinite(lat) ? String(lat) : '';
                if (objectLngEl) objectLngEl.value = Number.isFinite(lon) ? String(lon) : '';
                hideAddressSuggestions();
                setTimeout(() => {
                    if (objectMap) objectMap.invalidateSize();
                    updateObjectMapMarker(lat, lon);
                }, 100);
            }

            let lastAddressSuggestionRows = [];
            let addressSearchTimer = null;
            let addressSearchAbort = null;
            let reverseGeocodeAbort = null;

            async function searchAddressSuggestions(query) {
                if (!objectSuggestListEl) return;
                if (addressSearchAbort) addressSearchAbort.abort();
                addressSearchAbort = new AbortController();

                const cityPart = objectCityEl?.value ? `, ${objectCityEl.value}` : '';
                const q = `${query}${cityPart}, Kazakhstan`;
                const url =
                    `https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6&countrycodes=kz&q=${encodeURIComponent(q)}`;
                const r = await fetch(url, {
                    signal: addressSearchAbort.signal,
                    headers: {
                        'Accept': 'application/json'
                    },
                });
                const rows = await r.json().catch(() => []);
                if (!Array.isArray(rows) || !rows.length) {
                    hideAddressSuggestions();
                    return;
                }

                lastAddressSuggestionRows = rows;
                objectSuggestListEl.innerHTML = rows.map((row, idx) => {
                    const label = escapeHtml(String(row.display_name || row.name || '').slice(0, 255));
                    return `<button type="button" class="address-suggest-item" data-idx="${idx}">${label}</button>`;
                }).join('');
                objectSuggestListEl.classList.remove('hidden');

                objectSuggestListEl.querySelectorAll('.address-suggest-item').forEach(btn => {
                    btn.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        const idx = parseInt(this.dataset.idx, 10);
                        const row = lastAddressSuggestionRows[idx];
                        if (!row) return;
                        const lat = parseFloat(row.lat);
                        const lon = parseFloat(row.lon);
                        const titleRaw = String(row.display_name || row.name || '').slice(0,
                            255);
                        applyAddressPickFromGeocoder(lat, lon, titleRaw);
                    });
                });
            }

            async function reverseGeocodeAndFillAddress(lat, lng) {
                if (reverseGeocodeAbort) reverseGeocodeAbort.abort();
                reverseGeocodeAbort = new AbortController();
                const url =
                    `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(String(lat))}&lon=${encodeURIComponent(String(lng))}&zoom=18&addressdetails=1`;
                const r = await fetch(url, {
                    signal: reverseGeocodeAbort.signal,
                    headers: {
                        'Accept': 'application/json'
                    },
                });
                const data = await r.json().catch(() => ({}));
                if (data?.display_name) setAddressValue(data.display_name);
            }

            const _shortEntrance = '{{ __('objects.short_entrance') }}';
            const _shortApt = '{{ __('objects.short_apt') }}';
            const _shortFloor = '{{ __('objects.short_floor') }}';

            function buildFullAddress(obj) {
                let addr = obj.address || '';
                if (obj.apartment_entrance) addr += `, ${_shortEntrance} ${obj.apartment_entrance}`;
                if (obj.apartment) addr += `, ${_shortApt} ${obj.apartment}`;
                if (obj.apartment_floor) addr += `, ${_shortFloor} ${obj.apartment_floor}`;
                if (obj.city) addr += `, ${obj.city}`;
                return addr;
            }

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value).replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c]));
            }

            function formatTenge(amount) {
                return `${new Intl.NumberFormat('kk-KZ').format(amount || 0)} ₸`;
            }

            const statusLabels = {
                new: '{{ __('objects.new') }}',
                in_work: '{{ __('objects.in_work') }}',
                not_working: '{{ __('objects.not_working') }}'
            };
            const statusBadgeClasses = {
                new: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
                in_work: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
                not_working: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'
            };

            function typeLabel(type) {
                if (type === 'apartment') return '{{ __('objects.apartment') }}';
                if (type === 'house') return '{{ __('objects.house') }}';
                if (type === 'commercial') return '{{ __('objects.commercial') }}';
                return '{{ __('objects.other') }}';
            }

            window.currentViewedObjectId = null;

            function objectToDataAttr(obj) {
                return JSON.stringify(obj).replace(/'/g, "&#39;");
            }

            function closeObjectModal() {
                const modal = document.getElementById('object-modal');
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function openObjectModal() {
                const modal = document.getElementById('object-modal');
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                ensureObjectMap();
                setTimeout(() => {
                    if (objectMap) objectMap.invalidateSize();
                }, 60);
            }

            // Inline handlers in HTML need global functions
            window.closeObjectModal = closeObjectModal;
            window.openObjectModal = openObjectModal;

            function closeViewObjectModal() {
                const modal = document.getElementById('view-object-modal');
                const panel = modal?.querySelector('div[class*="absolute"]');
                if (!modal) return;
                modal.classList.add('hidden');
                if (panel) {
                    panel.classList.add('translate-x-full');
                    panel.classList.remove('translate-x-0');
                }
                window.currentViewedObjectId = null;
            }

            window.closeViewObjectModal = closeViewObjectModal;

            window.viewObject = function(id) {
                const content = document.getElementById('view-object-content');
                const rows = document.querySelectorAll('tr[data-object], div[data-object]');
                let obj = null;
                rows.forEach(row => {
                    try {
                        const o = JSON.parse(row.getAttribute('data-object'));
                        if (o.id === id) obj = o;
                    } catch (_) {}
                });

                if (!obj) return;
                window.currentViewedObjectId = parseInt(id, 10);

                const statusText = statusLabels[obj.status] || obj.status;
                const filePaths = Array.isArray(obj.file_paths) ? obj.file_paths : [];

                const filesHtml = filePaths.length ? filePaths.map((p, idx) => {
                    const safeName = String(p.split('/').pop() || '').replace(/[&<>"']/g, (c) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [c]));
                    const url = `/storage/${p}`;
                    return `
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">${safeName}</span>
                            <div class="flex items-center gap-2">
                                <a href="${url}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] dark:hover:border-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                                   title="{{ __('objects.view') }}">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                   </svg>
                                   {{ __('objects.view') }}
                                </a>

                                <a href="${url}" download="${safeName}"
                                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                                   title="{{ __('objects.download') }}">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
                                   </svg>
                                   {{ __('objects.download') }}
                                </a>

                                <button type="button"
                                   onclick="window.deleteObjectFile(${obj.id}, ${idx})"
                                   class="inline-flex items-center justify-center p-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors"
                                   title="{{ __('objects.delete_file') }}">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                   </svg>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('') : '';

                content.innerHTML = `
                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <label class="modal-helper block mb-1">{{ __('objects.address') }}</label>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${escapeHtml(buildFullAddress(obj))}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                            <label class="modal-helper block mb-1">{{ __('objects.type') }}</label>
                            <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${escapeHtml(typeLabel(obj.type || 'other'))}</p>
                        </div>
                        <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                            <label class="modal-helper block mb-1">{{ __('objects.status') }}</label>
                            <span id="view-object-status-text"
                                  class="px-2 py-0.5 rounded text-xs font-medium ${statusBadgeClasses[obj.status] || statusBadgeClasses.new}">
                                ${escapeHtml(statusText)}
                            </span>
                        </div>
                    </div>

                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <label class="modal-helper block mb-1">{{ __('objects.client') }}</label>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${escapeHtml(obj.client_name || '')}</p>
                    </div>

                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <label class="modal-helper block mb-1">{{ __('objects.area') }}</label>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${parseFloat(obj.area || 0).toLocaleString('kk-KZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})} {{ __('objects.area_m2') }}</p>
                    </div>

                    <div>
                        <h3 class="modal-section-title mb-2">{{ __('objects.repair_budget') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                <p class="modal-helper mb-1">{{ __('objects.planned') }}</p>
                                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${formatTenge(obj.repair_budget_planned || 0)}</p>
                            </div>
                            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                <p class="modal-helper mb-1">{{ __('objects.actual') }}</p>
                                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.repair_budget_actual ? formatTenge(obj.repair_budget_actual) : '-'}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="modal-section-title mb-2">{{ __('objects.repair_budget_per_m2') }}</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                <p class="modal-helper mb-1">{{ __('objects.planned') }}</p>
                                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${formatTenge(obj.repair_budget_per_m2_planned || 0)}</p>
                            </div>
                            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                <p class="modal-helper mb-1">{{ __('objects.actual') }}</p>
                                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.repair_budget_per_m2_actual ? formatTenge(obj.repair_budget_per_m2_actual) : '-'}</p>
                            </div>
                        </div>
                    </div>

                    ${obj.links && obj.links.length ? `
                                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                    <label class="modal-helper block mb-2">{{ __('objects.links') }}</label>
                                    <div class="space-y-1">
                                        ${obj.links.map(l => `<a href="${escapeHtml(l)}" target="_blank" class="block text-[#f59e0b] hover:underline text-sm truncate">${escapeHtml(l)}</a>`).join('')}
                                    </div>
                                </div>
                            ` : ''}

                    ${obj.comment ? `
                                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                                    <label class="modal-helper block mb-1">{{ __('objects.comment') }}</label>
                                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(obj.comment)}</p>
                                </div>
                            ` : ''}

                    ${filesHtml ? `
                                <div class="pt-2">
                                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">{{ __('objects.files') }}</label>
                                    <div class="space-y-2">${filesHtml}</div>
                                </div>
                            ` : ''}

                    <div class="pt-3">
                        <a href="/objects/${obj.id}"
                           class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                           title="{{ __('objects.details') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('objects.details') }}
                        </a>
                    </div>
                `;

                const modal = document.getElementById('view-object-modal');
                const panel = modal.querySelector('div[class*="absolute"]');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('translate-x-full');
                    panel.classList.add('translate-x-0');
                }, 10);
            };

            window.deleteObjectFile = async function(objectId, fileIndex) {
                if (!confirm('{{ __('objects.delete_file_confirm') }}')) return;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                try {
                    const r = await fetch(`/objects/${objectId}/files/${fileIndex}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                        projectAlert('error', data.message || '{{ __('objects.error') }}', '', 3000);
                        return;
                    }
                    await window.refreshObjects?.();
                    window.currentViewedObjectId = parseInt(objectId, 10);
                    window.viewObject?.(parseInt(objectId, 10));
                    projectAlert('success', '{{ __('objects.delete_file') }}', '', 2000);
                } catch (e) {
                    console.error(e);
                    projectAlert('error', '{{ __('objects.error') }}', '', 3000);
                }
            };

            window.updateObjectStatus = async function(objectId, newStatus, updatedObject) {
                const numericId = parseInt(objectId, 10);
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                try {
                    const r = await fetch(`/objects/${numericId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            status: newStatus
                        })
                    });
                    const data = await r.json().catch(() => ({}));
                    if (r.ok && data.success) {
                        // обновим статус в открытой модалке
                        const modal = document.getElementById('view-object-modal');
                        const isModalOpen = modal && !modal.classList.contains('hidden');
                        if (isModalOpen && window.currentViewedObjectId === numericId) {
                            const el = document.getElementById('view-object-status-text');
                            if (el && data.object?.status) {
                                el.textContent = statusLabels[data.object.status] || data.object.status;
                                el.className = statusBadgeClasses[data.object.status] || el.className;
                            }
                        }

                        await window.refreshObjects?.();
                    } else {
                        await window.refreshObjects?.();
                    }
                } catch (e) {
                    console.error(e);
                    await window.refreshObjects?.();
                }
            };

            window.renderActiveTab = function() {
                const currentTab = document.querySelector('[data-tab].active')?.dataset.tab || 'table';
                if (currentTab === 'table') window.renderTable?.();
                if (currentTab === 'list') window.renderList?.();
                if (currentTab === 'funnel') window.renderFunnel?.();
            };

            function renderPagination(containerId, totalPages) {
                const container = document.getElementById(containerId);
                if (!container) return;

                const prevLabel = '{{ __('objects.prev') }}';
                const nextLabel = '{{ __('objects.next') }}';

                if (allObjects.length <= itemsPerPage) {
                    container.innerHTML = '';
                    return;
                }

                const pages = [];
                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) pages.push(i);
                } else {
                    pages.push(1);
                    const start = Math.max(2, currentPage - 1);
                    const end = Math.min(totalPages - 1, currentPage + 1);
                    if (start > 2) pages.push('...');
                    for (let i = start; i <= end; i++) pages.push(i);
                    if (end < totalPages - 1) pages.push('...');
                    pages.push(totalPages);
                }

                const prevDisabled = currentPage <= 1;
                const nextDisabled = currentPage >= totalPages;

                container.innerHTML = `
                    <button type="button" ${prevDisabled ? 'disabled' : ''} data-page="${currentPage - 1}" class="px-4">${prevLabel}</button>
                    ${pages.map(p => {
                        if (p === '...') return `<span class="px-2 text-[#64748b] dark:text-[#A1A09A] opacity-60">...</span>`;
                        const active = p === currentPage;
                        return `<button type="button" data-page="${p}" class="px-4 ${active ? 'active' : ''}" ${active ? 'disabled' : ''}>${p}</button>`;
                    }).join('')}
                    <button type="button" ${nextDisabled ? 'disabled' : ''} data-page="${currentPage + 1}" class="px-4">${nextLabel}</button>
                `;

                container.querySelectorAll('button[data-page]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        currentPage = parseInt(btn.dataset.page, 10);
                        window.renderActiveTab?.();
                    });
                });
            }

            function renderClientStatusBadge(obj) {
                const cls = statusBadgeClasses[obj.status] || statusBadgeClasses.new;
                const label = statusLabels[obj.status] || obj.status;
                return `<span class="${cls}">${escapeHtml(label)}</span>`;
            }

            function sortAllObjects() {
                if (!sortColumn) return;
                const dir = sortDirection === 'asc' ? 1 : -1;
                allObjects.sort((a, b) => {
                    let av = a[sortColumn];
                    let bv = b[sortColumn];
                    if (typeof av === 'string') av = av.toLowerCase();
                    if (typeof bv === 'string') bv = bv.toLowerCase();
                    if (av === bv) return 0;
                    return av > bv ? dir : -dir;
                });
            }

            function updateSortHeaders() {
                document.querySelectorAll('.sortable-header').forEach(header => {
                    header.classList.remove('asc', 'desc');
                    if (header.dataset.sort === sortColumn) header.classList.add(sortDirection);
                });
            }

            // Sorting headers
            document.querySelectorAll('.sortable-header').forEach(header => {
                header.addEventListener('click', () => {
                    if (sortColumn === header.dataset.sort) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortColumn = header.dataset.sort;
                        sortDirection = 'asc';
                    }
                    sortAllObjects();
                    currentPage = 1;
                    updateSortHeaders();
                    window.renderActiveTab?.();
                });
            });

            // Tabs
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove(
                        'active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.add(
                        'hidden'));

                    const tabView = document.getElementById(this.dataset.tab + '-view');
                    if (tabView) tabView.classList.remove('hidden');
                    window.renderActiveTab?.();
                });
            });

            // Debounced live search
            let searchTimer = null;
            const onSearchInput = () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => window.refreshObjects?.(), 350);
            };
            searchEl?.addEventListener('input', onSearchInput);
            objectAddressEl?.addEventListener('input', function() {
                if (!addressFieldInternalUpdate) {
                    clearMapCoords();
                }
                const q = this.value.trim();
                clearTimeout(addressSearchTimer);
                if (q.length < 3) {
                    hideAddressSuggestions();
                    return;
                }
                addressSearchTimer = setTimeout(() => {
                    searchAddressSuggestions(q).catch(() => hideAddressSuggestions());
                }, 300);
            });
            objectAddressEl?.addEventListener('blur', () => setTimeout(hideAddressSuggestions, 220));
            objectCityEl?.addEventListener('change', () => {
                hideAddressSuggestions();
                clearMapCoords();
            });
            document.addEventListener('click', function(e) {
                if (!objectSuggestListEl || !objectAddressEl) return;
                if (e.target === objectAddressEl || objectSuggestListEl.contains(e.target)) return;
                hideAddressSuggestions();
            });
            typeFilterEl?.addEventListener('change', () => window.refreshObjects?.());
            clientFilterEl?.addEventListener('change', () => window.refreshObjects?.());
            statusFilterEl?.addEventListener('change', () => window.refreshObjects?.());

            window.refreshObjects = async function() {
                currentPage = 1;

                const params = new URLSearchParams();
                if (searchEl) {
                    const v = searchEl.value.trim();
                    if (v !== '') params.set('search', v);
                }
                if (typeFilterEl) {
                    const v = typeFilterEl.value.trim();
                    if (v !== '') params.set('type', v);
                }
                if (clientFilterEl) {
                    const v = clientFilterEl.value.trim();
                    if (v !== '') params.set('client_id', v);
                }
                if (statusFilterEl) {
                    const v = statusFilterEl.value.trim();
                    if (v !== '') params.set('status', v);
                }

                const url = params.toString() ? `/objects/search?${params.toString()}` : `/objects/search`;
                const r = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const payload = await r.json().catch(() => ({
                    data: []
                }));
                window.allObjects = payload.data || [];
                allObjects = window.allObjects;

                window.renderActiveTab?.();
            };

            window.renderTable = function() {
                if (!tbody) return;
                if (!allObjects.length) {
                    tbody.innerHTML =
                        `<tr><td colspan="9" class="px-4 py-6 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.no_objects') }}</td></tr>`;
                    document.getElementById('objects-pagination-table').innerHTML = '';
                    return;
                }

                sortAllObjects();
                const totalPages = Math.max(1, Math.ceil(allObjects.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const paged = allObjects.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

                tbody.innerHTML = paged.map(obj => {
                    const dataJson = objectToDataAttr(obj);
                    const full_address = buildFullAddress(obj);


                    return `
                        <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-object-id="${obj.id}" data-object='${dataJson}'>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(full_address)}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(typeLabel(obj.type || 'other'))}</td>
                            <td class="px-4 py-3 text-sm">${renderClientStatusBadge(obj)}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(obj.client_name || '')}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${parseFloat(obj.area || 0).toLocaleString('kk-KZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(formatTenge(obj.repair_budget_planned || 0))}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(formatTenge(obj.repair_budget_per_m2_planned || 0))}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(obj.comment || '-')}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <button type="button" title="{{ __('objects.view') }}" onclick="window.viewObject(${obj.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="{{ __('objects.edit') }}" onclick="window.editObject(${obj.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="{{ __('objects.delete') }}" onclick="window.deleteObject(${obj.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                renderPagination('objects-pagination-table', totalPages);
            };

            window.renderList = function() {
                if (!listBody) return;
                if (!allObjects.length) {
                    listBody.innerHTML =
                        `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.no_objects') }}</div>`;
                    document.getElementById('objects-pagination-list').innerHTML = '';
                    return;
                }

                sortAllObjects();
                const totalPages = Math.max(1, Math.ceil(allObjects.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const paged = allObjects.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

                listBody.innerHTML = paged.map(obj => {
                    const dataJson = objectToDataAttr(obj);
                    return `
                        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6" data-object-id="${obj.id}" data-object='${dataJson}'>
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${escapeHtml(buildFullAddress(obj))}</h3>
                                    <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                        <p>${escapeHtml(obj.client_name || '')}</p>
                                        <p>${escapeHtml(typeLabel(obj.type || 'other'))}</p>
                                    </div>
                                </div>
                                <div>${renderClientStatusBadge(obj)}</div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm mb-4">
                                <div>
                                    <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.area') }}:</span>
                                    <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${parseFloat(obj.area || 0).toLocaleString('kk-KZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                </div>
                                <div>
                                    <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.planned') }}:</span>
                                    <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${escapeHtml(formatTenge(obj.repair_budget_planned || 0))}</span>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.files') }}:</span>
                                    <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${(obj.file_paths || []).length}</span>
                                </div>
                            </div>

                            ${obj.comment ? `<p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">${escapeHtml(obj.comment)}</p>` : ''}

                            <div class="flex gap-2">
                                <button type="button" onclick="window.viewObject(${obj.id})"
                                    class="filter-btn p-1.5 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button type="button" onclick="window.editObject(${obj.id})"
                                    class="filter-btn p-1.5 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button type="button" onclick="window.deleteObject(${obj.id})"
                                    class="filter-btn p-1.5 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                renderPagination('objects-pagination-list', totalPages);
            };

            window.renderFunnel = function() {
                const funnelNew = document.getElementById('funnel-new');
                const funnelInWork = document.getElementById('funnel-in-work');
                const funnelNotWorking = document.getElementById('funnel-not-working');
                if (!funnelNew || !funnelInWork || !funnelNotWorking) return;
                funnelNew.innerHTML = '';
                funnelInWork.innerHTML = '';
                funnelNotWorking.innerHTML = '';

                const grouped = {
                    new: [],
                    in_work: [],
                    not_working: []
                };
                allObjects.forEach(obj => {
                    if (grouped[obj.status]) grouped[obj.status].push(obj);
                });

                const renderCol = (status, container) => {
                    const start = (currentPage - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    const pageItems = grouped[status].slice(start, end);

                    container.innerHTML = pageItems.map(obj => `
                        <div class="funnel-card" draggable="true"
                             ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                             data-object-id="${obj.id}" data-object='${objectToDataAttr(obj)}'>
                            <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">${escapeHtml(buildFullAddress(obj))}</h4>
                            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">${escapeHtml(obj.client_name || '')}</p>
                            <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                <button type="button" onclick="event.stopPropagation(); window.viewObject(${obj.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] transition-colors">{{ __('objects.view') }}</button>
                                <button type="button" onclick="event.stopPropagation(); window.editObject(${obj.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] transition-colors">{{ __('objects.edit') }}</button>
                                <button type="button" onclick="event.stopPropagation(); window.deleteObject(${obj.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 transition-colors">{{ __('objects.delete') }}</button>
                            </div>
                        </div>
                    `).join('');
                };

                renderCol('new', funnelNew);
                renderCol('in_work', funnelInWork);
                renderCol('not_working', funnelNotWorking);
            };

            let draggedElement = null;

            function clearFunnelHighlights() {
                document.querySelectorAll('.funnel-column.drag-over').forEach(col => {
                    col.classList.remove('drag-over');
                });
            }

            // Убираем подсветку, когда курсор уходит из колонки
            document.querySelectorAll('.funnel-column').forEach(column => {
                column.addEventListener('dragleave', function(e) {
                    if (!column.contains(e.relatedTarget)) {
                        column.classList.remove('drag-over');
                    }
                });
            });

            window.allowDrop = function(ev) {
                ev.preventDefault();
                clearFunnelHighlights();
                ev.currentTarget.classList.add('drag-over');
            };
            window.drag = function(ev) {
                draggedElement = ev.target.closest('.funnel-card');
                if (draggedElement) {
                    draggedElement.classList.add('dragging');
                    ev.dataTransfer.effectAllowed = 'move';
                }
            };
            window.drop = function(ev) {
                ev.preventDefault();
                clearFunnelHighlights();
                if (!draggedElement) return;
                const newStatus = ev.currentTarget.dataset.status;
                const objectId = draggedElement.dataset.objectId;
                ev.currentTarget.querySelector('.funnel-cards')?.appendChild(draggedElement);

                let updatedObject = null;
                try {
                    updatedObject = JSON.parse(draggedElement.dataset.object || '{}');
                    updatedObject.status = newStatus;
                } catch (_) {}

                window.updateObjectStatus?.(objectId, newStatus, updatedObject);
                draggedElement.classList.remove('dragging');
                draggedElement = null;
            };

            // Form handlers
            document.getElementById('add-object-btn').addEventListener('click', function() {
                document.getElementById('object-modal-title').textContent =
                    '{{ __('objects.new_object') }}';
                document.getElementById('object_id').value = '';
                document.getElementById('object-form').reset();
                if (objectCityEl) objectCityEl.value = '';
                if (objectLatEl) objectLatEl.value = '';
                if (objectLngEl) objectLngEl.value = '';
                syncApartmentVisibility();
                updateObjectMapMarker(NaN, NaN);
                hideAddressSuggestions();
                openObjectModal();
            });

            window.editObject = function(id) {
                const rows = document.querySelectorAll('tr[data-object], div[data-object]');
                let obj = null;
                rows.forEach(row => {
                    try {
                        const o = JSON.parse(row.getAttribute('data-object'));
                        if (o.id === id) obj = o;
                    } catch (_) {}
                });
                if (!obj) return;

                document.getElementById('object-modal-title').textContent = '{{ __('objects.edit_object') }}';
                document.getElementById('object_id').value = obj.id;
                document.getElementById('object_client_id').value = obj.client_id;
                document.getElementById('object_city').value = obj.city || '';
                document.getElementById('object_address').value = obj.address || '';
                document.getElementById('object_apartment').value = obj.apartment || '';
                document.getElementById('object_apartment_floor').value = obj.apartment_floor || '';
                document.getElementById('object_apartment_entrance').value = obj.apartment_entrance || '';
                document.getElementById('object_type').value = obj.type || 'other';
                document.getElementById('object_status').value = obj.status || 'new';
                document.getElementById('object_area').value = obj.area || '';
                document.getElementById('repair_budget_planned').value = obj.repair_budget_planned ?? '';
                document.getElementById('repair_budget_actual').value = obj.repair_budget_actual ?? '';
                document.getElementById('repair_budget_per_m2_planned').value = obj
                    .repair_budget_per_m2_planned ?? '';
                document.getElementById('repair_budget_per_m2_actual').value = obj
                    .repair_budget_per_m2_actual ?? '';
                document.getElementById('object_comment').value = obj.comment || '';
                const links = Array.isArray(obj.links) ? obj.links : [];
                document.getElementById('object_links_text').value = links.join('\n');

                if (objectLatEl) objectLatEl.value = obj.latitude ?? '';
                if (objectLngEl) objectLngEl.value = obj.longitude ?? '';
                syncApartmentVisibility();
                hideAddressSuggestions();
                openObjectModal();
                updateObjectMapMarker(Number(obj.latitude), Number(obj.longitude));
            };

            window.deleteObject = async function(id) {
                if (!confirm('{{ __('objects.delete_confirm') }}')) return;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                try {
                    const r = await fetch(`/objects/delete/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    });
                    if (!r.ok) throw new Error('Delete failed');
                    await window.refreshObjects?.();
                    projectAlert('success', '{{ __('objects.object_deleted') }}', '', 2000);
                } catch (e) {
                    console.error(e);
                    projectAlert('error', '{{ __('objects.error') }}', '', 3000);
                }
            };

            document.getElementById('object-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                const form = e.target;
                const action = form.getAttribute('action');
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const lat = parseFloat(objectLatEl?.value || '');
                const lng = parseFloat(objectLngEl?.value || '');
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    projectAlert('error', '{{ __('objects.map_point_required') }}', '', 3500);
                    return;
                }
                try {
                    const r = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            ...(token ? {
                                'X-CSRF-TOKEN': token
                            } : {})
                        },
                        body: new FormData(form)
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.success) {
                        projectAlert('error', data.message || '{{ __('objects.error') }}', '', 3000);
                        return;
                    }

                    projectAlert('success', data.message || '{{ __('objects.object_updated') }}', '',
                        2500);
                    closeObjectModal();
                    form.reset();
                    document.getElementById('object_id').value = '';
                    if (objectLatEl) objectLatEl.value = '';
                    if (objectLngEl) objectLngEl.value = '';
                    syncApartmentVisibility();
                    updateObjectMapMarker(NaN, NaN);
                    await window.refreshObjects?.();
                } catch (err) {
                    console.error(err);
                    projectAlert('error', '{{ __('objects.error') }}', '', 3000);
                }
            });

            // Initial render
            syncApartmentVisibility();
            window.renderActiveTab();
        });
    </script>
@endsection
