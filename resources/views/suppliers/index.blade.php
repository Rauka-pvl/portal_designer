@extends('layouts.dashboard')

@section('title', __('suppliers.suppliers'))

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
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

        .sortable-header {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }

        .sortable-header:hover {
            color: #f59e0b;
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

        .favorite-btn {
            color: #64748b;
            transition: all 0.3s;
        }

        .favorite-btn.active {
            color: #f59e0b;
        }

        .favorite-btn:hover {
            color: #f59e0b;
        }

        .accordion-section {
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 0.5rem;
            overflow: hidden;
            isolation: isolate;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-section:hover,
        .accordion-section:hover .accordion-header {
            background: #f1f5f9;
        }

        .dark .accordion-section {
            background: #161615;
        }

        .dark .accordion-section:hover,
        .dark .accordion-section:hover .accordion-header {
            background: #1a1a18;
        }

        .dark .accordion-header {
            background: #161615;
        }

        .accordion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
            background: #f8fafc;
            border: none;
            position: relative;
            z-index: 1;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-header:hover {
            color: #f59e0b;
            padding-left: 1.5rem;
        }

        .accordion-header svg {
            flex-shrink: 0;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.7;
        }

        .accordion-header:hover svg,
        .accordion-header[aria-expanded="true"] svg {
            opacity: 1;
        }

        .accordion-header[aria-expanded="true"] svg {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .accordion-content:not(.accordion-open) .accordion-body {
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s linear 0.2s, opacity 0.2s ease;
        }

        .accordion-content.accordion-open .accordion-body {
            visibility: visible;
            opacity: 1;
            transition: visibility 0s, opacity 0.25s ease;
        }

        .accordion-content.accordion-open {
            max-height: 1500px;
        }

        .accordion-body {
            overflow: hidden;
            padding: 0 1.25rem 1.25rem;
            animation: accordionFadeIn 0.35s ease-out;
            background: #f8fafc;
        }

        .dark .accordion-body {
            background: #161615;
        }

        @keyframes accordionFadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dark .accordion-header {
            color: #EDEDEC;
        }

        .dark .accordion-header:hover {
            color: #f59e0b;
        }

        #supplier-modal .modal-content {
            animation: modalSlideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        #supplier-modal {
            transition: opacity 0.3s ease;
        }

        #supplier-modal.modal-closing {
            opacity: 0;
        }

        #supplier-modal.modal-closing .modal-content {
            transform: scale(0.97) translateY(8px);
            opacity: 0;
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #supplier-modal .accordion-section {
            animation: sectionStagger 0.4s ease-out backwards;
        }

        #supplier-modal .accordion-section:nth-child(1) {
            animation-delay: 0.05s;
        }

        #supplier-modal .accordion-section:nth-child(2) {
            animation-delay: 0.1s;
        }

        #supplier-modal .accordion-section:nth-child(3) {
            animation-delay: 0.15s;
        }

        @keyframes sectionStagger {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #supplier-modal .modal-input {
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .accordion-add-btn {
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .accordion-add-btn:hover {
            transform: translateX(3px);
            opacity: 0.9;
        }
    </style>
@endpush

@section('content')
    @if (session('success'))
        <div
            class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif
    <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.suppliers') }}</h1>
        <button id="add-supplier-btn" class="add-btn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            {{ __('suppliers.add_supplier') }}
        </button>
    </div>

    <!-- Вкладки -->
    <div class="mb-6 flex gap-2">
        <button data-tab="table" class="tab-btn active">{{ __('suppliers.table') }}</button>
        <button data-tab="list" class="tab-btn">{{ __('suppliers.list') }}</button>
    </div>

    <!-- Поиск и фильтры -->
    <form method="GET" action="{{ route('suppliers.index') }}" id="search-form"
        class="mb-6 flex flex-col md:flex-row gap-4 flex-wrap">
        <input type="hidden" name="sort_by" id="sort_by_input" value="{{ request('sort_by', '') }}">
        <input type="hidden" name="sort_dir" id="sort_dir_input" value="{{ request('sort_dir', 'asc') }}">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('suppliers.search') }}"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
        </div>
        <div class="w-full md:w-44">
            <select name="type_filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="all" {{ request('type_filter', 'all') === 'all' ? 'selected' : '' }}>
                    {{ __('suppliers.filter_all') }}</option>
                <option value="recommended" {{ request('type_filter') === 'recommended' ? 'selected' : '' }}>
                    {{ __('suppliers.filter_recommended') }}</option>
                <option value="favorites" {{ request('type_filter') === 'favorites' ? 'selected' : '' }}>
                    {{ __('suppliers.filter_favorites') }}</option>
            </select>
        </div>
        <div class="w-full md:w-48">
            <select name="city_filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('suppliers.all_cities') }}</option>
                @foreach ($cities as $city)
                    <option value="{{ $city }}" {{ request('city_filter') == $city ? 'selected' : '' }}>
                        {{ $city }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-48">
            <select name="sphere_filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('suppliers.all_spheres') }}</option>
                @foreach ($spheres as $sphere)
                    <option value="{{ $sphere }}" {{ request('sphere_filter') == $sphere ? 'selected' : '' }}>
                        {{ $sphere }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full md:w-48">
            <select name="brand_filter"
                class="w-full px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                <option value="">{{ __('suppliers.all_brands') }}</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}" {{ request('brand_filter') == $brand ? 'selected' : '' }}>
                        {{ $brand }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="filter-btn hidden md:block">{{ __('suppliers.search') }}</button>
    </form>

    <!-- Контент вкладок -->
    <div id="table-view" class="tab-content">
        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header {{ request('sort_by') === 'name' ? (request('sort_dir', 'asc') === 'asc' ? 'asc' : 'desc') : '' }}"
                                data-sort="name">{{ __('suppliers.name') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header {{ request('sort_by') === 'phone' ? (request('sort_dir', 'asc') === 'asc' ? 'asc' : 'desc') : '' }}"
                                data-sort="phone">{{ __('suppliers.phone') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header {{ request('sort_by') === 'website' ? (request('sort_dir', 'asc') === 'asc' ? 'asc' : 'desc') : '' }}"
                                data-sort="website">{{ __('suppliers.website') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header {{ request('sort_by') === 'city' ? (request('sort_dir', 'asc') === 'asc' ? 'asc' : 'desc') : '' }}"
                                data-sort="city">{{ __('suppliers.city') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A] sortable-header {{ request('sort_by') === 'sphere' ? (request('sort_dir', 'asc') === 'asc' ? 'asc' : 'desc') : '' }}"
                                data-sort="sphere">{{ __('suppliers.sphere') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('suppliers.brand') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">
                                {{ __('suppliers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody id="suppliers-table-body" class="divide-y divide-[#e2e8f0] dark:divide-[#3E3E3A]">
                        @forelse($suppliers as $supplier)
                            <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-supplier-id="{{ $supplier->id }}">
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplier->name }}</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $supplier->phone ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($supplier->website)
                                        <a href="{{ $supplier->website }}" target="_blank"
                                            class="text-[#f59e0b] hover:underline">{{ $supplier->website }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $supplier->city ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $supplier->sphere ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">
                                    {{ $supplier->brand_display ?: '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="viewSupplier({{ $supplier->id }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('suppliers.view') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="editSupplier({{ $supplier->id }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('suppliers.edit') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="addOrderFromSupplier({{ $supplier->id }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors"
                                            title="{{ __('suppliers.add_order') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="deleteSupplier({{ $supplier->id }})"
                                            class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 transition-colors"
                                            title="{{ __('suppliers.delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <button type="button" onclick="toggleFavorite({{ $supplier->id }}, this)"
                                            class="p-1.5 rounded favorite-btn {{ $supplier->is_favorite ? 'active' : '' }}"
                                            title="{{ $supplier->is_favorite ? __('suppliers.remove_favorite') : __('suppliers.add_favorite') }}">
                                            <svg class="w-4 h-4"
                                                fill="{{ $supplier->is_favorite ? 'currentColor' : 'none' }}"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">
                                    {{ __('suppliers.no_suppliers') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Пагинация -->
            <div class="pagination mt-4" id="suppliers-pagination-table"></div>
        </div>
    </div>

    <div id="list-view" class="tab-content hidden">
        <div class="space-y-4" id="suppliers-list-body">
            @foreach ($suppliers as $supplier)
                <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6"
                    data-supplier-id="{{ $supplier->id }}">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">{{ $supplier->name }}
                            </h3>
                            <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                <p>{{ $supplier->phone ?? '-' }}</p>
                                @if ($supplier->website)
                                    <p><a href="{{ $supplier->website }}" target="_blank"
                                            class="text-[#f59e0b] hover:underline">{{ $supplier->website }}</a></p>
                                @endif
                                <p>{{ $supplier->city ?? '-' }}</p>
                            </div>
                        </div>
                        <button type="button" onclick="toggleFavorite({{ $supplier->id }}, this)"
                            class="p-2 rounded favorite-btn {{ $supplier->is_favorite ? 'active' : '' }}"
                            title="{{ $supplier->is_favorite ? __('suppliers.remove_favorite') : __('suppliers.add_favorite') }}">
                            <svg class="w-5 h-5" fill="{{ $supplier->is_favorite ? 'currentColor' : 'none' }}"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4 text-sm">
                        <div><span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.sphere') }}:</span> <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ $supplier->sphere ?? '-' }}</span>
                        </div>
                        <div><span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.brand') }}:</span> <span
                                class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">{{ $supplier->brand_display ?: '-' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="viewSupplier({{ $supplier->id }})"
                            class="filter-btn">{{ __('suppliers.view') }}</button>
                        <button type="button" onclick="editSupplier({{ $supplier->id }})"
                            class="filter-btn">{{ __('suppliers.edit') }}</button>
                        <button type="button" onclick="deleteSupplier({{ $supplier->id }})"
                            class="filter-btn text-red-500 hover:text-red-600">{{ __('suppliers.delete') }}</button>
                        <button type="button" onclick="addOrderFromSupplier({{ $supplier->id }})"
                            class="filter-btn">{{ __('suppliers.add_order') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pagination mt-4" id="suppliers-pagination-list"></div>
    </div>

    <div class="mt-3 flex justify-end">
        <div class="w-full md:w-56 shrink-0">
            <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('objects.per_page') }}</div>
            <div class="relative">
                <button id="suppliers-per-page-button" type="button"
                    class="w-full flex items-center justify-between px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
                    <span id="suppliers-per-page-label">10</span>
                    <svg class="w-4 h-4 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="suppliers-per-page-menu"
                    class="hidden absolute left-0 right-0 mt-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg overflow-hidden z-[60]">
                    @foreach ([10, 30, 50, 100] as $v)
                        <button type="button"
                            class="w-full px-4 py-2 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a] transition-colors text-left suppliers-per-page-option"
                            data-value="{{ $v }}">
                            <span class="suppliers-per-page-check hidden mr-2 items-center">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 6L9 17l-5-5" />
                                </svg>
                            </span>
                            {{ $v }}
                        </button>
                    @endforeach
                </div>

                <select id="suppliers-per-page" class="hidden">
                    <option value="10" selected>10</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Модалка просмотра поставщика (справа) -->
    <div id="view-supplier-modal" class="fixed inset-0 bg-black/50 z-50 hidden modal-overlay"
        onmousedown="if(event.target === this) closeViewSupplierModal()">
        <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-[#161615] border-l border-[#e2e8f0] dark:border-[#3E3E3A] shadow-2xl transform transition-transform duration-300 translate-x-full modal-content"
            onclick="event.stopPropagation()">
            <div class="flex flex-col h-full">
                <div
                    class="flex items-center justify-between px-6 py-5 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.view') }}
                        </h2>
                        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-0.5">{{ __('suppliers.view') }}
                            {{ __('suppliers.supplier') }}</p>
                    </div>
                    <button onclick="closeViewSupplierModal()"
                        class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-supplier-content" class="flex-1 overflow-y-auto p-6 space-y-5"></div>
            </div>
        </div>
    </div>

    <!-- Модалка добавления/редактирования поставщика -->
    <div id="supplier-modal"
        class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center modal-overlay p-4"
        onmousedown="if(event.target === this) closeSupplierModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full mx-auto max-h-[90vh] overflow-hidden flex flex-col modal-content border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div
                class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] shrink-0">
                <div>
                    <h2 class="text-xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="supplier-modal-title">
                        {{ __('suppliers.new_supplier') }}</h2>
                    <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">
                        {{ __('suppliers.supplier_modal_subtitle') }}</p>
                </div>
                <button type="button" onclick="closeSupplierModal()"
                    class="p-2 rounded-lg text-[#64748b] dark:text-[#A1A09A] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A] hover:text-[#0f172a] dark:hover:text-[#EDEDEC] transition-all duration-200 hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="supplier-form" class="flex flex-col flex-1 min-h-0" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="supplier_id" id="supplier_id">
                <input type="hidden" name="remove_logo" id="remove_logo" value="0">
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">

                    <!-- Секция 1: Основная информация -->
                    <div class="accordion-section">
                        <button type="button" class="accordion-header" aria-expanded="true"
                            aria-controls="accordion-main" id="accordion-main-btn"
                            onclick="toggleAccordion('accordion-main')">
                            {{ __('suppliers.main_info') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="accordion-content accordion-open" id="accordion-main" aria-hidden="false">
                            <div class="accordion-body space-y-5">
                                <div class="flex items-start gap-4">
                                    <div class="relative w-24 h-24 rounded-full bg-[#f1f5f9] dark:bg-[#0a0a0a] border-2 border-dashed border-[#e2e8f0] dark:border-[#3E3E3A] flex items-center justify-center flex-shrink-0 overflow-hidden cursor-pointer group"
                                        id="logo-preview" onclick="window.handleLogoPreviewClick(event)"
                                        title="{{ __('suppliers.upload') }}">
                                        <img id="logo-preview-img" src="" alt=""
                                            class="hidden w-full h-full object-cover">
                                        <span id="logo-preview-placeholder"
                                            class="text-3xl text-[#94a3b8] dark:text-[#71716c] group-hover:text-[#f59e0b] transition-colors">+</span>
                                        <span id="logo-edit-hint"
                                            class="hidden absolute inset-0 bg-black/40 rounded-full flex items-center justify-center text-white text-xs font-medium opacity-0 group-hover:opacity-100 transition-opacity">{{ __('suppliers.edit') }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <label class="modal-label">{{ __('suppliers.logo') }}</label>
                                        <div class="flex items-center gap-3 mt-1">
                                            <input type="file" name="logo" id="logo-file-input"
                                                accept="image/jpeg,image/gif,image/png,image/webp" class="hidden">
                                            <label for="logo-file-input"
                                                class="px-4 py-2 rounded-lg border border-[#e2e8f0] dark:border-[#3E3E3A] text-sm font-medium text-[#64748b] dark:text-[#A1A09A] cursor-pointer hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                                {{ __('suppliers.upload') }}
                                            </label>
                                            <button type="button" id="logo-remove-btn"
                                                class="text-sm text-red-500 hover:underline hidden">{{ __('suppliers.remove') }}</button>
                                        </div>
                                        <p class="modal-helper">{{ __('suppliers.logo_hint') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label modal-label-required">{{ __('suppliers.name') }}</label>
                                    <input type="text" name="name" required class="modal-input"
                                        placeholder="{{ __('suppliers.name_placeholder') }}">
                                    <p class="modal-helper">{{ __('suppliers.name_helper') }}</p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="recommend" id="recommend"
                                        class="rounded border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                    <label for="recommend"
                                        class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.recommend_supplier') }}</label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.phone') }}</label>
                                        <input type="text" inputmode="tel" name="phone" id="supplier-phone"
                                            class="modal-input" placeholder="+7 700 123 45 67" autocomplete="tel">
                                        <p class="modal-helper">{{ __('suppliers.phone_helper') }}</p>
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.email') }}</label>
                                        <input type="email" name="email" class="modal-input"
                                            placeholder="{{ __('suppliers.email_placeholder') }}">
                                        <p class="modal-helper">{{ __('suppliers.email_helper') }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">Telegram</label>
                                        <div class="input-with-icon">
                                            <span class="input-icon text-[#0088cc]"><svg class="w-5 h-5"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                                </svg></span>
                                            <input type="text" name="telegram" id="supplier-telegram"
                                                class="modal-input" placeholder="@username или +7 700 123 45 67">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="modal-label">WhatsApp</label>
                                        <div class="input-with-icon">
                                            <span class="input-icon text-[#25D366]"><svg class="w-5 h-5"
                                                    viewBox="0 0 24 24" fill="currentColor">
                                                    <path
                                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                                </svg></span>
                                            <input type="text" name="whatsapp" id="supplier-whatsapp"
                                                class="modal-input" placeholder="+7 700 123 45 67" inputmode="tel">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.website') }}</label>
                                        <input type="url" name="website" class="modal-input"
                                            placeholder="Введите сайт">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.city') }}</label>
                                        <input type="text" name="city" class="modal-input"
                                            placeholder="{{ __('suppliers.city_placeholder') }}">
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.address') }}</label>
                                    <input type="text" name="address" class="modal-input"
                                        placeholder="{{ __('suppliers.address_placeholder') }}">
                                    <p class="modal-helper">{{ __('suppliers.address_helper') }}</p>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.sphere_activity') }}</label>
                                    <select name="sphere" class="modal-input">
                                        <option value="">{{ __('suppliers.sphere_placeholder') }}</option>
                                        @foreach ((array) ($sphereOptions ?? []) as $sphere)
                                            <option value="{{ is_object($sphere) ? ($sphere->key ?? '') : (is_array($sphere) ? ($sphere['key'] ?? '') : '') }}">
                                                {{ is_object($sphere) ? ($sphere->name ?? '') : (is_array($sphere) ? ($sphere['name'] ?? '') : '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.work_terms') }}</label>
                                        <select name="work_terms_type" class="modal-input">
                                            <option value="percent">{{ __('suppliers.work_terms_percent') }}</option>
                                            <option value="amount">{{ __('suppliers.work_terms_amount') }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.value') }}</label>
                                        <input type="text" name="work_terms_value" class="modal-input"
                                            placeholder="{{ __('suppliers.value_placeholder') }}">
                                        <p class="modal-helper">{{ __('suppliers.value_helper') }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.brands') }}</label>
                                    <p class="modal-helper mb-2">{{ __('suppliers.brands_helper') }}</p>
                                    <div id="brands-tags" class="flex flex-wrap gap-2 mb-2"></div>
                                    <div class="flex gap-2">
                                        <input type="text" name="brand_input" id="brand_input"
                                            class="modal-input flex-1"
                                            placeholder="{{ __('suppliers.brand_placeholder') }}">
                                        <button type="button" id="add-brand-btn"
                                            class="accordion-add-btn text-sm font-medium text-[#f59e0b] hover:underline whitespace-nowrap">+
                                            {{ __('suppliers.add') }}</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.cities_presence') }}</label>
                                    <p class="modal-helper mb-2">{{ __('suppliers.cities_helper') }}</p>
                                    <div id="cities-tags" class="flex flex-wrap gap-2 mb-2"></div>
                                    <div class="flex gap-2">
                                        <input type="text" name="city_input" id="city_input"
                                            class="modal-input flex-1"
                                            placeholder="{{ __('suppliers.city_placeholder') }}">
                                        <button type="button" id="add-city-btn"
                                            class="accordion-add-btn text-sm font-medium text-[#f59e0b] hover:underline whitespace-nowrap">+
                                            {{ __('suppliers.add') }}</button>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.comment') }}</label>
                                    <input type="text" name="comment_main" class="modal-input"
                                        placeholder="{{ __('suppliers.comment_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция 2: Реквизиты -->
                    <div class="accordion-section">
                        <button type="button" class="accordion-header" aria-expanded="false"
                            aria-controls="accordion-requisites" onclick="toggleAccordion('accordion-requisites')">
                            {{ __('suppliers.requisites') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="accordion-content" id="accordion-requisites" aria-hidden="true">
                            <div class="accordion-body space-y-5">
                                <div>
                                    <label class="modal-label">{{ __('suppliers.org_form') }}</label>
                                    <div class="flex gap-6 mt-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="org_form" value="ooo" checked
                                                class="text-[#f59e0b] focus:ring-[#f59e0b]">
                                            <span
                                                class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.org_ooo') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="org_form" value="ip"
                                                class="text-[#f59e0b] focus:ring-[#f59e0b]">
                                            <span
                                                class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.org_ip') }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.inn') }}</label>
                                    <input type="text" name="inn" id="supplier-inn" class="modal-input"
                                        placeholder="000000000000" maxlength="12" inputmode="numeric" pattern="[0-9]*"
                                        oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.kpp') }}</label>
                                        <input type="text" name="kpp" id="supplier-kpp" class="modal-input"
                                            placeholder="000000000" maxlength="9" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.ogrn') }}</label>
                                        <input type="text" name="ogrn" id="supplier-ogrn" class="modal-input"
                                            placeholder="0000000000000" maxlength="15" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                    <div>
                                        <label class="modal-label">{{ __('suppliers.okpo') }}</label>
                                        <input type="text" name="okpo" id="supplier-okpo" class="modal-input"
                                            placeholder="00000000" maxlength="10" inputmode="numeric"
                                            oninput="this.value=this.value.replace(/\D/g,'')">
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.legal_address') }}</label>
                                    <input type="text" name="legal_address" class="modal-input"
                                        placeholder="{{ __('suppliers.legal_address_placeholder') }}">
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.actual_address') }}</label>
                                    <input type="text" name="actual_address" class="modal-input"
                                        placeholder="{{ __('suppliers.actual_address_placeholder') }}">
                                    <div class="flex items-center gap-2 mt-2">
                                        <input type="checkbox" name="address_match" id="address_match"
                                            class="rounded border-[#e2e8f0] dark:border-[#3E3E3A] text-[#f59e0b] focus:ring-[#f59e0b]">
                                        <label for="address_match"
                                            class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.match_legal') }}</label>
                                    </div>
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.director') }}</label>
                                    <input type="text" name="director" class="modal-input"
                                        placeholder="{{ __('suppliers.director_placeholder') }}">
                                </div>

                                <div>
                                    <label class="modal-label">{{ __('suppliers.accountant') }}</label>
                                    <input type="text" name="accountant" class="modal-input"
                                        placeholder="{{ __('suppliers.accountant_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Секция 3: Банковские реквизиты -->
                    <div class="accordion-section">
                        <button type="button" class="accordion-header" aria-expanded="false"
                            aria-controls="accordion-bank" onclick="toggleAccordion('accordion-bank')">
                            {{ __('suppliers.bank_details') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="accordion-content" id="accordion-bank" aria-hidden="true">
                            <div class="accordion-body space-y-5">
                                <div>
                                    <label class="modal-label">{{ __('suppliers.bik') }}</label>
                                    <input type="text" name="bik" id="supplier-bik" class="modal-input"
                                        placeholder="00000000" maxlength="8" inputmode="numeric"
                                        oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.bank') }}</label>
                                    <input type="text" name="bank" class="modal-input"
                                        placeholder="{{ __('suppliers.bank_placeholder') }}">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.checking_account') }}</label>
                                    <input type="text" name="checking_account" id="supplier-checking-account"
                                        class="modal-input" placeholder="00000000000000000000" maxlength="20"
                                        inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.corr_account') }}</label>
                                    <input type="text" name="corr_account" id="supplier-corr-account"
                                        class="modal-input" placeholder="00000000000000000000" maxlength="20"
                                        inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')">
                                </div>
                                <div>
                                    <label class="modal-label">{{ __('suppliers.comment') }}</label>
                                    <input type="text" name="comment_bank" class="modal-input"
                                        placeholder="{{ __('suppliers.comment_placeholder') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer flex-col sm:flex-row gap-3">
                    <button type="submit" id="supplier-submit-btn"
                        class="add-btn w-full sm:w-auto">{{ __('suppliers.add_supplier') }}</button>
                    <button type="button" onclick="closeSupplierModal()"
                        class="btn-secondary w-full sm:w-auto flex items-center justify-center gap-2 transition-all duration-200 hover:gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ __('suppliers.go_back') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модалка обрезки логотипа -->
    <div id="logo-crop-modal" class="fixed inset-0 bg-black/70 z-[60] hidden items-center justify-center p-4"
        onmousedown="if(event.target === this) closeLogoCropModal()">
        <div class="bg-white dark:bg-[#161615] rounded-xl max-w-2xl w-full max-h-[90vh] flex flex-col border border-[#e2e8f0] dark:border-[#3E3E3A]"
            onclick="event.stopPropagation()">
            <div class="px-6 py-4 border-b border-[#e2e8f0] dark:border-[#3E3E3A] flex items-center justify-between">
                <h3 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('suppliers.crop_logo') }}</h3>
                <button type="button" onclick="closeLogoCropModal()"
                    class="p-2 rounded-lg text-[#64748b] hover:bg-[#e2e8f0] dark:hover:bg-[#3E3E3A]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-4 flex-1 min-h-0 overflow-hidden">
                <div class="max-h-[60vh] bg-[#0a0a0a] rounded-lg overflow-hidden">
                    <img id="logo-crop-image" src="" alt="Crop" class="max-w-full max-h-[60vh]">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-[#e2e8f0] dark:border-[#3E3E3A] flex gap-3 justify-end">
                <button type="button" onclick="closeLogoCropModal()"
                    class="btn-secondary">{{ __('suppliers.cancel') }}</button>
                <button type="button" id="logo-crop-apply" class="add-btn">{{ __('suppliers.apply') }}</button>
            </div>
        </div>
    </div>

    <script>
        function toggleAccordion(id) {
            const content = document.getElementById(id);
            const btn = document.querySelector('[aria-controls="' + id + '"]');
            const isOpen = content.classList.contains('accordion-open');
            content.classList.toggle('accordion-open', !isOpen);
            content.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
            if (btn) btn.setAttribute('aria-expanded', !isOpen);
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Переключение вкладок
            document.querySelectorAll('[data-tab]').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('[data-tab]').forEach(b => b.classList.remove(
                        'active'));
                    this.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.add(
                        'hidden'));
                    document.getElementById(this.dataset.tab + '-view').classList.remove('hidden');
                });
            });

            // Brands / Cities add buttons
            const addBrand = () => {
                const inp = document.getElementById('brand_input');
                const val = inp?.value?.trim();
                if (!val) return;
                const tags = document.getElementById('brands-tags');
                const span = document.createElement('span');
                span.className =
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm text-[#0f172a] dark:text-[#EDEDEC]';
                span.innerHTML = val +
                    ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                const hi = document.createElement('input');
                hi.type = 'hidden';
                hi.name = 'brands[]';
                hi.value = val;
                span.appendChild(hi);
                tags.appendChild(span);
                inp.value = '';
            };
            const addCity = () => {
                const inp = document.getElementById('city_input');
                const val = inp?.value?.trim();
                if (!val) return;
                const tags = document.getElementById('cities-tags');
                const span = document.createElement('span');
                span.className =
                    'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm text-[#0f172a] dark:text-[#EDEDEC]';
                span.innerHTML = val +
                    ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                const hi = document.createElement('input');
                hi.type = 'hidden';
                hi.name = 'cities_presence[]';
                hi.value = val;
                span.appendChild(hi);
                tags.appendChild(span);
                inp.value = '';
            };
            document.getElementById('add-brand-btn')?.addEventListener('click', addBrand);
            document.getElementById('add-city-btn')?.addEventListener('click', addCity);

            // Инициализация масок при первом открытии формы
            initSupplierMasks();

            // Кнопка добавления поставщика
            document.getElementById('add-supplier-btn')?.addEventListener('click', () => {
                document.getElementById('supplier-modal').classList.remove('hidden');
                document.getElementById('supplier-modal').classList.add('flex');
                document.getElementById('supplier-form').reset();
                document.getElementById('supplier_id').value = '';
                document.getElementById('supplier-modal-title').textContent =
                    '{{ __('suppliers.new_supplier') }}';
                document.getElementById('supplier-submit-btn').textContent =
                    '{{ __('suppliers.add_supplier') }}';
                document.getElementById('brands-tags').innerHTML = '';
                document.getElementById('cities-tags').innerHTML = '';
                window.resetLogoPreview();
                if (supplierMasks.phone) {
                    supplierMasks.phone.value = '';
                    supplierMasks.whatsapp.value = '';
                }
            });

            // Логотип: выбор файла → открыть кроппер
            document.getElementById('logo-file-input')?.addEventListener('change', function(e) {
                const file = e.target.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;
                window.openLogoCropModal(file);
            });

            // Кнопка удаления логотипа
            document.getElementById('logo-remove-btn')?.addEventListener('click', function(e) {
                e.stopPropagation();
                window.resetLogoPreview(true);
            });
        });
    </script>
    <script>
        window.allSuppliers = @json($suppliersData ?? []);
        (function() {
            const searchForm = document.getElementById('search-form');
            const tableBody = document.getElementById('suppliers-table-body');
            const listBody = document.getElementById('suppliers-list-body');
            const tablePagination = document.getElementById('suppliers-pagination-table');
            const listPagination = document.getElementById('suppliers-pagination-list');
            const searchInput = searchForm?.querySelector('input[name="search"]');
            const typeFilter = searchForm?.querySelector('select[name="type_filter"]');
            const cityFilter = searchForm?.querySelector('select[name="city_filter"]');
            const sphereFilter = searchForm?.querySelector('select[name="sphere_filter"]');
            const brandFilter = searchForm?.querySelector('select[name="brand_filter"]');
            const perPageSelect = document.getElementById('suppliers-per-page');
            const perPageButton = document.getElementById('suppliers-per-page-button');
            const perPageLabel = document.getElementById('suppliers-per-page-label');
            const perPageMenu = document.getElementById('suppliers-per-page-menu');

            let currentPage = 1;
            let itemsPerPage = 10;
            let sortColumn = null;
            let sortDirection = 'asc';

            if (perPageSelect) {
                itemsPerPage = parseInt(perPageSelect.value, 10) || 10;
            }
            if (perPageLabel) {
                perPageLabel.textContent = String(itemsPerPage);
            }

            const setActivePerPage = (value) => {
                if (!perPageMenu) return;
                perPageMenu.querySelectorAll('.suppliers-per-page-option').forEach(btn => {
                    const isActive = parseInt(btn.dataset.value, 10) === value;
                    btn.classList.toggle('bg-[#fef3c7]', isActive);
                    btn.classList.toggle('dark:bg-[#1D0002]', isActive);
                    btn.classList.toggle('text-[#f59e0b]', isActive);
                    btn.classList.toggle('dark:text-[#f59e0b]', isActive);
                    const check = btn.querySelector('.suppliers-per-page-check');
                    if (check) {
                        check.classList.toggle('hidden', !isActive);
                        check.classList.toggle('inline-flex', isActive);
                    }
                });
            };
            setActivePerPage(itemsPerPage);

            if (perPageButton && perPageMenu) {
                perPageButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    perPageMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', function() {
                    if (!perPageMenu.classList.contains('hidden')) perPageMenu.classList.add('hidden');
                });
                perPageMenu.querySelectorAll('.suppliers-per-page-option').forEach(btn => {
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
                        renderActiveTab();
                    });
                });
            }

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

            function filteredSuppliers() {
                const search = (searchInput?.value || '').trim().toLowerCase();
                const tf = typeFilter?.value || 'all';
                const cf = cityFilter?.value || '';
                const sf = sphereFilter?.value || '';
                const bf = brandFilter?.value || '';

                let data = (window.allSuppliers || []).filter((s) => {
                    const hay = [
                        s.name, s.phone, s.email, s.website, s.city, s.sphere, s.address, s.comment, s.brand_display
                    ].filter(Boolean).join(' ').toLowerCase();
                    const bySearch = !search || hay.includes(search);
                    const byType = tf === 'all' || (tf === 'recommended' && !!s.recommend) || (tf === 'favorites' && !!s.is_favorite);
                    const byCity = !cf || (s.city || '') === cf;
                    const bySphere = !sf || (s.sphere || '') === sf;
                    const brands = Array.isArray(s.brands) ? s.brands : [];
                    const byBrand = !bf || brands.includes(bf);
                    return bySearch && byType && byCity && bySphere && byBrand;
                });

                if (sortColumn) {
                    const dir = sortDirection === 'asc' ? 1 : -1;
                    data = data.slice().sort((a, b) => {
                        const av = String(a?.[sortColumn] ?? '').toLowerCase();
                        const bv = String(b?.[sortColumn] ?? '').toLowerCase();
                        if (av === bv) return 0;
                        return av > bv ? dir : -dir;
                    });
                }

                return data;
            }

            function renderPagination(container, total, onChange) {
                if (!container) return;
                if (total <= 1) {
                    container.innerHTML = '';
                    return;
                }
                let html = `<button type="button" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''}>{{ __('objects.prev') }}</button>`;
                for (let i = 1; i <= total; i++) {
                    html += `<button type="button" data-page="${i}" class="${i === currentPage ? 'active' : ''}" ${i === currentPage ? 'disabled' : ''}>${i}</button>`;
                }
                html += `<button type="button" data-page="${currentPage + 1}" ${currentPage >= total ? 'disabled' : ''}>{{ __('objects.next') }}</button>`;
                container.innerHTML = html;
                container.querySelectorAll('button[data-page]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        if (btn.disabled) return;
                        const p = parseInt(btn.dataset.page, 10);
                        if (!Number.isFinite(p)) return;
                        currentPage = p;
                        onChange();
                    });
                });
            }

            function renderTable() {
                if (!tableBody) return;
                const data = filteredSuppliers();
                const totalPages = Math.max(1, Math.ceil(data.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const paged = data.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);
                if (!paged.length) {
                    tableBody.innerHTML = `<tr><td colspan="7" class="px-4 py-8 text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.no_suppliers') }}</td></tr>`;
                } else {
                    tableBody.innerHTML = paged.map((s) => `
                        <tr class="hover:bg-[#f8fafc] dark:hover:bg-[#0a0a0a]" data-supplier-id="${s.id}">
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.name || '')}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.phone || '-')}</td>
                            <td class="px-4 py-3 text-sm">${s.website ? `<a href="${escapeHtml(s.website)}" target="_blank" class="text-[#f59e0b] hover:underline">${escapeHtml(s.website)}</a>` : '-'}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.city || '-')}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.sphere || '-')}</td>
                            <td class="px-4 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${escapeHtml(s.brand_display || '-')}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <button type="button" title="{{ __('suppliers.view') }}" onclick="viewSupplier(${s.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="{{ __('suppliers.edit') }}" onclick="editSupplier(${s.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button type="button" title="{{ __('suppliers.delete') }}" onclick="deleteSupplier(${s.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <button type="button" title="{{ __('suppliers.add_order') }}" onclick="addOrderFromSupplier(${s.id})"
                                        class="p-1.5 rounded text-[#64748b] dark:text-[#A1A09A] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] hover:text-[#f59e0b] transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                    <button type="button" title="${s.is_favorite ? '{{ __('suppliers.remove_favorite') }}' : '{{ __('suppliers.add_favorite') }}'}" onclick="toggleFavorite(${s.id}, this)"
                                        class="p-1.5 rounded favorite-btn ${s.is_favorite ? 'active' : ''}">
                                        <svg class="w-4 h-4" fill="${s.is_favorite ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
                renderPagination(tablePagination, totalPages, renderActiveTab);
            }

            function renderList() {
                if (!listBody) return;
                const data = filteredSuppliers();
                const totalPages = Math.max(1, Math.ceil(data.length / itemsPerPage));
                if (currentPage > totalPages) currentPage = 1;
                const paged = data.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);
                if (!paged.length) {
                    listBody.innerHTML = `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.no_suppliers') }}</div>`;
                } else {
                    listBody.innerHTML = paged.map((s) => `
                        <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6" data-supplier-id="${s.id}">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${escapeHtml(s.name || '')}</h3>
                                    <div class="space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                                        <p>${escapeHtml(s.phone || '-')}</p>
                                        ${s.website ? `<p><a href="${escapeHtml(s.website)}" target="_blank" class="text-[#f59e0b] hover:underline">${escapeHtml(s.website)}</a></p>` : ''}
                                        <p>${escapeHtml(s.city || '-')}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="toggleFavorite(${s.id}, this)" class="p-2 rounded favorite-btn ${s.is_favorite ? 'active' : ''}">★</button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4 text-sm">
                                <div><span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.sphere') }}:</span> <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${escapeHtml(s.sphere || '-')}</span></div>
                                <div><span class="text-[#64748b] dark:text-[#A1A09A]">{{ __('suppliers.brand') }}:</span> <span class="text-[#0f172a] dark:text-[#EDEDEC] font-medium ml-2">${escapeHtml(s.brand_display || '-')}</span></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="viewSupplier(${s.id})" class="filter-btn">{{ __('suppliers.view') }}</button>
                                <button type="button" onclick="editSupplier(${s.id})" class="filter-btn">{{ __('suppliers.edit') }}</button>
                                <button type="button" onclick="deleteSupplier(${s.id})" class="filter-btn text-red-500 hover:text-red-600">{{ __('suppliers.delete') }}</button>
                                <button type="button" onclick="addOrderFromSupplier(${s.id})" class="filter-btn">{{ __('suppliers.add_order') }}</button>
                            </div>
                        </div>
                    `).join('');
                }
                renderPagination(listPagination, totalPages, renderActiveTab);
            }

            function updateSortHeaders() {
                document.querySelectorAll('.sortable-header').forEach((h) => {
                    h.classList.remove('asc', 'desc');
                    if (h.dataset.sort === sortColumn) h.classList.add(sortDirection);
                });
            }

            function renderActiveTab() {
                const currentTab = document.querySelector('[data-tab].active')?.dataset.tab || 'table';
                if (currentTab === 'table') renderTable();
                if (currentTab === 'list') renderList();
            }
            window.renderSuppliersActiveTab = renderActiveTab;

            searchForm?.addEventListener('submit', (e) => {
                e.preventDefault();
                currentPage = 1;
                renderActiveTab();
            });
            [searchInput, typeFilter, cityFilter, sphereFilter, brandFilter].forEach((el) => {
                if (!el) return;
                const evt = el === searchInput ? 'input' : 'change';
                el.addEventListener(evt, () => {
                    currentPage = 1;
                    renderActiveTab();
                });
            });

            document.querySelectorAll('.sortable-header[data-sort]').forEach((header) => {
                header.addEventListener('click', function() {
                    const col = this.dataset.sort;
                    if (!col) return;
                    if (sortColumn === col) {
                        sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortColumn = col;
                        sortDirection = 'asc';
                    }
                    currentPage = 1;
                    updateSortHeaders();
                    renderActiveTab();
                });
            });

            updateSortHeaders();
            renderActiveTab();
        })();
    </script>
    <script src="https://unpkg.com/imask@7.6.1/dist/imask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        const TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector(
            'input[name="_token"]')?.value;
        const PHONE_MASK = '+7 000 000 00 00';
        const supplierMasks = {};

        function initSupplierMasks() {
            if (supplierMasks.phone) return;
            const phoneEl = document.getElementById('supplier-phone');
            const whatsappEl = document.getElementById('supplier-whatsapp');
            if (phoneEl && typeof IMask !== 'undefined') {
                supplierMasks.phone = IMask(phoneEl, {
                    mask: PHONE_MASK,
                    lazy: false
                });
                supplierMasks.whatsapp = IMask(whatsappEl, {
                    mask: PHONE_MASK,
                    lazy: false
                });
            }
        }

        function setPhoneMaskValue(mask, val) {
            if (!mask) return;
            const digits = val ? String(val).replace(/\D/g, '').replace(/^8/, '7').slice(-10) : '';
            mask.unmaskedValue = digits;
        }
        let logoCropper = null;
        let croppedLogoBlob = null;
        let logoCropObjectUrl = null;

        function updateLogoPreview(src) {
            const img = document.getElementById('logo-preview-img');
            const placeholder = document.getElementById('logo-preview-placeholder');
            const removeBtn = document.getElementById('logo-remove-btn');
            const editHint = document.getElementById('logo-edit-hint');
            if (src) {
                img.src = typeof src === 'string' ? src : URL.createObjectURL(src);
                img.classList.remove('hidden');
                placeholder.classList.add('hidden');
                if (removeBtn) removeBtn.classList.remove('hidden');
                if (editHint) editHint.classList.remove('hidden');
            } else {
                img.src = '';
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
                if (removeBtn) removeBtn.classList.add('hidden');
                if (editHint) editHint.classList.add('hidden');
            }
        }

        window.resetLogoPreview = function(removeExisting) {
            croppedLogoBlob = null;
            const fileInput = document.getElementById('logo-file-input');
            if (fileInput) fileInput.value = '';
            document.getElementById('remove_logo').value = removeExisting ? '1' : '0';
            updateLogoPreview(null);
        };

        function openLogoCropModalWithSource(src) {
            if (logoCropObjectUrl) {
                URL.revokeObjectURL(logoCropObjectUrl);
                logoCropObjectUrl = null;
            }
            const modal = document.getElementById('logo-crop-modal');
            const cropImg = document.getElementById('logo-crop-image');
            if (!modal || !cropImg) return;
            cropImg.src = typeof src === 'string' ? src : (logoCropObjectUrl = URL.createObjectURL(src));
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                if (logoCropper) logoCropper.destroy();
                logoCropper = new Cropper(cropImg, {
                    aspectRatio: 1,
                    viewMode: 2,
                    dragMode: 'move'
                });
            }, 50);
        }

        function openLogoCropModal(file) {
            const reader = new FileReader();
            reader.onload = function() {
                openLogoCropModalWithSource(reader.result);
            };
            reader.readAsDataURL(file);
        }

        window.handleLogoPreviewClick = function(e) {
            if (e.target.closest('#logo-remove-btn')) return;
            const img = document.getElementById('logo-preview-img');
            if (img && !img.classList.contains('hidden') && img.src) {
                openLogoCropModalWithSource(croppedLogoBlob || img.src);
            } else {
                document.getElementById('logo-file-input').click();
            }
        };

        function closeLogoCropModal() {
            const modal = document.getElementById('logo-crop-modal');
            if (logoCropper) {
                logoCropper.destroy();
                logoCropper = null;
            }
            if (logoCropObjectUrl) {
                URL.revokeObjectURL(logoCropObjectUrl);
                logoCropObjectUrl = null;
            }
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        }

        document.getElementById('logo-crop-apply')?.addEventListener('click', function() {
            if (!logoCropper) return;
            const canvas = logoCropper.getCroppedCanvas({
                width: 400,
                height: 400
            });
            canvas.toBlob(function(blob) {
                croppedLogoBlob = blob;
                updateLogoPreview(blob);
                closeLogoCropModal();
            }, 'image/jpeg', 0.9);
        });

        function closeViewSupplierModal() {
            const modal = document.getElementById('view-supplier-modal');
            const panel = modal.querySelector('div[class*="absolute"]');
            modal.classList.add('hidden');
            if (panel) {
                panel.classList.add('translate-x-full');
                panel.classList.remove('translate-x-0');
            }
        }

        async function viewSupplier(id) {
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const s = await r.json();
                const content = document.getElementById('view-supplier-content');
                const brands = Array.isArray(s.brands) ? s.brands.join(', ') : (s.brands || '-');
                const cities = Array.isArray(s.cities_presence) ? s.cities_presence.join(', ') : (s.cities_presence ||
                    '-');
                const logoHtml = s.logo_url ?
                    `<div class="mb-4"><img src="${s.logo_url}" alt="Logo" class="w-20 h-20 rounded-full object-cover border-2 border-[#e2e8f0] dark:border-[#3E3E3A]"></div>` :
                    '';
                content.innerHTML = logoHtml + `
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.name') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${s.name || '-'}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.phone') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${s.phone || '-'}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.website') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${s.website ? '<a href="'+s.website+'" target="_blank" class="text-[#f59e0b] hover:underline">'+s.website+'</a>' : '-'}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.city') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${s.city || '-'}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.sphere') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${s.sphere || '-'}</p></div>
            <div><label class="block text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-1">{{ __('suppliers.brand') }}</label><p class="text-[#0f172a] dark:text-[#EDEDEC]">${brands}</p></div>
        `;
                const modal = document.getElementById('view-supplier-modal');
                const panel = modal?.querySelector('div[class*="absolute"]');
                modal.classList.remove('hidden');
                setTimeout(() => {
                    if (panel) {
                        panel.classList.remove('translate-x-full');
                        panel.classList.add('translate-x-0');
                    }
                }, 10);
            } catch (e) {
                console.error(e);
            }
        }

        async function editSupplier(id) {
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const s = await r.json();
                document.getElementById('supplier-modal').classList.remove('hidden');
                document.getElementById('supplier-modal').classList.add('flex');
                document.getElementById('supplier-modal-title').textContent = '{{ __('suppliers.edit_supplier') }}';
                document.getElementById('supplier-submit-btn').textContent = '{{ __('suppliers.save') }}';
                document.getElementById('supplier_id').value = s.id;
                document.querySelector('input[name="name"]').value = s.name || '';
                setPhoneMaskValue(supplierMasks.phone, s.phone);
                document.querySelector('input[name="email"]').value = s.email || '';
                document.querySelector('input[name="telegram"]').value = s.telegram || '';
                setPhoneMaskValue(supplierMasks.whatsapp, s.whatsapp);
                document.querySelector('input[name="website"]').value = s.website || '';
                document.querySelector('input[name="city"]').value = s.city || '';
                document.querySelector('input[name="address"]').value = s.address || '';
                const sphereSel = document.querySelector('select[name="sphere"]');
                if (sphereSel) sphereSel.value = s.sphere || '';
                const wt = document.querySelector('select[name="work_terms_type"]');
                if (wt) wt.value = s.work_terms_type || 'percent';
                document.querySelector('input[name="work_terms_value"]').value = s.work_terms_value || '';
                document.querySelector('input[name="comment_main"]').value = s.comment || '';
                document.querySelector('input[name="recommend"]').checked = !!s.recommend;
                document.querySelectorAll('input[name="org_form"]').forEach(rd => {
                    rd.checked = rd.value === (s.org_form || 'ooo');
                });
                document.querySelector('input[name="inn"]').value = s.inn || '';
                document.querySelector('input[name="kpp"]').value = s.kpp || '';
                document.querySelector('input[name="ogrn"]').value = s.ogrn || '';
                document.querySelector('input[name="okpo"]').value = s.okpo || '';
                document.querySelector('input[name="legal_address"]').value = s.legal_address || '';
                document.querySelector('input[name="actual_address"]').value = s.actual_address || '';
                document.querySelector('input[name="address_match"]').checked = !!s.address_match;
                document.querySelector('input[name="director"]').value = s.director || '';
                document.querySelector('input[name="accountant"]').value = s.accountant || '';
                document.querySelector('input[name="bik"]').value = s.bik || '';
                document.querySelector('input[name="bank"]').value = s.bank || '';
                document.querySelector('input[name="checking_account"]').value = s.checking_account || '';
                document.querySelector('input[name="corr_account"]').value = s.corr_account || '';
                document.querySelector('input[name="comment_bank"]').value = s.comment_bank || '';
                croppedLogoBlob = null;
                document.getElementById('remove_logo').value = '0';
                if (s.logo_url) {
                    updateLogoPreview(s.logo_url);
                } else {
                    resetLogoPreview(false);
                }
                const bt = document.getElementById('brands-tags');
                bt.innerHTML = '';
                (s.brands || []).forEach(b => {
                    const span = document.createElement('span');
                    span.className =
                        'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm';
                    span.innerHTML = b +
                        ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                    const hi = document.createElement('input');
                    hi.type = 'hidden';
                    hi.name = 'brands[]';
                    hi.value = b;
                    span.appendChild(hi);
                    bt.appendChild(span);
                });
                const ct = document.getElementById('cities-tags');
                ct.innerHTML = '';
                (s.cities_presence || []).forEach(c => {
                    const span = document.createElement('span');
                    span.className =
                        'inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-[#f1f5f9] dark:bg-[#0a0a0a] text-sm';
                    span.innerHTML = c +
                        ' <button type="button" class="text-red-500 hover:text-red-600" onclick="this.parentElement.remove()">&times;</button>';
                    const hi = document.createElement('input');
                    hi.type = 'hidden';
                    hi.name = 'cities_presence[]';
                    hi.value = c;
                    span.appendChild(hi);
                    ct.appendChild(span);
                });
            } catch (e) {
                console.error(e);
            }
        }

        async function deleteSupplier(id) {
            if (!confirm('{{ __('suppliers.delete') }}?')) return;
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    }
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    projectAlert('error', data.message || '{{ __('suppliers.delete') }}', '', 3000);
                    return;
                }
                window.allSuppliers = (window.allSuppliers || []).filter((s) => parseInt(s.id, 10) !== parseInt(id, 10));
                window.renderSuppliersActiveTab?.();
                projectAlert('success', data.message || '{{ __('suppliers.deleted') }}', '', 2200);
            } catch (e) {
                console.error(e);
                projectAlert('error', '{{ __('objects.error') }}', '', 3000);
            }
        }

        async function toggleFavorite(id, btn) {
            try {
                const r = await fetch('{{ url('suppliers') }}/' + id + '/toggle-favorite', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const d = await r.json();
                const idx = (window.allSuppliers || []).findIndex((s) => parseInt(s.id, 10) === parseInt(id, 10));
                if (idx >= 0) window.allSuppliers[idx].is_favorite = !!d.is_favorite;
                if (btn) {
                    btn.classList.toggle('active', !!d.is_favorite);
                    const svg = btn.querySelector('svg');
                    if (svg) svg.setAttribute('fill', d.is_favorite ? 'currentColor' : 'none');
                }
                window.renderSuppliersActiveTab?.();
            } catch (e) {
                console.error(e);
            }
        }

        function addOrderFromSupplier(supplierId) {
            // Перенаправляем на страницу поставок с предзаполненным поставщиком
            window.location.href = '{{ route('supplier-orders.index') }}?supplier_id=' + supplierId;
        }

        function closeSupplierModal() {
            const modal = document.getElementById('supplier-modal');
            modal.classList.add('modal-closing');
            setTimeout(() => {
                modal.classList.remove('modal-closing', 'flex');
                modal.classList.add('hidden');
                document.getElementById('supplier-form').reset();
                window.resetLogoPreview?.();
            }, 280);
        }

        // Обработка формы поставщика
        document.getElementById('supplier-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const id = form.querySelector('#supplier_id').value;
            const url = id ? '{{ url('suppliers') }}/' + id : '{{ route('suppliers.store') }}';
            const fd = new FormData(form);
            fd.append('_token', TOKEN);
            if (id) fd.append('_method', 'PUT');
            fd.delete('supplier_id');
            fd.delete('brand_input');
            fd.delete('city_input');
            fd.delete('logo');
            if (croppedLogoBlob) {
                fd.append('logo', croppedLogoBlob, 'logo.jpg');
            }
            const removeLogo = document.getElementById('remove_logo').value;
            if (removeLogo === '1') fd.set('remove_logo', '1');
            const submitBtn = document.getElementById('supplier-submit-btn');
            submitBtn.disabled = true;
            try {
                const r = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': TOKEN,
                        'Accept': 'application/json'
                    },
                    body: fd
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok && data?.supplier) {
                    const s = data.supplier;
                    const list = window.allSuppliers || [];
                    const i = list.findIndex((x) => parseInt(x.id, 10) === parseInt(s.id, 10));
                    if (i >= 0) list[i] = s;
                    else list.unshift(s);
                    window.allSuppliers = list;
                    closeSupplierModal();
                    window.renderSuppliersActiveTab?.();
                    projectAlert('success', data.message || '{{ __('suppliers.updated') }}', '', 2400);
                } else {
                    alert(data.message || 'Error');
                }
            } catch (err) {
                alert('Error');
            } finally {
                submitBtn.disabled = false;
            }
        });
    </script>
@endsection
