@extends('layouts.dashboard')

@section('title', __('supplier-orders.supplier_order'))
@section('header_title', __('supplier-orders.supplier_order'))

@php
    $o = $orderData;
    $statusMap = [
        'draft' => __('supplier-orders.status_draft'),
        'order_created' => __('supplier-orders.status_order_created'),
        'order_confirmed' => __('supplier-orders.status_order_confirmed'),
        'advance_payment' => __('supplier-orders.status_advance_payment'),
        'full_payment' => __('supplier-orders.status_full_payment'),
        'delivery_completed' => __('supplier-orders.status_delivery_completed'),
    ];
    $statusKey = (string) ($o['workflow_status'] ?? $o['status'] ?? 'draft');
    $statusLabel = $statusMap[$statusKey] ?? $statusKey;
    $categoryName = ($categoryOptions[$o['category'] ?? ''] ?? null) ?: (($o['category'] ?? '') !== '' ? $o['category'] : '—');
    $roomName = ($roomOptions[$o['room'] ?? ''] ?? null) ?: (($o['room'] ?? '') !== '' ? $o['room'] : '—');
    $locale = str_replace('_', '-', app()->getLocale());
    $fmtDate = function ($ymd) use ($locale) {
        if (! $ymd) return '—';
        try {
            return \Illuminate\Support\Carbon::parse($ymd)->locale(app()->getLocale())->isoFormat('D MMMM YYYY');
        } catch (\Throwable $e) {
            return $ymd;
        }
    };
    $fmtMoney = function ($n) {
        if ($n === null || $n === '') return '—';
        return number_format((int) $n, 0, ',', ' ').' ₸';
    };
    $statusBadgeClass = match ($statusKey) {
        'full_payment', 'delivery_completed' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        'advance_payment', 'order_confirmed' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30',
        'order_created' => 'bg-[#f59e0b]/15 text-[#d97706] dark:text-[#fbbf24] border-[#f59e0b]/35',
        'draft' => 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/25',
        default => 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-500/25',
    };
    $focusPayment = request('focus') === 'payment';
    $backFallback = route('supplier-orders.index', array_filter([
        'project_id' => request('project_id'),
        'supplier_id' => request('supplier_id'),
    ]));
    if (request('source') === 'dashboard' && \Illuminate\Support\Facades\Route::has('dashboard')) {
        $backFallback = route('dashboard');
    }
@endphp

