@extends('layouts.dashboard')

@section('title', __('projects.projects'))

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

    .custom-select {
        position: relative;
    }

    .custom-select-toggle {
        width: 100%;
        padding: 0.5rem 1rem;
        border: 1px solid #7c8799;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #0f172a;
        font-size: 0.875rem;
    }

    .custom-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #7c8799;
        border-radius: 8px;
        margin-top: 0.25rem;
        max-height: 300px;
        overflow-y: auto;
        z-index: 50;
        display: none;
    }

    .custom-select-dropdown.open {
        display: block;
    }

    .custom-select-search {
        padding: 0.5rem;
        border-bottom: 1px solid #7c8799;
    }

    .custom-select-search input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #7c8799;
        border-radius: 6px;
        font-size: 0.875rem;
    }

    .custom-select-options {
        max-height: 250px;
        overflow-y: auto;
    }

    .custom-select-option {
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.875rem;
        color: #0f172a;
    }

    .custom-select-option:hover {
        background: #f1f5f9;
    }

    .custom-select-option.selected {
        background: #fef3c7;
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

    .dark .custom-select-toggle {
        background: #161615;
        border-color: #3E3E3A;
        color: #EDEDEC;
    }

    .dark .custom-select-dropdown {
        background: #161615;
        border-color: #3E3E3A;
    }

    .dark .custom-select-search input {
        background: #0a0a0a;
        border-color: #3E3E3A;
        color: #EDEDEC;
    }

    .dark .custom-select-option {
        color: #EDEDEC;
    }

    .dark .custom-select-option:hover {
        background: #0a0a0a;
    }

    .dark .custom-select-option.selected {
        background: #1D0002;
        color: #f59e0b;
    }
</style>
@endpush

@section('content')
<div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('projects.projects') }}</h1>
    <button id="add-project-btn" class="filter-btn active">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('projects.add_project') }}
    </button>
</div>

<!-- Вкладки -->
<div class="mb-6 flex gap-2">
    <button data-tab="table" class="tab-btn active">{{ __('projects.table') }}</button>
    <button data-tab="list" class="tab-btn">{{ __('projects.list') }}</button>
    <button data-tab="funnel" class="tab-btn">{{ __('projects.funnel') }}</button>
</div>

