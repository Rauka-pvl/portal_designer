@extends('layouts.dashboard')

@section('title', __('clients.my_clients'))

@push('styles')
    <style>
        .tab-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
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
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: move;
            transition: all 0.3s;
        }

        .funnel-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .funnel-card.dragging {
            opacity: 0.5;
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

        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }

        .sortable-header:hover {
            color: #f59e0b;
        }

        /* Стрелки сортировки (без эмодзи): треугольники через border */
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

        /* ↑ */
        .sortable-header::before {
            top: calc(50% - 8px);
            border-bottom: 6px solid currentColor;
        }

        /* ↓ */
        .sortable-header::after {
            top: calc(50% + 2px);
            border-top: 6px solid currentColor;
        }

        /* Asc: показываем только верхнюю */
        .sortable-header.asc::before {
            opacity: 1;
        }

        .sortable-header.asc::after {
            opacity: 0;
        }

        /* Desc: показываем только нижнюю */
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
            margin-top: 1.5rem;
            padding: 1rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
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
    </style>
@endpush

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('clients.my_clients') }}</h1>
        <button id="add-client-btn" class="add-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('clients.add_client') }}
        </button>
    </div>

    <!-- Вкладки -->
    <div class="mb-6 flex gap-2">
        <button data-tab="table" class="tab-btn active">{{ __('clients.table') }}</button>
        <button data-tab="list" class="tab-btn">{{ __('clients.list') }}</button>
        <button data-tab="funnel" class="tab-btn">{{ __('clients.funnel') }}</button>
    </div>

    <!-- Поиск и фильтры -->
    <div class="mb-6 flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" id="search-input" placeholder="{{ __('clients.search') }}"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
        </div>
        <div class="w-full md:w-48">
            <select id="status-filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('clients.all_statuses') }}</option>
                <option value="new">{{ __('clients.new') }}</option>
                <option value="in_work">{{ __('clients.in_work') }}</option>
                <option value="not_working">{{ __('clients.not_working') }}</option>
            </select>
        </div>


    </div>

    <!-- Контент вкладок -->
    <div id="table-view" class="tab-content">
        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="name">{{ __('clients.full_name') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="phone">{{ __('clients.phone') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="email">{{ __('clients.email') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="status">{{ __('clients.status') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="objects_count">{{ __('clients.objects_count') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="total_amount">{{ __('clients.total_amount') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('clients.links') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('clients.comment') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('clients.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="clients-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]">
                        @foreach ($clients as $client)
                            <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]"
                                data-client-id="{{ $client->id }}" data-client='@json($client)'>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $client->full_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $client->phone }}
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $client->email }}
                                </td>
                                <td class="px-4 py-3 text-sm" id="client-status-{{ $client->id }}">
                                    <span class="client-status-badge px-2 py-1 rounded text-xs font-medium
                                @if ($client->status === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200
                                @elseif($client->status === 'in_work')
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                                @else
                                    bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200 @endif">
                                        @if ($client->status === 'new')
                                            {{ __('clients.new') }}
                                        @elseif($client->status === 'in_work')
                                            {{ __('clients.in_work') }}
                                        @else
                                            {{ __('clients.not_working') }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $client->count_objects }}
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ number_format($client->sum_repair_budget_planned, 0, ',', ' ') }} ₸</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($client->link)
                                        <a href="{{ $client->link }}" target="_blank"
                                            class="text-[#f59e0b] hover:underline">
                                            {{ $client->link }}
                                        </a>
                                    @else
                                        <span class="text-[#64748b] dark:text-[#A1A09A]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $client->comment ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <button onclick="viewClient('{{ $client->id }}')"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('clients.view') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button onclick="editClient('{{ $client->id }}')"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('clients.edit') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="deleteClient('{{ $client->id }}')"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-500 dark:hover:text-red-400 transition-colors"
                                            title="{{ __('clients.delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <button onclick="addObject('{{ $client->id }}')"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('clients.add_object') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between px-4 py-2 gap-4">
                <div class="pagination" id="clients-pagination-table" style="margin-top:0;"></div>

                <div class="flex items-center justify-end gap-3">
                    <div class="text-sm text-[#64748b] dark:text-[#A1A09A] whitespace-nowrap">{{ __('clients.per_page') }}</div>

                    <div class="relative w-40">
                        <button id="clients-per-page-button" type="button"
                            class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                            <span id="clients-per-page-label">10</span>
                            <svg class="w-4 h-4 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="clients-per-page-menu"
                            class="hidden absolute left-0 right-0 mt-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg overflow-hidden z-[60]">
                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left clients-per-page-option"
                                data-value="10">
                                <span class="clients-per-page-check hidden mr-2 items-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </span>
                                10
                            </button>

                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left clients-per-page-option"
                                data-value="30">
                                <span class="clients-per-page-check hidden mr-2 items-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </span>
                                30
                            </button>

                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left clients-per-page-option"
                                data-value="50">
                                <span class="clients-per-page-check hidden mr-2 items-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </span>
                                50
                            </button>

                            <button type="button"
                                class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left clients-per-page-option"
                                data-value="100">
                                <span class="clients-per-page-check hidden mr-2 items-center">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg>
                                </span>
                                100
                            </button>
                        </div>

                        <select id="clients-per-page" class="hidden">
                            <option value="10" selected>10</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="list-view" class="tab-content hidden">
        <div class="space-y-4" id="clients-list-body">
            @foreach ($clients as $client)
                <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6"
                    data-client-id="{{ $client->id }}" data-client='@json($client)'>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">
                                {{ $client->full_name }}
                            </h3>
                            <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                <p>{{ $client->phone }}</p>
                                <p>{{ $client->email }}</p>
                            </div>
                        </div>
                        <div id="client-list-status-{{ $client->id }}">
                            <span class="client-status-badge px-2 py-1 rounded text-xs font-medium
        @if ($client->status === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200
        @elseif($client->status === 'in_work') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
        @else bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200 @endif">
                                @if ($client->status === 'new')
                                    {{ __('clients.new') }}
                                @elseif($client->status === 'in_work')
                                    {{ __('clients.in_work') }}
                                @else
                                    {{ __('clients.not_working') }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.objects_count') }}:</span>
                            <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ $client->count_objects }}</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.total_amount') }}:</span>
                            <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ number_format($client->sum_repair_budget_planned, 0, ',', ' ') }}
                                ₸</span>
                        </div>
                    </div>
                    @if ($client->comment)
                        <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ $client->comment }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        <button onclick="viewClient('{{ $client->id }}')"
                            class="filter-btn">{{ __('clients.view') }}</button>
                        <button onclick="editClient('{{ $client->id }}')"
                            class="filter-btn">{{ __('clients.edit') }}</button>
                        <button type="button" onclick="deleteClient('{{ $client->id }}')"
                            class="filter-btn text-red-500 hover:text-red-600">{{ __('clients.delete') }}</button>
                        <button onclick="addObject('{{ $client->id }}')"
                            class="filter-btn">{{ __('clients.add_object') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pagination" id="clients-pagination-list"></div>
    </div>

    <div id="funnel-view" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="funnel-column" data-status="new" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('clients.new') }}</h3>
                <div id="funnel-new" class="funnel-cards">
                    @foreach ($clients as $client)
                        @if ($client->status === 'new')
                            <div class="funnel-card" id="client-{{ $client->id }}" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-client-id="{{ $client->id }}" data-client='@json($client)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $client->full_name }}</h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $client->phone }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.edit') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); deleteClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('clients.delete') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="funnel-column" data-status="in_work" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('clients.in_work') }}</h3>
                <div id="funnel-in-work" class="funnel-cards">
                    @foreach ($clients as $client)
                        @if ($client->status === 'in_work')
                            <div class="funnel-card" id="client-{{ $client->id }}" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-client-id="{{ $client->id }}" data-client='@json($client)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $client->full_name }}</h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $client->phone }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.edit') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); deleteClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('clients.delete') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="funnel-column" data-status="not_working" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('clients.not_working') }}
                </h3>
                <div id="funnel-not-working" class="funnel-cards">
                    @foreach ($clients as $client)
                        @if ($client->status === 'not_working')
                            <div class="funnel-card" id="client-{{ $client->id }}" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-client-id="{{ $client->id }}" data-client='@json($client)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $client->full_name }}</h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $client->phone }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('clients.edit') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); deleteClient('{{ $client->id }}')"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('clients.delete') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка просмотра клиента (справа) -->
    <div id="view-client-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay"
        onmousedown="if(event.target === this) closeViewClientModal()">
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#e2e8f0] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content"
            onclick="event.stopPropagation()">
            <div class="flex flex-col h-full">
                <div
                    class="flex items-center justify-between px-6 py-5 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('clients.view') }}</h2>
                        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('clients.view') }}
                            {{ __('clients.client') }}</p>
                    </div>
                    <button onclick="closeViewClientModal()"
                        class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-client-content" class="flex-1 overflow-y-auto p-6 space-y-5"></div>
            </div>
        </div>
    </div>

    <!-- Модалка добавления/редактирования клиента -->
    <div id="client-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeClientModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] shrink-0">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="client-modal-title">
                        {{ __('clients.add_client') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('clients.add_client') }} —
                        {{ __('clients.my_clients') }}</p>
                </div>
                <button type="button" onclick="closeClientModal()"
                    class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="client-form" method="POST" action="{{ route('clients.add_client') }}"
                enctype="multipart/form-data" class="flex flex-col flex-1 min-h-0">
                @csrf
                <input type="hidden" name="client_id" id="client_id">
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.client_type') }}</label>
                            <select name="client_type" id="client_type" required class="modal-input">
                                <option value="person" selected>{{ __('clients.person') }}</option>
                                <option value="company">{{ __('clients.company') }}</option>
                            </select>
                            @error('client_type')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror

                            <label class="modal-label modal-label-required mt-4" id="client-full-name-label">{{ __('clients.fio') }}</label>
                            <input type="text" placeholder="Иван Иванов" name="full_name" id="client_name" required
                                class="modal-input">
                            @error('full_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.phone') }}</label>
                            <input type="tel" placeholder="+7 (700) 000-00-00" name="phone" id="client_phone"
                                required class="modal-input">
                            @error('phone')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('clients.email') }}</label>
                        <input type="email" placeholder="example@mail.com" name="email" id="client_email" required
                            class="modal-input">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('clients.status') }}</label>
                        <select name="status" id="client_status" required class="modal-input">
                            <option id="new" value="new">{{ __('clients.new') }}</option>
                            <option id="in_work" value="in_work">{{ __('clients.in_work') }}</option>
                            <option id="not_working" value="not_working">{{ __('clients.not_working') }}</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.comment') }}</label>
                        <textarea name="comment" placeholder="{{ __('clients.comment_placeholder') }}" id="client_comment" rows="3"
                            class="modal-input resize-none"></textarea>
                        @error('comment')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.link') }}</label>
                        <input type="url" placeholder="https://..." name="link" id="client_link"
                            class="modal-input">
                        @error('link')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.files') }}</label>
                        <input type="file" name="files[]" multiple
                            class="modal-input file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                        @error('file')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">{{ __('clients.save') }}</button>
                    <button type="button" onclick="closeClientModal()"
                        class="btn-secondary">{{ __('clients.cancel') }}</button>
                </div>
            </form>

        </div>
    </div>



    {{-- <!-- Модалка добавления объекта -->
    <div id="object-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeObjectModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] shrink-0">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('clients.add_object') }}
                    </h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('clients.add_object') }}
                        {{ __('clients.client') }}</p>
                </div>
                <button type="button" onclick="closeObjectModal()"
                    class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="object-form" class="flex flex-col flex-1 min-h-0" method="POST" action="{{ Route::has('objects.add_object') ? route('objects.add_object') : '#' }}" enctype="multipart/form-data">
                @csrf
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.client') }}</label>
                            <select name="client_id" required class="modal-input">
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.property_type') }}</label>
                            <select name="property_type" required class="modal-input">
                                <option value="apartment">{{ __('clients.apartment') }}</option>
                                <option value="house">{{ __('clients.house') }}</option>
                                <option value="commercial">{{ __('clients.commercial') }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('clients.object_address') }}</label>
                        <input type="text" name="address" required class="modal-input">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.object_status') }}</label>
                            <select name="object_status" required class="modal-input">
                                <option value="new">{{ __('clients.new') }}</option>
                                <option value="in_work">{{ __('clients.in_work') }}</option>
                                <option value="not_working">{{ __('clients.not_working') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('clients.object_area') }}</label>
                            <input type="number" name="area" step="0.01" required class="modal-input">
                        </div>
                    </div>
                    <div>
                        <h3 class="modal-section-title">{{ __('clients.repair_budget') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <input type="number" name="planned_cost" step="0.01"
                                    placeholder="{{ __('clients.planned_cost') }}" class="modal-input">
                            </div>
                            <div>
                                <input type="number" name="actual_cost" step="0.01"
                                    placeholder="{{ __('clients.actual_cost') }}" class="modal-input">
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="modal-section-title">{{ __('clients.repair_budget_per_m2') }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                            <div>
                                <input type="number" name="planned_cost_per_m2" step="0.01"
                                    placeholder="{{ __('clients.planned_cost') }}" class="modal-input">
                            </div>
                            <div>
                                <input type="number" name="actual_cost_per_m2" step="0.01"
                                    placeholder="{{ __('clients.actual_cost') }}" class="modal-input">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.links') }}</label>
                        <div id="links-container" class="space-y-2">
                            <input type="url" name="links[]" placeholder="{{ __('clients.add_link') }}"
                                class="modal-input">
                        </div>
                        <button type="button" onclick="addLinkField()"
                            class="mt-2 text-sm font-medium modal-accent-link flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('clients.add_link') }}
                        </button>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.files') }}</label>
                        <input type="file" name="files[]" multiple
                            class="modal-input file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                    </div>
                    <div>
                        <label class="modal-label">{{ __('clients.comment') }}</label>
                        <textarea name="comment" rows="3" class="modal-input resize-none"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">{{ __('clients.save') }}</button>
                    <button type="button" onclick="closeObjectModal()"
                        class="btn-secondary">{{ __('clients.cancel') }}</button>
                </div>
            </form>
        </div>
    </div> --}}

    <!-- Модалка добавления/редактирования объекта -->
    <div id="object-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeObjectModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] flex-shrink-0">
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
            <form id="object-form" class="flex flex-col flex-1 min-h-0" method="POST"
                action="{{ Route::has('objects.add_object') ? route('objects.add_object') : '#' }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="object_form_method" value="">
                <input type="hidden" name="object_id" id="object_id" value="">
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.select_client') }}</label>
                            <select name="client_id" required class="modal-input">
                                <option value="">{{ __('objects.select_client_placeholder') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name }}</option>
                                @endforeach
                            </select>
                            {{-- <input type="text" name="address" required value="{{ $client }}"
                                class="modal-input"> --}}
                            <p class="modal-helper">{{ __('objects.create_client_helper') }} <a
                                    href="{{ route('clients.index') }}"
                                    class="modal-accent-link">{{ __('objects.create_client') }}</a></p>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.property_type') }}</label>
                            <select name="type" required class="modal-input">
                                <option value="" disabled selected>{{ __('objects.select_type_placeholder') }}
                                </option>
                                <option value="apartment">{{ __('objects.apartment') }}</option>
                                <option value="house">{{ __('objects.house') }}</option>
                                <option value="commercial">{{ __('objects.commercial') }}</option>
                                <option value="other">{{ __('objects.other') }}</option>
                            </select>
                            <p class="modal-helper">{{ __('objects.select_type_helper') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label modal-label-required">{{ __('objects.address') }}</label>
                        <input type="text" name="address" required
                            placeholder="{{ __('objects.address_placeholder') }}" class="modal-input">
                        <p class="modal-helper">{{ __('objects.address_helper') }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.status') }}</label>
                            <select name="status" required class="modal-input">
                                <option value="">{{ __('objects.select_status_placeholder') }}</option>
                                <option value="new">{{ __('objects.new') }}</option>
                                <option value="in_work">{{ __('objects.in_work') }}</option>
                                <option value="not_working">{{ __('objects.not_working') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="modal-label modal-label-required">{{ __('objects.area') }},
                                {{ __('objects.area_m2') }}</label>
                            <input type="number" name="area" step="0.01" required
                                placeholder="{{ __('objects.area_placeholder') }}" class="modal-input">
                        </div>
                    </div>
                    <div>
                        <h3 class="modal-section-title">{{ __('objects.repair_budget') }}</h3>
                        <p class="modal-section-subtitle">{{ __('objects.budget_subtitle') }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <input type="number" name="repair_budget_planned" step="0.01"
                                    placeholder="{{ __('objects.planned') }}" class="modal-input">
                                <p class="modal-helper">{{ __('objects.planned_example') }}</p>
                            </div>
                            <div>
                                <input type="number" name="repair_budget_actual" step="0.01"
                                    placeholder="{{ __('objects.actual') }}" class="modal-input">
                                <p class="modal-helper">{{ __('objects.actual_example') }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="modal-section-title">{{ __('objects.repair_budget_per_m2') }}</h3>
                        <p class="modal-section-subtitle">{{ __('objects.budget_per_m2_subtitle') }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <input type="number" name="repair_budget_per_m2_planned" step="0.01"
                                    placeholder="{{ __('objects.planned') }}" class="modal-input">
                                <p class="modal-helper">{{ __('objects.per_m2_example') }}</p>
                            </div>
                            <div>
                                <input type="number" name="repair_budget_per_m2_actual" step="0.01"
                                    placeholder="{{ __('objects.actual') }}" class="modal-input">
                                <p class="modal-helper">{{ __('objects.per_m2_example') }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('objects.links') }}</label>
                        <p class="modal-section-subtitle">{{ __('objects.links_subtitle') }}</p>
                        <div id="links-container" class="space-y-2">
                            <div class="flex gap-2">
                                <div class="input-with-icon flex-1">
                                    <span class="input-icon text-[#64748b] dark:text-[#A1A09A]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </span>
                                    <input type="url" name="links[]" placeholder="{{ __('objects.paste_link') }}"
                                        class="modal-input">
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addLinkField()"
                            class="mt-2 text-sm font-medium modal-accent-link flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('objects.add_link') }}
                        </button>
                    </div>
                    <div>
                        <label class="modal-label">{{ __('objects.files') }}</label>
                        <input type="file" name="file"
                            class="modal-input file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b] hover:file:bg-[#f59e0b]/20">
                    </div>
                    <div>
                        <label class="modal-label">{{ __('objects.comment') }}</label>
                        <textarea name="comment" rows="3" placeholder="{{ __('objects.comment') }}" class="modal-input resize-none"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">{{ __('objects.save') }}</button>
                    <button type="button" onclick="closeObjectModal()"
                        class="btn-secondary">{{ __('objects.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentPage = 1;
            let itemsPerPage = 10;
            let sortColumn = null;
            let sortDirection = 'asc';
            window.allClients = @json($clients);
            let allClients = window.allClients;

            const perPageSelect = document.getElementById('clients-per-page');
            const perPageButton = document.getElementById('clients-per-page-button');
            const perPageLabel = document.getElementById('clients-per-page-label');
            const perPageMenu = document.getElementById('clients-per-page-menu');

            if (perPageSelect) {
                itemsPerPage = parseInt(perPageSelect.value, 10) || 10;
            }

            if (perPageLabel) {
                perPageLabel.textContent = String(itemsPerPage);
            }

            IMask(
                document.getElementById('client_phone'), {
                    mask: '+{7} (000) 000-00-00'
                }
            );

            // Подпись к полю `full_name` зависит от типа клиента (ФИО / Название компании).
            const clientTypeSelect = document.getElementById('client_type');
            const clientFullNameLabel = document.getElementById('client-full-name-label');

            function updateClientFullNameLabel() {
                if (!clientTypeSelect || !clientFullNameLabel) return;
                const type = clientTypeSelect.value;
                clientFullNameLabel.textContent = type === 'company'
                    ? '{{ __('clients.company_name') }}'
                    : '{{ __('clients.fio') }}';
            }

            if (clientTypeSelect && clientFullNameLabel) {
                clientTypeSelect.addEventListener('change', updateClientFullNameLabel);
                updateClientFullNameLabel();
            }

            // Переключение вкладок
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tab = this.dataset.tab;

                    // Обновляем кнопки
                    document.querySelectorAll('[data-tab]').forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Показываем нужный контент
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(tab + '-view').classList.remove('hidden');

                    if (tab === 'table') {
                        window.renderTable?.();
                    } else if (tab === 'list') {
                        window.renderList?.();
                    } else if (tab === 'funnel') {
                        window.renderFunnel?.();
                    }
                });
            });

            window.updateFileName = function(input) {
                const fileName = input.files[0] ? input.files[0].name : "Выберите файл...";
                document.getElementById('file-name').textContent = fileName;
            }

            // Сортировка
            function sortClients(column) {
                if (sortColumn === column) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortColumn = column;
                    sortDirection = 'asc';
                }

                allClients.sort((a, b) => {
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
                updateSortHeaders();
                window.renderAll?.();
            }

            function updateSortHeaders() {
                document.querySelectorAll('.sortable-header').forEach(header => {
                    header.classList.remove('asc', 'desc');
                    if (header.dataset.sort === sortColumn) {
                        header.classList.add(sortDirection);
                    }
                });
            }


            // Получение отфильтрованных клиентов
            function getFilteredClients() {
                const search = document.getElementById('search-input').value.toLowerCase();
                const status = document.getElementById('status-filter').value;

                return allClients.filter(client => {
                    const searchStr = Object.values(client).join(' ').toLowerCase();
                    const matchSearch = !search || searchStr.includes(search);
                    const matchStatus = !status || client.status === status;
                    return matchSearch && matchStatus;
                });
            }

            // Обработчики событий
            document.querySelectorAll('.sortable-header').forEach(header => {
                header.addEventListener('click', () => {
                    sortClients(header.dataset.sort);
                });
            });

            // Открытие модалки добавления клиента
            document.getElementById('add-client-btn').addEventListener('click', function() {
                document.getElementById('client-modal').classList.remove('hidden');
                document.getElementById('client-modal').classList.add('flex');
                document.getElementById('client-form').reset();
                document.getElementById('client_id').value = '';
                document.getElementById('client-modal-title').textContent =
                    '{{ __('clients.add_client') }}';
                if (typeof updateClientFullNameLabel === 'function') {
                    updateClientFullNameLabel();
                }
            });


            // Фильтр по статусу
            document.getElementById('status-filter').addEventListener('change', function() {
                window.refreshClients?.();
            });

            // Кастомный dropdown количества клиентов
            if (perPageButton && perPageMenu) {
                const setActivePerPage = (value) => {
                    perPageMenu.querySelectorAll('.clients-per-page-option').forEach(btn => {
                        const isActive = parseInt(btn.dataset.value, 10) === value;
                        btn.classList.toggle('bg-[#fef3c7]', isActive);
                        btn.classList.toggle('dark:bg-[#1D0002]', isActive);
                        btn.classList.toggle('text-[#f59e0b]', isActive);
                        btn.classList.toggle('dark:text-[#f59e0b]', isActive);

                        const check = btn.querySelector('.clients-per-page-check');
                        if (check) {
                            check.classList.toggle('hidden', !isActive);
                            check.classList.toggle('inline-flex', isActive);
                        }
                    });
                };

                // initial active state
                setActivePerPage(itemsPerPage);

                const toggleMenu = () => perPageMenu.classList.toggle('hidden');

                perPageButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleMenu();
                });

                document.addEventListener('click', function() {
                    if (!perPageMenu.classList.contains('hidden')) perPageMenu.classList.add('hidden');
                });

                perPageMenu.querySelectorAll('.clients-per-page-option').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const value = parseInt(this.dataset.value, 10);
                        if (!value) return;

                        itemsPerPage = value;
                        currentPage = 1;
                        if (perPageSelect) perPageSelect.value = String(value);
                        if (perPageLabel) perPageLabel.textContent = String(value);

                        setActivePerPage(value);
                        perPageMenu.classList.add('hidden');
                        window.renderAll?.();
                    });
                });
            }

            // Живой поиск
            let searchDebounceTimer = null;
            document.getElementById('search-input').addEventListener('input', function() {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => window.refreshClients?.(), 350);
            });

            // Рендеринг воронки
            window.renderFunnel = function() {
                // Очищаем все колонки
                document.getElementById('funnel-new').innerHTML = '';
                document.getElementById('funnel-in-work').innerHTML = '';
                document.getElementById('funnel-not-working').innerHTML = '';

                // Группируем клиентов по статусу
                const clientsByStatus = {
                    new: [],
                    in_work: [],
                    not_working: []
                };

                allClients.forEach(client => {
                    if (clientsByStatus[client.status]) {
                        clientsByStatus[client.status].push(client);
                    }
                });

                // Рендерим карточки в соответствующие колонки
                const viewLabel = '{{ __('clients.view') }}';
                const editLabel = '{{ __('clients.edit') }}';
                const deleteLabel = '{{ __('clients.delete') }}';
                Object.keys(clientsByStatus).forEach(status => {
                    const container = document.getElementById(`funnel-${status.replace('_', '-')}`);
                    const start = (currentPage - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    const pageClients = clientsByStatus[status].slice(start, end);
                    container.innerHTML = pageClients.map(client => `
                        <div class="funnel-card" draggable="true"
                            ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                            data-client-id="${client.id}" data-client='${JSON.stringify(client).replace(/'/g, "&#39;")}'>
                            <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1 flex items-center gap-2">
                                <span>${escapeHtml(client.full_name || '')}</span>
                                ${renderClientTypeBadge(client)}
                            </h4>
                            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">${client.phone || ''}</p>
                            <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                <button type="button" onclick="event.stopPropagation(); viewClient(${client.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">${viewLabel}</button>
                                <button type="button" onclick="event.stopPropagation(); editClient(${client.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">${editLabel}</button>
                                <button type="button" onclick="event.stopPropagation(); deleteClient(${client.id})"
                                    class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:border-red-500 hover:text-red-600 transition-colors">${deleteLabel}</button>
                            </div>
                        </div>
                    `).join('');
                });
            }

            // Обработка формы клиента
            const clientStatusBadgeClasses = {
                new: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
                in_work: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
                not_working: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'
            };

            const clientStatusLabels = {
                new: '{{ __('clients.new') }}',
                in_work: '{{ __('clients.in_work') }}',
                not_working: '{{ __('clients.not_working') }}'
            };

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value).replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[c]));
            }

            function renderClientStatusBadge(client) {
                const status = client.status || 'new';
                const cls = clientStatusBadgeClasses[status] || clientStatusBadgeClasses.new;
                const label = clientStatusLabels[status] || status;
                return `<span class="${cls}">${escapeHtml(label)}</span>`;
            }

            function renderClientTypeBadge(client) {
                if ((client.client_type || 'person') !== 'company') return '';
                const cls = 'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-[#f59e0b]/15 text-[#f59e0b] dark:bg-[#f59e0b]/10 dark:text-[#f59e0b] border border-[#f59e0b]/30 dark:border-[#f59e0b]/20';
                const label = '{{ __('clients.legal_entity') }}';
                return `<span class="${cls}">${escapeHtml(label)}</span>`;
            }

            function clientToDataAttr(client) {
                // Важно: JSON кладём в атрибут data-*, поэтому аккуратно экранируем одинарные кавычки.
                return JSON.stringify(client).replace(/'/g, "&#39;");
            }

            function formatTenge(amount) {
                return `${new Intl.NumberFormat('kk-KZ').format(amount || 0)} ₸`;
            }

            window.renderTable = function() {
                const tbody = document.getElementById('clients-table-body');
                const viewLabel = '{{ __('clients.view') }}';
                const editLabel = '{{ __('clients.edit') }}';
                const deleteLabel = '{{ __('clients.delete') }}';
                const addObjectLabel = '{{ __('clients.add_object') }}';

                if (!tbody) return;

                if (!allClients.length) {
                    tbody.innerHTML = `<tr><td colspan="9" class="px-4 py-6 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.no_clients') }}</td></tr>`;
                    const p = document.getElementById('clients-pagination-table');
                    if (p) p.innerHTML = '';
                    return;
                }

                const totalPages = Math.max(1, Math.ceil(allClients.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const pagedClients = allClients.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

                tbody.innerHTML = pagedClients.map(client => {
                    const dataJson = clientToDataAttr(client);
                    const linkHtml = client.link
                        ? `<a href="${escapeHtml(client.link)}" target="_blank" class="text-[#f59e0b] hover:underline">${escapeHtml(client.link)}</a>`
                        : `<span class="text-[#64748b] dark:text-[#A1A09A]">-</span>`;
                    const commentHtml = client.comment ? escapeHtml(client.comment) : '-';
                    const typeBadgeHtml = renderClientTypeBadge(client);

                    return `
                        <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]"
                            data-client-id="${client.id}" data-client='${dataJson}'>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                <div class="flex items-center gap-2">
                                    <span>${escapeHtml(client.full_name || '')}</span>
                                    ${typeBadgeHtml}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(client.phone || '')}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(client.email || '')}</td>
                            <td class="px-4 py-3 text-sm" id="client-status-${client.id}">${renderClientStatusBadge(client)}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(client.count_objects || 0)}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(formatTenge(client.sum_repair_budget_planned || 0))}</td>
                            <td class="px-4 py-3 text-sm">${linkHtml}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${commentHtml}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <button type="button" title="${escapeHtml(viewLabel)}" onclick="viewClient(${client.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="${escapeHtml(editLabel)}" onclick="editClient(${client.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="${escapeHtml(deleteLabel)}" onclick="deleteClient(${client.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <button type="button" title="${escapeHtml(addObjectLabel)}" onclick="addObject(${client.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] dark:hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                renderPagination('clients-pagination-table', totalPages);
            };

            window.renderList = function() {
                const listBody = document.getElementById('clients-list-body');
                const viewLabel = '{{ __('clients.view') }}';
                const editLabel = '{{ __('clients.edit') }}';
                const deleteLabel = '{{ __('clients.delete') }}';
                const addObjectLabel = '{{ __('clients.add_object') }}';

                if (!listBody) return;

                if (!allClients.length) {
                    listBody.innerHTML = `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.no_clients') }}</div>`;
                    const p = document.getElementById('clients-pagination-list');
                    if (p) p.innerHTML = '';
                    return;
                }

                const totalPages = Math.max(1, Math.ceil(allClients.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const pagedClients = allClients.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

                listBody.innerHTML = pagedClients.map(client => {
                    const dataJson = clientToDataAttr(client);
                    const linkHtml = client.link ? `<a href="${escapeHtml(client.link)}" target="_blank" class="text-[#f59e0b] hover:underline">${escapeHtml(client.link)}</a>` : '-';
                    const commentHtml = client.comment ? `<p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">${escapeHtml(client.comment)}</p>` : '';

                    return `
                        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6"
                            data-client-id="${client.id}" data-client='${dataJson}'>
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">
                                        <span>${escapeHtml(client.full_name || '')}</span>
                                        ${renderClientTypeBadge(client)}
                                    </h3>
                                    <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                        <p>${escapeHtml(client.phone || '')}</p>
                                        <p>${escapeHtml(client.email || '')}</p>
                                    </div>
                                </div>
                                <div id="client-list-status-${client.id}">
                                    ${renderClientStatusBadge(client)}
                                </div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                                <div>
                                    <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.objects_count') }}:</span>
                                    <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${escapeHtml(client.count_objects || 0)}</span>
                                </div>
                                <div>
                                    <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('clients.total_amount') }}:</span>
                                    <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${escapeHtml(formatTenge(client.sum_repair_budget_planned || 0))}</span>
                                </div>
                            </div>
                            ${commentHtml}
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="viewClient(${client.id})" class="filter-btn">${escapeHtml(viewLabel)}</button>
                                <button type="button" onclick="editClient(${client.id})" class="filter-btn">${escapeHtml(editLabel)}</button>
                                <button type="button" onclick="deleteClient(${client.id})" class="filter-btn text-red-500 hover:text-red-600">${escapeHtml(deleteLabel)}</button>
                                <button type="button" onclick="addObject(${client.id})" class="filter-btn">${escapeHtml(addObjectLabel)}</button>
                            </div>
                        </div>
                    `;
                }).join('');

                renderPagination('clients-pagination-list', totalPages);
            };

            window.renderAll = function() {
                window.renderTable?.();
                window.renderList?.();
                window.renderFunnel?.();
            };

            function renderActiveTab() {
                const currentTab = document.querySelector('[data-tab].active')?.dataset.tab || 'table';
                if (currentTab === 'table') window.renderTable?.();
                if (currentTab === 'list') window.renderList?.();
                if (currentTab === 'funnel') window.renderFunnel?.();
            }

            function renderPagination(containerId, totalPages) {
                const container = document.getElementById(containerId);
                if (!container) return;

                const prevLabel = '{{ __('clients.prev') }}';
                const nextLabel = '{{ __('clients.next') }}';

                // Если данных меньше одной страницы — скрываем пагинацию.
                if (allClients.length <= itemsPerPage) {
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
                        if (p === '...') {
                            return `<span class="px-2 text-[#64748b] dark:text-[#A1A09A] opacity-60">...</span>`;
                        }
                        const active = p === currentPage;
                        return `<button type="button" data-page="${p}" class="px-4 ${active ? 'active' : ''}" ${active ? 'disabled' : ''}>${p}</button>`;
                    }).join('')}
                    <button type="button" ${nextDisabled ? 'disabled' : ''} data-page="${currentPage + 1}" class="px-4">${nextLabel}</button>
                `;

                container.querySelectorAll('button[data-page]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        currentPage = parseInt(btn.dataset.page, 10);
                        renderActiveTab();
                    });
                });
            }

            window.refreshClients = async function() {
                currentPage = 1;
                const search = document.getElementById('search-input')?.value?.trim() || '';
                const status = document.getElementById('status-filter')?.value?.trim() || '';

                const params = new URLSearchParams();
                if (search !== '') params.set('search', search);
                if (status !== '') params.set('status', status);

                const url = params.toString() ? `/clients/search?${params.toString()}` : `/clients/search`;

                const r = await fetch(url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                const payload = await r.json().catch(() => ({ data: [] }));
                window.allClients = payload.data || [];
                allClients = window.allClients;

                window.renderAll?.();
            };

            // Первичная отрисовка с учетом пагинации
            renderActiveTab();

            // AJAX-сохранение (создание / обновление)
            document.getElementById('client-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const form = e.target;
                const action = form.getAttribute('action') || form.action;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const r = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            ...(token ? { 'X-CSRF-TOKEN': token } : {})
                        },
                        body: new FormData(form)
                    });

                    const data = await r.json().catch(() => ({}));

                    if (!r.ok || !data.success) {
                        projectAlert('error', data.message || '{{ __('clients.error') }}', '', 3000);
                        return;
                    }

                    projectAlert('success', data.message || '{{ __('clients.saved') }}', '', 2500);
                    closeClientModal();
                    form.reset();
                    document.getElementById('client_id').value = '';
                    await window.refreshClients?.();
                } catch (err) {
                    projectAlert('error', '{{ __('clients.error') }}', '', 3000);
                    console.error(err);
                }
            });





        });

        @if ($errors->any())
            @php
                $objectFields = ['client_id', 'address', 'type', 'status', 'area', 'repair_budget_planned', 'repair_budget_actual', 'repair_budget_per_m2_planned', 'repair_budget_per_m2_actual', 'links', 'file', 'comment'];
                $hasObjectErrors = collect($objectFields)->contains(function ($field) use ($errors) {
                    return $errors->has($field);
                });
            @endphp

            @if ($hasObjectErrors)
                // Открываем модалку объекта
                const modal = document.getElementById('object-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            @else
                // Открываем модалку клиента
                const modal = document.getElementById('client-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            @endif
        @endif

        function closeClientModal() {
            document.getElementById('client-modal').classList.add('hidden');
            document.getElementById('client-modal').classList.remove('flex');
        }

        function closeObjectModal() {
            document.getElementById('object-modal').classList.add('hidden');
            document.getElementById('object-modal').classList.remove('flex');
            document.getElementById('object-form').reset();
            // Переактивируем select
            document.querySelector('#object-form select[name="client_id"]').disabled = false;

            const linksContainer = document.getElementById('links-container');
            linksContainer.innerHTML =
                `<input type="url" name="links[]" placeholder="{{ __('clients.add_link') }}" class="modal-input">`;
        }

        function addObject(clientId) {
            document.getElementById('object-modal').classList.remove('hidden');
            document.getElementById('object-modal').classList.add('flex');


            const clientSelect = document.querySelector('#object-form select[name="client_id"]');

            // Очищаем дополнительные поля ссылок
            const linksContainer = document.getElementById('links-container');
            linksContainer.innerHTML = `
        <div class="flex gap-2 mb-2">
            <input type="url" name="links[]" class="flex-1 px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
        </div>
    `;
            // Если выбран конкретный клиент - выбираем его и отключаем select
            if (clientId) {
                clientSelect.value = clientId;

            } else {
                clientSelect.disabled = false;
            }
        }

        function addLinkField() {
            const container = document.getElementById('links-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
                <input type="url" name="links[]" placeholder="{{ __('clients.add_link') }}" class="modal-input flex-1">
                <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 hover:border-red-300 transition-colors">×</button>
            `;
            container.appendChild(div);
        }

        function closeViewClientModal() {
            const modal = document.getElementById('view-client-modal');
            const panel = modal.querySelector('div[class*="absolute"]');
            modal.classList.add('hidden');
            if (panel) {
                panel.classList.add('translate-x-full');
                panel.classList.remove('translate-x-0');
            }
            window.currentViewedClientId = null;
        }

        function viewClient(id) {
            const rows = document.querySelectorAll(`tr[data-client], div[data-client]`);
            let client = null;
            rows.forEach(row => {
                const c = JSON.parse(row.getAttribute('data-client'));
                if (c.id === id) {
                    client = c;
                }
            });
            if (client) {
                window.currentViewedClientId = parseInt(id, 10);
                const filePaths = Array.isArray(client.file_paths) && client.file_paths.length
                    ? client.file_paths
                    : (client.file_path ? [client.file_path] : []);

                const filesHtml = filePaths.length
                    ? filePaths.map((p, idx) => {
                        const fileUrl = p ? `/storage/${p}` : '';
                        const fileName = p ? p.split('/').pop() : '';
                        const safeFileName = String(fileName || '').replace(/[&<>"']/g, (c) => ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#39;'
                        }[c]));

                        return `
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">${safeFileName}</span>
                                <div class="flex items-center gap-2">
                                    <a href="${fileUrl}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] dark:hover:border-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                                       title="{{ __('clients.view') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        {{ __('clients.view') }}
                                    </a>

                                    <a href="${fileUrl}" download="${fileName}"
                                       class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                                       title="{{ __('clients.download') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
                                        </svg>
                                        {{ __('clients.download') }}
                                    </a>

                                    <button type="button"
                                        onclick="window.deleteClientFile(${client.id}, ${idx})"
                                        class="inline-flex items-center justify-center p-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-500 hover:text-red-600 transition-colors"
                                        title="{{ __('clients.delete_file') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')
                    : '';
                const content = document.getElementById('view-client-content');
                const statusText = client.status === 'new' ? '{{ __('clients.new') }}' :
                    client.status === 'in_work' ? '{{ __('clients.in_work') }}' :
                    '{{ __('clients.not_working') }}';
                content.innerHTML = `
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.full_name') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${client.full_name}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.phone') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${client.phone}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.email') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${client.email}</p>
                </div>
                ${client.link ? `
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.link') }}</label>
                    <a href="${client.link}" target="_blank" class="text-[#f59e0b] hover:underline">${client.link}</a>
                </div>
                ` : ''}
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.status') }}</label>
                    <p id="view-client-status-text" class="text-[#0f172a] dark:text-[#EDEDEC]">${statusText}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.objects_count') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${client.count_objects}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.total_amount') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]"> ${new Intl.NumberFormat('kk-KZ').format(client.sum_repair_budget_planned || 0)} ₸</p>
                </div>
                ${client.comment ? `
                                                                                                                                                                                                                                                                                                                                        <div>
                                                                                                                                                                                                                                                                                                                                            <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('clients.comment') }}</label>
                                                                                                                                                                                                                                                                                                                                            <p class="text-[#0f172a] dark:text-[#EDEDEC]">${client.comment}</p>
                                                                                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                                                                                        ` : ''}

                ${filesHtml ? `
                <div class="pt-4">
                    <label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">
                        {{ __('clients.files') }}
                    </label>
                    <div class="mt-1 flex flex-col gap-2">
                        ${filesHtml}
                    </div>
                </div>
                ` : ''}

                <div class="pt-4">
                    <a href="/clients/${client.id}"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] dark:text-[#f59e0b] hover:bg-[#fef3c7] dark:hover:bg-[#1D0002] transition-colors"
                        title="{{ __('clients.details') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('clients.details') }}
                    </a>
                </div>
        `;
                const modal = document.getElementById('view-client-modal');
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

        async function deleteClientFile(clientId, fileIndex) {
            if (!confirm('{{ __('clients.delete_file_confirm') }}')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
            try {
                const r = await fetch(`/clients/${clientId}/files/${fileIndex}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });

                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    projectAlert('error', data.message || '{{ __('clients.error') }}', '', 3000);
                    return;
                }

                await window.refreshClients?.();
                window.currentViewedClientId = parseInt(clientId, 10);
                window.renderActiveTab?.();
                viewClient(parseInt(clientId, 10));
                projectAlert('success', '{{ __('clients.delete_file') }}', '', 2000);
            } catch (e) {
                console.error(e);
                projectAlert('error', '{{ __('clients.error') }}', '', 3000);
            }
        }
        window.deleteClientFile = deleteClientFile;

        function editClient(id) {
            const rows = document.querySelectorAll(`tr[data-client], div[data-client]`);
            let client = null;
            rows.forEach(row => {
                const c = JSON.parse(row.getAttribute('data-client'));
                if (c.id === id) {
                    client = c;
                }
            });
            if (client) {
                document.getElementById('client-modal').classList.remove('hidden');
                document.getElementById('client-modal').classList.add('flex');
                document.getElementById('client-modal-title').textContent = '{{ __('clients.edit') }}';
                document.getElementById('client_id').value = client.id;
                const clientTypeSelectEl = document.getElementById('client_type');
                if (clientTypeSelectEl) {
                    clientTypeSelectEl.value = client.client_type || 'person';
                }
                document.getElementById('client_name').value = client.full_name || '';
                document.getElementById('client_phone').value = client.phone;
                document.getElementById('client_email').value = client.email;
                document.getElementById('client_status').value = client.status;
                document.getElementById('client_comment').value = client.comment || '';
                document.getElementById('client_link').value = client.link || '';
                if (typeof updateClientFullNameLabel === 'function') {
                    updateClientFullNameLabel();
                }
            }






        }




        // Drag & Drop для воронок
        let draggedElement = null;

        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }

        function drag(ev) {
            draggedElement = ev.target.closest('.funnel-card');
            if (draggedElement) {
                draggedElement.classList.add('dragging');
                ev.dataTransfer.effectAllowed = 'move';

            }
        }

        function drop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('drag-over');

            if (draggedElement) {
                const newStatus = ev.currentTarget.dataset.status;
                const clientId = draggedElement.dataset.clientId;

                ev.currentTarget.querySelector('.funnel-cards').appendChild(draggedElement);
                draggedElement.classList.remove('dragging');

                let updatedClient = null;
                try {
                    updatedClient = JSON.parse(draggedElement.dataset.client || '{}');
                    updatedClient.status = newStatus;
                } catch (_) {}
                updateClientStatus(clientId, newStatus, updatedClient);
            }
        }

        const clientStatusBadgeClasses = {
            new: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
            in_work: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
            not_working: 'client-status-badge px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'
        };
        const clientStatusLabels = {
            new: '{{ __("clients.new") }}',
            in_work: '{{ __("clients.in_work") }}',
            not_working: '{{ __("clients.not_working") }}'
        };

        function updateClientStatusInAllViews(clientId, newStatus, updatedClient) {
            document.querySelectorAll(`[data-client-id="${clientId}"]`).forEach(el => {
                if (updatedClient) el.dataset.client = JSON.stringify(updatedClient);
                const badge = el.querySelector('.client-status-badge');
                if (badge) {
                    badge.className = clientStatusBadgeClasses[newStatus] || badge.className;
                    badge.textContent = clientStatusLabels[newStatus] || newStatus;
                }
            });
        }

        function updateClientStatus(clientId, newStatus, updatedClient) {
            const numericId = parseInt(clientId);
            fetch(`/clients/${numericId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                .then(response => response.json())
                .then(data => {
                    // Полный rerender таблицы/списка/воронки из актуальных данных.
                    if (data.success) {
                        const modal = document.getElementById('view-client-modal');
                        const isModalOpen = modal && !modal.classList.contains('hidden');

                        // Если открыта модалка "Просмотр" текущего клиента — обновляем только текст статуса.
                        const shouldUpdateModal = isModalOpen && window.currentViewedClientId === numericId;
                        if (shouldUpdateModal && data.client?.status) {
                            const el = document.getElementById('view-client-status-text');
                            if (el) {
                                el.textContent = clientStatusLabels[data.client.status] || data.client.status;
                            }
                        }

                        window.refreshClients?.();
                    } else {
                        console.error('Ошибка при обновлении статуса');
                        window.refreshClients?.();
                    }

                    draggedElement = null;
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    window.refreshClients?.();
                    draggedElement = null;
                });
        }

        function deleteClient(id) {
            if (!confirm('{{ __("clients.delete_confirm") }}')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
            fetch('{{ url("clients/delete") }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            }).then(r => {
                if (r.ok) {
                    window.refreshClients?.();
                    return;
                }
                throw new Error('Delete failed');
            }).catch(() => projectAlert('error', '{{ __("clients.error") }}', '', 3000));
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