@section('content')
<div id="order-detail" class="pb-28 max-w-6xl mx-auto">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex items-start gap-3">
            @include('partials.back-link', [
                'fallback' => $backFallback,
                'label' => __('supplier-orders.back'),
                'variant' => 'btn',
                'icon' => true,
            ])
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">
                    {{ __('supplier-orders.supplier_order') }} №{{ $o['id'] }}
                </h1>
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A] truncate">
                    {{ $o['project_name'] ?? '—' }} · {{ $o['supplier_name'] ?? '—' }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <span id="order-status-badge" class="inline-flex items-center min-h-10 px-3 rounded-full border text-sm font-medium {{ $statusBadgeClass }}">
                {{ $statusLabel }}
            </span>
            <button id="btn-edit" type="button"
                class="inline-flex items-center justify-center min-h-10 px-4 rounded-xl border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 text-sm font-medium transition-colors">
                {{ __('supplier-orders.edit_action') }}
            </button>
            <details class="relative">
                <summary class="list-none cursor-pointer inline-flex items-center justify-center min-h-10 min-w-10 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors"
                    aria-label="{{ __('supplier-orders.more_actions') }}">⋯</summary>
                <div class="absolute right-0 mt-2 w-48 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg p-1 z-20">
                    <form method="POST" action="{{ route('supplier-orders.destroy', $o['id']) }}"
                        onsubmit="return confirm(@json(__('supplier-orders.delete_confirm')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20">
                            {{ __('supplier-orders.delete') }}
                        </button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    @include('designer.supplier-orders.partials.offer-negotiation', [
        'orderData' => $o,
        'focusOfferSection' => $focusOfferSection ?? false,
    ])

    {{-- Summary (view) --}}
    <div data-detail-view class="mb-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.amount') }}</p>
            <p class="mt-1 text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]" data-view-summa>{{ $fmtMoney($o['summa'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.planned_delivery_date') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-date-planned>{{ $fmtDate($o['date_planned'] ?? null) }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.actual_delivery_date') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-date-actual>{{ $fmtDate($o['date_actual'] ?? null) }}</p>
        </div>
        <div class="rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
            <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.status') }}</p>
            <p class="mt-1 text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-status>{{ $statusLabel }}</p>
        </div>
    </div>

    <form id="supplier-order-details-form" method="POST" action="{{ route('supplier-orders.update', $o['id']) }}"
        enctype="multipart/form-data" data-ajax="1" class="space-y-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="intent" value="update">

        {{-- VIEW sections --}}
        <section data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.section_main') }}</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.project') }}</dt><dd class="mt-0.5 font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-project>{{ $o['project_name'] ?? '—' }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.supplier') }}</dt><dd class="mt-0.5 font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-supplier>{{ $o['supplier_name'] ?? '—' }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.category') }}</dt><dd class="mt-0.5 font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-category>{{ $categoryName }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.room') }}</dt><dd class="mt-0.5 font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-room>{{ $roomName }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.mark_on_drawing') }}</dt><dd class="mt-0.5 font-medium text-[#0f172a] dark:text-[#EDEDEC]" data-view-mark>{{ ($o['mark'] ?? '') !== '' ? $o['mark'] : '—' }}</dd></div>
            </dl>
        </section>

        <section id="payment-section" data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 {{ $focusPayment ? 'ring-2 ring-[#f59e0b]/50' : '' }}">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.section_finance') }}</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.amount') }}</dt><dd class="mt-0.5 font-medium" data-view-summa-2>{{ $fmtMoney($o['summa'] ?? 0) }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.bonus_percent') }}</dt><dd class="mt-0.5 font-medium" data-view-bonus>{{ isset($o['bonus_percent']) && $o['bonus_percent'] !== null ? rtrim(rtrim(number_format((float)$o['bonus_percent'], 2, '.', ''), '0'), '.').'%' : '—' }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.advance_amount') }}</dt><dd class="mt-0.5 font-medium" data-view-prepay-amt>{{ $fmtMoney($o['prepayment_amount'] ?? null) }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.balance_amount') }}</dt><dd class="mt-0.5 font-medium" data-view-pay-amt>{{ $fmtMoney($o['payment_amount'] ?? null) }}</dd></div>
            </dl>
        </section>

        <section data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.section_dates') }}</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.advance_date') }}</dt><dd class="mt-0.5 font-medium" data-view-prepay-date>{{ $fmtDate($o['prepayment_date'] ?? null) }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.balance_date') }}</dt><dd class="mt-0.5 font-medium" data-view-pay-date>{{ $fmtDate($o['payment_date'] ?? null) }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.planned_delivery_date') }}</dt><dd class="mt-0.5 font-medium">{{ $fmtDate($o['date_planned'] ?? null) }}</dd></div>
                <div><dt class="text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.actual_delivery_date') }}</dt><dd class="mt-0.5 font-medium">{{ $fmtDate($o['date_actual'] ?? null) }}</dd></div>
            </dl>
        </section>

        <section data-detail-view class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.product_service') }}</h2>
            <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC] whitespace-pre-wrap" data-view-comment>{{ ($o['product_service'] ?? $o['comment'] ?? '') !== '' ? ($o['product_service'] ?? $o['comment']) : '—' }}</p>
        </section>

        {{-- EDIT sections (hidden by default) --}}
        <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.section_main') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.status') }}</label>
                    <select name="status" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        @foreach (['order_created','order_confirmed','advance_payment','full_payment','delivery_completed'] as $st)
                            <option value="{{ $st }}" @selected(($o['status'] ?? '') === $st)>{{ $statusMap[$st] }}</option>
                        @endforeach
                    </select>
                    <p id="status-hint" class="hidden mt-1 text-xs text-[#d97706]">{{ __('supplier-orders.status_full_payment_hint') }}</p>
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.project') }}</label>
                    <select name="project_id" required class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        @foreach (($projects ?? []) as $project)
                            <option value="{{ $project->id }}" @selected((int) ($o['project_id'] ?? 0) === (int) $project->id)>{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.supplier') }}</label>
                    <select name="supplier_id" required class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        @foreach (($suppliers ?? []) as $supplier)
                            <option value="{{ $supplier->id }}" @selected((int) ($o['supplier_id'] ?? 0) === (int) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.category') }}</label>
                    <select name="category" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        <option value=""></option>
                        @foreach (($categoryOptions ?? []) as $key => $name)
                            <option value="{{ $key }}" @selected(($o['category'] ?? '') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.room') }}</label>
                    <select name="room" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                        <option value=""></option>
                        @foreach (($roomOptions ?? []) as $key => $name)
                            <option value="{{ $key }}" @selected(($o['room'] ?? '') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.mark_on_drawing') }}</label>
                    <input name="mark" value="{{ $o['mark'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
            </div>
        </section>

        <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.section_finance') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.amount') }}</label>
                    <input type="number" name="summa" value="{{ $o['summa'] ?? 0 }}" required min="0" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.bonus_percent') }}</label>
                    <input type="number" name="bonus_percent" step="0.01" min="0" max="100" value="{{ $o['bonus_percent'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.advance_amount') }}</label>
                    <input type="number" name="prepayment_amount" min="0" value="{{ $o['prepayment_amount'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.balance_amount') }}</label>
                    <input type="number" name="payment_amount" min="0" value="{{ $o['payment_amount'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
            </div>
        </section>

        <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.section_dates') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.planned_delivery_date') }}</label>
                    <input type="date" name="date_planned" value="{{ $o['date_planned'] ?? '' }}" required class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.actual_delivery_date') }}</label>
                    <input type="date" name="date_actual" value="{{ $o['date_actual'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.advance_date') }}</label>
                    <input type="date" name="prepayment_date" value="{{ $o['prepayment_date'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
                <div>
                    <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.balance_date') }}</label>
                    <input type="date" name="payment_date" value="{{ $o['payment_date'] ?? '' }}" class="mt-1 w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
                </div>
            </div>
        </section>

        <section data-detail-edit class="hidden rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 space-y-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.product_service') }}</h2>
            <textarea name="comment" rows="3" class="w-full px-3 py-2 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">{{ $o['product_service'] ?? $o['comment'] ?? '' }}</textarea>
            <div>
                <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.files') }}</label>
                <input type="file" name="files[]" multiple class="mt-1 w-full text-sm text-[#64748b] dark:text-[#A1A09A] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-[#f59e0b]/10 file:text-[#f59e0b]">
                @foreach (($o['files'] ?? []) as $filePath)
                    <input type="hidden" name="existing_files[]" value="{{ $filePath }}">
                @endforeach
            </div>
        </section>
    </form>

    <div data-detail-view class="mt-4 rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.files') }}</h2>
        @include('partials.file-actions-list', [
            'filePaths' => $o['files'] ?? [],
            'deleteCallback' => 'window.deleteSupplierOrderFileFromShow',
            'deleteEntityId' => $o['id'],
        ])
    </div>

    <div data-detail-view class="mt-4 rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('supplier-orders.project_steps_section') }}</h2>
        @php $incSteps = $o['included_steps'] ?? []; @endphp
        @if (count($incSteps) === 0)
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-orders.project_steps_empty') }}</p>
        @else
            <div class="space-y-3">
                @foreach ($incSteps as $step)
                    <div class="border-l-2 border-[#f59e0b]/60 pl-3">
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ $step['stage_type_label'] ?? ($step['stage_type'] ?? '') }}</p>
                        <p class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $step['title'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@include('partials.detail-sticky-actions', ['formId' => 'supplier-order-details-form'])
@include('partials.detail-confirm-modal')
@endsection

@section('scripts')
<script>
(function () {
    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    // Vite app.js is type=module (deferred). Wait until initDetailEdit exists.
    function whenDetailEditReady(fn, triesLeft) {
        if (typeof window.initDetailEdit === 'function' || triesLeft <= 0) {
            fn();
            return;
        }
        setTimeout(function () { whenDetailEditReady(fn, triesLeft - 1); }, 40);
    }

    whenReady(function () {
        whenDetailEditReady(bootSupplierOrderDetail, 50);
    });

    function bootSupplierOrderDetail() {
    const form = document.getElementById('supplier-order-details-form');
    if (!form) return;

    const statusSelect = form.querySelector('select[name="status"]');
    const statusHint = document.getElementById('status-hint');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const statusLabels = @json($statusMap);

    function syncStatusHint() {
        if (!statusHint || !statusSelect) return;
        statusHint.classList.toggle('hidden', statusSelect.value !== 'full_payment');
    }
    statusSelect?.addEventListener('change', syncStatusHint);

    window.deleteSupplierOrderFileFromShow = async function (orderId, fileIndex) {
        if (!confirm(@json(__('objects.delete_file_confirm')))) return;
        try {
            const r = await fetch(`/supplier-orders/${orderId}/files/${fileIndex}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) throw new Error(data.message || 'error');
            location.reload();
        } catch (e) {
            projectAlert?.('error', e.message || @json(__('supplier-orders.error')), '', 3000);
        }
    };

    if (location.hash === '#payment-section' || new URLSearchParams(location.search).get('focus') === 'payment') {
        document.getElementById('payment-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function startEditMode() {
        document.querySelectorAll('[data-detail-view]').forEach((el) => el.classList.add('hidden'));
        document.querySelectorAll('[data-detail-edit]').forEach((el) => el.classList.remove('hidden'));
        document.getElementById('btn-edit')?.classList.add('hidden');
        document.getElementById('detail-sticky-actions')?.classList.remove('hidden');
        const saveBtn = document.getElementById('btn-save');
        if (saveBtn) saveBtn.disabled = false;
    }

    if (typeof window.initDetailEdit === 'function') {
        window.initDetailEdit({
            form,
            editBtn: '#btn-edit',
            cancelBtn: '#btn-cancel',
            saveBtn: '#btn-save',
            sticky: '#detail-sticky-actions',
            unsavedModal: '#detail-unsaved-modal',
            onSave: async (f) => {
                const fd = new FormData(f);
                fd.set('intent', 'update');
                const r = await fetch(f.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    const msg = data.message || Object.values(data.errors || {}).flat().join(' ') || @json(__('supplier-orders.error'));
                    projectAlert?.('error', msg, '', 4000);
                    return false;
                }
                projectAlert?.('success', data.message || @json(__('supplier-orders.saved')), '', 2500);
                if (data.meta?.renegotiated) {
                    projectAlert?.('success', @json(__('supplier-orders.renegotiate_started')), '', 3500);
                }
                const o = data.order || {};
                const badge = document.getElementById('order-status-badge');
                const st = o.workflow_status || o.status || '';
                if (badge && statusLabels[st]) badge.textContent = statusLabels[st];
                setTimeout(() => location.reload(), 400);
                return true;
            },
        });
    } else {
        // Fallback if Vite bundle is stale / not loaded
        document.getElementById('btn-edit')?.addEventListener('click', startEditMode);
        document.getElementById('btn-cancel')?.addEventListener('click', () => location.reload());
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!window.lockSubmit?.(form)) {
                // allow single submit even without lockSubmit
            }
            const fd = new FormData(form);
            fd.set('intent', 'update');
            try {
                const r = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data.success) {
                    projectAlert?.('error', data.message || @json(__('supplier-orders.error')), '', 4000);
                    window.unlockSubmit?.(form);
                    return;
                }
                projectAlert?.('success', data.message || @json(__('supplier-orders.saved')), '', 2500);
                location.reload();
            } catch (err) {
                window.unlockSubmit?.(form);
                projectAlert?.('error', @json(__('supplier-orders.error')), '', 3000);
            }
        });
    }
    } // bootSupplierOrderDetail
})();
</script>
@endsection
