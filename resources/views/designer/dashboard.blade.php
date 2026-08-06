@extends('layouts.dashboard')

@section('title', __('dashboard.dashboard'))
@section('header_title', __('dashboard.dashboard'))

@section('content')
@php
    $m = $metrics ?? [];
    $period = $period ?? 'month';
@endphp

<div class="crm-page-header mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1 class="text-lg font-semibold text-[var(--crm-text)]">{{ __('dashboard.dashboard') }}</h1>
        <p class="text-sm text-[var(--crm-muted)]">{{ __('dashboard.crm_analytics_subtitle') }}</p>
    </div>
    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-2" id="period-form">
        <select name="period" class="crm-input crm-select" onchange="document.getElementById('custom-dates').classList.toggle('hidden', this.value !== 'custom'); this.form.submit()">
            <option value="week" @selected($period === 'week')>{{ __('dashboard.crm_period_week') }}</option>
            <option value="month" @selected($period === 'month')>{{ __('dashboard.crm_period_month') }}</option>
            <option value="quarter" @selected($period === 'quarter')>{{ __('dashboard.crm_period_quarter') }}</option>
            <option value="year" @selected($period === 'year')>{{ __('dashboard.crm_period_year') }}</option>
            <option value="custom" @selected($period === 'custom')>{{ __('dashboard.crm_period_custom') }}</option>
        </select>
        <div id="custom-dates" class="flex gap-2 {{ $period === 'custom' ? '' : 'hidden' }}">
            <input type="date" name="from" value="{{ $from ?? '' }}" class="crm-input">
            <input type="date" name="to" value="{{ $to ?? '' }}" class="crm-input">
            <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm">OK</button>
        </div>
    </form>
    </div>

<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-5">
    @foreach ([
        ['key' => 'active_projects', 'label' => __('dashboard.crm_active_projects')],
        ['key' => 'overdue_projects', 'label' => __('dashboard.crm_overdue_projects')],
        ['key' => 'deadlines_7_days', 'label' => __('dashboard.crm_deadlines_7')],
        ['key' => 'overdue_checklists', 'label' => __('dashboard.crm_overdue_checklists')],
        ['key' => 'delayed_supplies', 'label' => __('dashboard.crm_delayed_supplies')],
        ['key' => 'completed_projects', 'label' => __('dashboard.crm_completed_projects')],
    ] as $card)
        <div class="crm-metric-card">
            <div class="text-xs text-[var(--crm-muted)] mb-1">{{ $card['label'] }}</div>
            <div class="text-2xl font-semibold text-[var(--crm-text)]">{{ (int) ($m[$card['key']] ?? 0) }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="crm-card p-4">
        <h2 class="text-sm font-semibold mb-3">{{ __('dashboard.crm_chart_by_stage') }}</h2>
        <div class="relative h-56"><canvas id="chart-stages"></canvas><div id="empty-stages" class="crm-empty hidden">{{ __('dashboard.crm_no_data') }}</div></div>
    </div>
    <div class="crm-card p-4">
        <h2 class="text-sm font-semibold mb-3">{{ __('dashboard.crm_chart_created_completed') }}</h2>
        <div class="relative h-56"><canvas id="chart-created"></canvas><div id="empty-created" class="crm-empty hidden">{{ __('dashboard.crm_no_data') }}</div></div>
    </div>
    <div class="crm-card p-4">
        <h2 class="text-sm font-semibold mb-3">{{ __('dashboard.crm_chart_supplies') }}</h2>
        <div class="relative h-56"><canvas id="chart-supplies"></canvas><div id="empty-supplies" class="crm-empty hidden">{{ __('dashboard.crm_no_data') }}</div></div>
    </div>
    <div class="crm-card p-4">
        <h2 class="text-sm font-semibold mb-3">{{ __('dashboard.crm_chart_deadlines') }}</h2>
        <div class="relative h-56"><canvas id="chart-deadlines"></canvas><div id="empty-deadlines" class="crm-empty hidden">{{ __('dashboard.crm_no_data') }}</div></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const charts = @json($charts ?? []);
    const isDark = document.documentElement.classList.contains('dark');
    const tick = isDark ? '#A1A09A' : '#64748b';
    const grid = isDark ? 'rgba(62,62,58,.5)' : 'rgba(124,135,153,.25)';

    function sum(arr) { return (arr || []).reduce((a, b) => a + Number(b || 0), 0); }
    function empty(canvasId, emptyId, hasData) {
        const c = document.getElementById(canvasId);
        const e = document.getElementById(emptyId);
        if (!hasData) { c.classList.add('hidden'); e.classList.remove('hidden'); return false; }
        return true;
    }

    const byStage = charts.projects_by_stage || { labels: [], values: [], colors: [] };
    if (empty('chart-stages', 'empty-stages', sum(byStage.values) > 0)) {
        new Chart(document.getElementById('chart-stages'), {
            type: 'bar',
            data: {
                labels: byStage.labels,
                datasets: [{ data: byStage.values, backgroundColor: byStage.colors, borderRadius: 4 }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: {
                    x: { ticks: { color: tick }, grid: { color: grid } },
                    y: { ticks: { color: tick }, grid: { display: false } }
                }
            }
        });
    }

    const cvc = charts.created_vs_completed || { labels: [], created: [], completed: [] };
    if (empty('chart-created', 'empty-created', sum(cvc.created) + sum(cvc.completed) > 0)) {
        new Chart(document.getElementById('chart-created'), {
            type: 'line',
            data: {
                labels: cvc.labels,
                datasets: [
                    { label: '{{ __('projects.add_project') }}', data: cvc.created, borderColor: '#f59e0b', tension: .3, fill: false },
                    { label: '{{ __('dashboard.crm_completed_projects') }}', data: cvc.completed, borderColor: '#22c55e', tension: .3, fill: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: tick } } },
                scales: {
                    x: { ticks: { color: tick, maxRotation: 0 }, grid: { color: grid } },
                    y: { ticks: { color: tick }, grid: { color: grid }, beginAtZero: true }
                }
            }
        });
    }

    const supplies = charts.supplies_by_status || { labels: [], values: [], colors: [] };
    if (empty('chart-supplies', 'empty-supplies', sum(supplies.values) > 0)) {
        new Chart(document.getElementById('chart-supplies'), {
            type: 'doughnut',
            data: {
                labels: supplies.labels,
                datasets: [{ data: supplies.values, backgroundColor: supplies.colors, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: tick, boxWidth: 12 } } }
            }
        });
    }

    const dead = charts.deadline_compliance || { labels: [], values: [], colors: [] };
    if (empty('chart-deadlines', 'empty-deadlines', sum(dead.values) > 0)) {
        new Chart(document.getElementById('chart-deadlines'), {
            type: 'doughnut',
            data: {
                labels: dead.labels,
                datasets: [{ data: dead.values, backgroundColor: dead.colors, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: tick, boxWidth: 12 } } }
            }
        });
    }
})();
</script>
@endsection
