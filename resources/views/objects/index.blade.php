@extends('layouts.dashboard')

@section('title', __('objects.object_passport'))

@push('styles')
    <style>
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
    </style>
@endpush

@section('content')
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('objects.object_passport') }}</h1>
        <button id="add-object-btn" class="add-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('objects.add_object') }}
        </button>
    </div>

    <!-- Вкладки -->
    <div class="mb-6 flex gap-2">
        <button data-tab="table" class="tab-btn active">{{ __('objects.table') }}</button>
        <button data-tab="list" class="tab-btn">{{ __('objects.list') }}</button>
        <button data-tab="funnel" class="tab-btn">{{ __('objects.funnel') }}</button>
    </div>

    <!-- Поиск и фильтры -->
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
        <div class="w-full md:w-48">
            <select id="client-filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('objects.all_clients') }}</option>
                @foreach ($clients as $client)
                    <option value="{{ $client['id'] }}">{{ $client['full_name'] }}</option>
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

    <!-- Контент вкладок -->
    <div id="table-view" class="tab-content">
        <!-- Таблица -->
        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
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
                                data-sort="client">{{ __('objects.client') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header"
                                data-sort="area">{{ __('objects.area') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.repair_budget') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.repair_budget_per_m2') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.projects') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.links') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.comment') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('objects.view') }}</th>
                        </tr>
                    </thead>
                    <tbody id="objects-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]">
                        @foreach ($objects as $object)
                            <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]"
                                data-object-id="{{ $object['id'] }}" data-object='@json($object)'>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $object['address'] }}
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    @if ($object['type'] === 'apartment')
                                        {{ __('objects.apartment') }}
                                    @elseif($object['type'] === 'house')
                                        {{ __('objects.house') }}
                                    @elseif($object['type'] === 'commercial')
                                        {{ __('objects.commercial') }}
                                    @else
                                        {{ __('objects.other') }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="object-status-badge px-2 py-1 rounded text-xs font-medium
                            @if ($object['status'] === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200
                            @elseif($object['status'] === 'in_work')
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                            @else
                                bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200 @endif">
                                        @if ($object['status'] === 'new')
                                            {{ __('objects.new') }}
                                        @elseif($object['status'] === 'in_work')
                                            {{ __('objects.in_work') }}
                                        @else
                                            {{ __('objects.not_working') }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $object['client_name'] }}</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ number_format($object['area'], 2, ',', ' ') }} м²</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a]">
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.planned') }}:
                                        {{ number_format($object['repair_budget_planned'], 0, ',', ' ') }} ₸</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.actual') }}:
                                        {{ $object['repair_budget_actual'] > 0 ? number_format($object['repair_budget_actual'], 0, ',', ' ') . ' ₸' : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.planned') }}:
                                        {{ number_format($object['repair_budget_per_m2_planned'], 0, ',', ' ') }} ₸</div>
                                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.actual') }}:
                                        {{ $object['repair_budget_per_m2_actual'] > 0 ? number_format($object['repair_budget_per_m2_actual'], 0, ',', ' ') . ' ₸' : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $object['projects_count'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if (count($object['links']) > 0)
                                        @foreach ($object['links'] as $link)
                                            <a href="{{ $link }}" target="_blank"
                                                class="text-[#f59e0b] hover:underline text-xs">{{ __('objects.links') }}</a>
                                        @endforeach
                                    @else
                                        <span class="text-[#64748b] dark:text-[#A1A09A]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $object['comment'] ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <button onclick="viewObject({{ $object['id'] }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('objects.view') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button onclick="editObject({{ $object['id'] }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('objects.edit') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button onclick="deleteObject({{ $object['id'] }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-500 transition-colors"
                                            title="{{ __('objects.delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <button onclick="addProject({{ $object['id'] }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('objects.add_project') }}">
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
            <!-- Пагинация -->
            <div class="pagination" id="pagination">
            </div>
        </div>
    </div>

    <div id="list-view" class="tab-content hidden">
        <div class="space-y-4" id="objects-list-body">
            @foreach ($objects as $object)
                <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6"
                    data-object-id="{{ $object['id'] }}" data-object='@json($object)'>
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">
                                {{ $object['address'] }}</h3>
                            <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                <p>{{ $object['client_name'] }}</p>
                                <p>
                                    @if ($object['type'] === 'apartment')
                                        {{ __('objects.apartment') }}
                                    @elseif($object['type'] === 'house')
                                        {{ __('objects.house') }}
                                    @elseif($object['type'] === 'commercial')
                                        {{ __('objects.commercial') }}
                                    @else
                                        {{ __('objects.other') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="object-status-badge px-2 py-1 rounded text-xs font-medium
                    @if ($object['status'] === 'new') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200
                    @elseif($object['status'] === 'in_work')
                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200
                    @else
                        bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200 @endif">
                            @if ($object['status'] === 'new')
                                {{ __('objects.new') }}
                            @elseif($object['status'] === 'in_work')
                                {{ __('objects.in_work') }}
                            @else
                                {{ __('objects.not_working') }}
                            @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.area') }}:</span>
                            <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ number_format($object['area'], 2, ',', ' ') }}
                                м²</span>
                        </div>
                        <div>
                            <span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('objects.projects') }}:</span>
                            <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ $object['projects_count'] }}</span>
                        </div>
                    </div>
                    @if ($object['comment'])
                        <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ $object['comment'] }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        <button onclick="viewObject({{ $object['id'] }})"
                            class="filter-btn">{{ __('objects.view') }}</button>
                        <button onclick="editObject({{ $object['id'] }})"
                            class="filter-btn">{{ __('objects.edit') }}</button>
                        <button onclick="deleteObject({{ $object['id'] }})"
                            class="filter-btn text-red-500 hover:text-red-600">{{ __('objects.delete') }}</button>
                        <button onclick="addProject({{ $object['id'] }})"
                            class="filter-btn">{{ __('objects.add_project') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="funnel-view" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="funnel-column" data-status="new" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.new') }}</h3>
                <div id="funnel-new" class="funnel-cards">
                    @foreach ($objects as $object)
                        @if ($object['status'] === 'new')
                            <div class="funnel-card" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-object-id="{{ $object['id'] }}" data-object='@json($object)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $object['address'] }}
                                </h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $object['client_name'] }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.edit') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="funnel-column" data-status="in_work" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.in_work') }}</h3>
                <div id="funnel-in-work" class="funnel-cards">
                    @foreach ($objects as $object)
                        @if ($object['status'] === 'in_work')
                            <div class="funnel-card" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-object-id="{{ $object['id'] }}" data-object='@json($object)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $object['address'] }}
                                </h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $object['client_name'] }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.edit') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="funnel-column" data-status="not_working" ondrop="drop(event)" ondragover="allowDrop(event)">
                <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-4">{{ __('objects.not_working') }}
                </h3>
                <div id="funnel-not-working" class="funnel-cards">
                    @foreach ($objects as $object)
                        @if ($object['status'] === 'not_working')
                            <div class="funnel-card" draggable="true"
                                ondragstart="if(event.target.closest('button')) { event.preventDefault(); return false; } drag(event)"
                                data-object-id="{{ $object['id'] }}" data-object='@json($object)'>
                                <h4 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-1">{{ $object['address'] }}
                                </h4>
                                <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ $object['client_name'] }}</p>
                                <div class="flex items-center gap-2 mt-2" onclick="event.stopPropagation()">
                                    <button type="button"
                                        onclick="event.stopPropagation(); viewObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.view') }}</button>
                                    <button type="button"
                                        onclick="event.stopPropagation(); editObject({{ $object['id'] }})"
                                        class="text-xs px-2 py-1 rounded border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">{{ __('objects.edit') }}</button>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка просмотра объекта (справа) -->
    <div id="view-object-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay"
        onmousedown="if(event.target === this) closeViewObjectModal()">
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#e2e8f0] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content"
            onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
            <div class="flex flex-col h-full">
                <div
                    class="flex items-center justify-between px-6 py-5 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('objects.view') }}
                        </h2>
                        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">
                            {{ __('objects.modal_view_subtitle') }}</p>
                    </div>
                    <button onclick="closeViewObjectModal()"
                        class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-object-content" class="flex-1 overflow-y-auto p-6 space-y-5">
                    <!-- Контент будет заполнен через JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка добавления/редактирования объекта -->
    <div id="object-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeObjectModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()" onmousedown="event.stopPropagation()">
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
                action="{{ route('objects.add_object') }}" enctype="multipart/form-data">
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
                                    <option value="{{ $client['id'] }}">{{ $client['full_name'] }}</option>
                                @endforeach
                            </select>
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
            document.getElementById('add-object-btn').addEventListener('click', () => {
                document.getElementById('object-modal').classList.remove('hidden');
                document.getElementById('object-modal').classList.add('flex');
                document.getElementById('object-form').reset();
                document.getElementById('object-modal-title').textContent =
                    '{{ __('objects.new_object') }}';
                document.getElementById('object-form').action = '{{ route('objects.add_object') }}';
                document.getElementById('object_form_method').value = '';
                document.getElementById('object_id').value = '';
                resetLinksContainer();
            });

            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tab = this.dataset.tab;
                    document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove(
                        'active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.add(
                        'hidden'));
                    const view = document.getElementById(tab + '-view');
                    if (view) view.classList.remove('hidden');
                });
            });

            function objectMatchesFilters(obj) {
                const search = (document.getElementById('search-input')?.value || '').toLowerCase().trim();
                const typeFilter = document.getElementById('type-filter')?.value || '';
                const clientFilter = document.getElementById('client-filter')?.value || '';
                const statusFilter = document.getElementById('status-filter')?.value || '';

                if (typeFilter && obj.type !== typeFilter) return false;
                if (clientFilter && String(obj.client_id) !== clientFilter) return false;
                if (statusFilter && obj.status !== statusFilter) return false;

                if (search) {
                    const searchStr = [
                        obj.address,
                        obj.client_name,
                        obj.type,
                        obj.status,
                        obj.comment,
                        (obj.links || []).join(' ')
                    ].filter(Boolean).join(' ').toLowerCase();
                    if (!searchStr.includes(search)) return false;
                }
                return true;
            }

            function applyFilters() {
                const tableRows = document.querySelectorAll('#objects-table-body tr[data-object]');
                const listCards = document.querySelectorAll('#objects-list-body > div[data-object]');
                const funnelCards = document.querySelectorAll('.funnel-card[data-object]');

                [tableRows, listCards, funnelCards].forEach(elements => {
                    elements.forEach(el => {
                        try {
                            const obj = JSON.parse(el.getAttribute('data-object') || '{}');
                            el.style.display = objectMatchesFilters(obj) ? '' : 'none';
                        } catch (_) {
                            el.style.display = '';
                        }
                    });
                });
            }

            [document.getElementById('search-input'), document.getElementById('type-filter'), document
                .getElementById('client-filter'), document.getElementById('status-filter')
            ].forEach(el => {
                if (!el) return;
                el.addEventListener('input', applyFilters);
                el.addEventListener('change', applyFilters);
            });
            window.applyFilters = applyFilters;
        });

        function resetLinksContainer() {
            const linksContainer = document.getElementById('links-container');
            linksContainer.innerHTML = `
        <div class="flex gap-2">
            <div class="input-with-icon flex-1">
                <span class="input-icon text-[#64748b] dark:text-[#A1A09A]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </span>
                <input type="url" name="links[]" placeholder="{{ __('objects.paste_link') }}" class="modal-input">
            </div>
        </div>
    `;
        }

        function closeObjectModal() {
            document.getElementById('object-modal').classList.add('hidden');
            document.getElementById('object-modal').classList.remove('flex');
            document.getElementById('object-form').reset();
            resetLinksContainer();
        }

        function addLinkField() {
            const container = document.getElementById('links-container');
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
        <div class="input-with-icon flex-1">
            <span class="input-icon text-[#64748b] dark:text-[#A1A09A]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            </span>
            <input type="url" name="links[]" placeholder="{{ __('objects.paste_link') }}" class="modal-input">
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 hover:border-red-300 transition-colors">×</button>
    `;
            container.appendChild(div);
        }

        function closeViewObjectModal() {
            const modal = document.getElementById('view-object-modal');
            const panel = modal.querySelector('div[class*="absolute"]');
            modal.classList.add('hidden');
            if (panel) {
                panel.classList.add('translate-x-full');
                panel.classList.remove('translate-x-0');
            }
        }

        function viewObject(id) {
            const rows = document.querySelectorAll('tr[data-object], div[data-object]');
            let obj = null;
            rows.forEach(row => {
                const o = JSON.parse(row.getAttribute('data-object'));
                if (o.id === id) {
                    obj = o;
                }
            });
            if (obj) {
                const content = document.getElementById('view-object-content');
                const statusText = obj.status === 'new' ? '{{ __('objects.new') }}' :
                    obj.status === 'in_work' ? '{{ __('objects.in_work') }}' :
                    '{{ __('objects.not_working') }}';
                const typeText = obj.type === 'apartment' ? '{{ __('objects.apartment') }}' :
                    obj.type === 'house' ? '{{ __('objects.house') }}' :
                    obj.type === 'commercial' ? '{{ __('objects.commercial') }}' :
                    '{{ __('objects.other') }}';
                content.innerHTML = `
            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                <label class="modal-section-title">{{ __('objects.address') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.address}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <label class="modal-helper block mb-1">{{ __('objects.type') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${typeText}</p>
                </div>
                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <label class="modal-helper block mb-1">{{ __('objects.status') }}</label>
                    <span class="px-2 py-0.5 rounded text-xs font-medium ${obj.status === 'new' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200' : obj.status === 'in_work' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'}">${statusText}</span>
                </div>
            </div>
            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                <label class="modal-helper block mb-1">{{ __('objects.client') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.client_name}</p>
            </div>
            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                <label class="modal-helper block mb-1">{{ __('objects.area') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${parseFloat(obj.area).toLocaleString('kk-KZ', {minimumFractionDigits: 2, maximumFractionDigits: 2})} {{ __('objects.area_m2') }}</p>
            </div>
            <div>
                <h3 class="modal-section-title mb-2">{{ __('objects.repair_budget') }}</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <p class="modal-helper mb-1">{{ __('objects.planned') }}</p>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${parseInt(obj.repair_budget_planned).toLocaleString('kk-KZ')} ₸</p>
                    </div>
                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <p class="modal-helper mb-1">{{ __('objects.actual') }}</p>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.repair_budget_actual > 0 ? parseInt(obj.repair_budget_actual).toLocaleString('kk-KZ') + ' ₸' : '-'}</p>
                    </div>
                </div>
            </div>
            <div>
                <h3 class="modal-section-title mb-2">{{ __('objects.repair_budget_per_m2') }}</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <p class="modal-helper mb-1">{{ __('objects.planned') }}</p>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${parseInt(obj.repair_budget_per_m2_planned).toLocaleString('kk-KZ')} ₸</p>
                    </div>
                    <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                        <p class="modal-helper mb-1">{{ __('objects.actual') }}</p>
                        <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.repair_budget_per_m2_actual > 0 ? parseInt(obj.repair_budget_per_m2_actual).toLocaleString('kk-KZ') + ' ₸' : '-'}</p>
                    </div>
                </div>
            </div>
            <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                <label class="modal-helper block mb-1">{{ __('objects.projects') }}</label>
                <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">${obj.projects_count}</p>
            </div>
            ${obj.links && obj.links.length > 0 ? `
                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <label class="modal-helper block mb-2">{{ __('objects.links') }}</label>
                    <div class="space-y-1">
                        ${obj.links.map(link => '<a href="'+link+'" target="_blank" class="block text-[#f59e0b] hover:underline text-sm truncate">'+link+'</a>').join('')}
                    </div>
                </div>
                ` : ''}
            ${obj.comment ? `
                <div class="p-4 rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a] border border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <label class="modal-helper block mb-1">{{ __('objects.comment') }}</label>
                    <p class="text-[#0f172a] dark:text-[#EDEDEC]">${obj.comment}</p>
                </div>
                ` : ''}
        `;
                const modal = document.getElementById('view-object-modal');
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

        function editObject(id) {
            const rows = document.querySelectorAll('tr[data-object], div[data-object]');
            let obj = null;
            rows.forEach(row => {
                try {
                    const o = JSON.parse(row.getAttribute('data-object') || '{}');
                    if (o.id === id) obj = o;
                } catch (_) {}
            });
            if (obj) {
                document.getElementById('object-modal').classList.remove('hidden');
                document.getElementById('object-modal').classList.add('flex');
                document.getElementById('object-modal-title').textContent = '{{ __('objects.edit_object') }}';
                document.getElementById('object-form').action = '{{ url('objects/update') }}/' + obj.id;
                document.getElementById('object_form_method').value = 'PUT';
                document.getElementById('object_id').value = obj.id;
                document.querySelector('input[name="address"]').value = obj.address || '';
                document.querySelector('select[name="type"]').value = obj.type || '';
                document.querySelector('select[name="status"]').value = obj.status || '';
                document.querySelector('select[name="client_id"]').value = obj.client_id || '';
                document.querySelector('input[name="area"]').value = obj.area || '';
                document.querySelector('input[name="repair_budget_planned"]').value = obj.repair_budget_planned ?? '';
                document.querySelector('input[name="repair_budget_actual"]').value = obj.repair_budget_actual ?? '';
                document.querySelector('input[name="repair_budget_per_m2_planned"]').value = obj
                    .repair_budget_per_m2_planned ?? '';
                document.querySelector('input[name="repair_budget_per_m2_actual"]').value = obj
                    .repair_budget_per_m2_actual ?? '';
                document.querySelector('textarea[name="comment"]').value = obj.comment || '';
                const links = obj.links || [];
                resetLinksContainer();
                const container = document.getElementById('links-container');
                links.forEach((url, i) => {
                    if (i === 0) {
                        container.querySelector('input[name="links[]"]').value = url;
                    } else {
                        const div = document.createElement('div');
                        div.className = 'flex gap-2';
                        div.innerHTML = `
                    <div class="input-with-icon flex-1">
                        <span class="input-icon text-[#64748b] dark:text-[#A1A09A]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </span>
                        <input type="url" name="links[]" placeholder="{{ __('objects.paste_link') }}" class="modal-input" value="${(url || '').replace(/"/g, '&quot;')}">
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="px-3 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 hover:border-red-300 transition-colors">×</button>
                `;
                        container.appendChild(div);
                    }
                });
            }
        }

        function deleteObject(id) {
            if (!confirm('{{ __('objects.delete_confirm') }}')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector(
                'input[name="_token"]')?.value;
            fetch('{{ url('objects/delete') }}/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                },
            }).then(r => {
                if (r.ok) {
                    document.querySelectorAll(`tr[data-object], div[data-object]`).forEach(el => {
                        try {
                            const o = JSON.parse(el.getAttribute('data-object') || '{}');
                            if (o.id === id) el.remove();
                        } catch (_) {}
                    });
                    document.querySelectorAll(`.funnel-card[data-object-id="${id}"]`).forEach(el => el.remove());
                    return;
                }
                throw new Error('Delete failed');
            }).catch(() => alert('{{ __('objects.error') }}'));
        }

        function addProject(objectId) {
            window.location.href = '{{ route("projects.index") }}' + (objectId ? '?add_project=1&object_id=' + objectId : '?add_project=1');
        }


        // Drag & Drop для воронок
        let draggedElement = null;

        function allowDrop(ev) {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'move';
            ev.currentTarget.classList.add('drag-over');
        }

        function drag(ev) {
            draggedElement = ev.target;
            ev.target.classList.add('dragging');
            ev.dataTransfer.effectAllowed = 'move';
        }

        const statusBadgeClasses = {
            new: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200',
            in_work: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200',
            not_working: 'object-status-badge px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-200'
        };
        const statusLabels = {
            new: '{{ __("objects.new") }}',
            in_work: '{{ __("objects.in_work") }}',
            not_working: '{{ __("objects.not_working") }}'
        };
        function updateObjectStatusInAllViews(objectId, newStatus, updatedObject) {
            document.querySelectorAll(`[data-object-id="${objectId}"]`).forEach(el => {
                el.dataset.object = JSON.stringify(updatedObject);
                const badge = el.querySelector('.object-status-badge');
                if (badge) {
                    badge.className = statusBadgeClasses[newStatus] || badge.className;
                    badge.textContent = statusLabels[newStatus] || newStatus;
                }
            });
            if (typeof window.applyFilters === 'function') window.applyFilters();
        }

        function drop(ev) {
            ev.preventDefault();
            const column = ev.currentTarget;
            column.classList.remove('drag-over');

            if (draggedElement) {
                const newStatus = column.dataset.status;
                const objectId = draggedElement.dataset.objectId;
                const cardsContainer = column.querySelector('.funnel-cards');
                
                const oldObject = JSON.parse(draggedElement.dataset.object || '{}');
                const updatedObject = Object.assign({}, oldObject, { status: newStatus });

                if (cardsContainer) {
                    cardsContainer.appendChild(draggedElement);
                    draggedElement.dataset.object = JSON.stringify(updatedObject);

                }
                draggedElement.classList.remove('dragging');

                const cardToRestore = draggedElement;
                const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector(
                    'input[name="_token"]')?.value;
                fetch('{{ route('objects.update_status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        id: parseInt(objectId),
                        status: newStatus
                    }),
                }).then(r => {
                    if (!r.ok) throw new Error('Update failed');
                    updateObjectStatusInAllViews(objectId, newStatus, updatedObject);
                }).catch(() => {
                    if (cardsContainer && cardToRestore) {
                        const oldColumn = document.querySelector(`.funnel-column[data-status="${oldObject.status}"] .funnel-cards`);
                        if (oldColumn) oldColumn.appendChild(cardToRestore);
                        cardToRestore.dataset.object = JSON.stringify(oldObject);
                    }
                });

                draggedElement = null;
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