<!-- Поиск и фильтры -->
<div class="mb-6 flex flex-col md:flex-row gap-4">
    <div class="flex-1">
        <input type="text" id="search-input" placeholder="{{ __('projects.search') }}"
               class="w-full px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:border-[#f59e0b]">
    </div>
    <div class="w-full md:w-48">
        <div class="custom-select" id="status-filter-wrapper">
            <div class="custom-select-toggle" onclick="toggleCustomSelect('status-filter-wrapper')">
                <span id="status-filter-text">{{ __('projects.all_statuses') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div class="custom-select-dropdown">
                <div class="custom-select-search">
                    <input type="text" placeholder="{{ __('projects.search') }}" oninput="filterSelectOptions(this, 'status-filter-options')">
                </div>
                <div class="custom-select-options" id="status-filter-options">
                    <div class="custom-select-option" data-value="" onclick="selectOption(this, 'status-filter', '{{ __('projects.all_statuses') }}')">{{ __('projects.all_statuses') }}</div>
                    <div class="custom-select-option" data-value="contract_negotiation" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_contract_negotiation') }}')">{{ __('projects.status_contract_negotiation') }}</div>
                    <div class="custom-select-option" data-value="contract_signed" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_contract_signed') }}')">{{ __('projects.status_contract_signed') }}</div>
                    <div class="custom-select-option" data-value="prepayment_received" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_prepayment_received') }}')">{{ __('projects.status_prepayment_received') }}</div>
                    <div class="custom-select-option" data-value="tz_signed" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_tz_signed') }}')">{{ __('projects.status_tz_signed') }}</div>
                    <div class="custom-select-option" data-value="documents_signed" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_documents_signed') }}')">{{ __('projects.status_documents_signed') }}</div>
                    <div class="custom-select-option" data-value="in_work" onclick="selectOption(this, 'status-filter', '{{ __('projects.status_in_work') }}')">{{ __('projects.status_in_work') }}</div>
                </div>
            </div>
            <input type="hidden" id="status-filter" value="">
        </div>
    </div>
    <div class="w-full md:w-48">
        <div class="custom-select" id="stage-filter-wrapper">
            <div class="custom-select-toggle" onclick="toggleCustomSelect('stage-filter-wrapper')">
                <span id="stage-filter-text">{{ __('projects.all_stages') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div class="custom-select-dropdown">
                <div class="custom-select-search">
                    <input type="text" placeholder="{{ __('projects.search') }}" oninput="filterSelectOptions(this, 'stage-filter-options')">
                </div>
                <div class="custom-select-options" id="stage-filter-options">
                    <div class="custom-select-option" data-value="" onclick="selectOption(this, 'stage-filter', '{{ __('projects.all_stages') }}')">{{ __('projects.all_stages') }}</div>
                    <div class="custom-select-option" data-value="measurement" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_measurement') }}')">{{ __('projects.stage_measurement') }}</div>
                    <div class="custom-select-option" data-value="planning" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_planning') }}')">{{ __('projects.stage_planning') }}</div>
                    <div class="custom-select-option" data-value="drawings" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_drawings') }}')">{{ __('projects.stage_drawings') }}</div>
                    <div class="custom-select-option" data-value="equipment" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_equipment') }}')">{{ __('projects.stage_equipment') }}</div>
                    <div class="custom-select-option" data-value="estimate" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_estimate') }}')">{{ __('projects.stage_estimate') }}</div>
                    <div class="custom-select-option" data-value="visualization" onclick="selectOption(this, 'stage-filter', '{{ __('projects.stage_visualization') }}')">{{ __('projects.stage_visualization') }}</div>
                </div>
            </div>
            <input type="hidden" id="stage-filter" value="">
        </div>
    </div>
    <div class="w-full md:w-48">
        <div class="custom-select" id="object-filter-wrapper">
            <div class="custom-select-toggle" onclick="toggleCustomSelect('object-filter-wrapper')">
                <span id="object-filter-text">{{ __('projects.all_objects') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div class="custom-select-dropdown">
                <div class="custom-select-search">
                    <input type="text" placeholder="{{ __('projects.search') }}" oninput="filterSelectOptions(this, 'object-filter-options')">
                </div>
                <div class="custom-select-options" id="object-filter-options">
                    <div class="custom-select-option" data-value="" onclick="selectOption(this, 'object-filter', '{{ __('projects.all_objects') }}')">{{ __('projects.all_objects') }}</div>
                    @foreach($objects as $object)
                    <div class="custom-select-option" data-value="{{ $object['id'] }}" onclick="selectOption(this, 'object-filter', '{{ $object['address'] }}')">{{ $object['address'] }}</div>
                    @endforeach
                </div>
            </div>
            <input type="hidden" id="object-filter" value="">
        </div>
    </div>
    <div class="w-full md:w-48">
        <div class="custom-select" id="client-filter-wrapper">
            <div class="custom-select-toggle" onclick="toggleCustomSelect('client-filter-wrapper')">
                <span id="client-filter-text">{{ __('projects.all_clients') }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div class="custom-select-dropdown">
                <div class="custom-select-search">
                    <input type="text" placeholder="{{ __('projects.search') }}" oninput="filterSelectOptions(this, 'client-filter-options')">
                </div>
                <div class="custom-select-options" id="client-filter-options">
                    <div class="custom-select-option" data-value="" onclick="selectOption(this, 'client-filter', '{{ __('projects.all_clients') }}')">{{ __('projects.all_clients') }}</div>
                    @foreach($clients as $client)
                    <div class="custom-select-option" data-value="{{ $client['id'] }}" onclick="selectOption(this, 'client-filter', '{{ $client['name'] }}')">{{ $client['name'] }}</div>
                    @endforeach
                </div>
            </div>
            <input type="hidden" id="client-filter" value="">
        </div>
    </div>
</div>
