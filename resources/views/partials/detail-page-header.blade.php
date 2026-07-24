{{-- Shared detail header: title, status badge slot, Edit, menu --}}
@php
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $editLabel = $editLabel ?? __('detail.edit');
    $showEdit = $showEdit ?? true;
    $fallback = $fallback ?? url()->previous();
@endphp
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0 flex items-start gap-3">
        @include('partials.back-link', [
            'fallback' => $fallback,
            'label' => $backLabel ?? __('detail.back'),
            'variant' => 'btn',
            'icon' => true,
        ])
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $title }}</h1>
            @if ($subtitle !== '')
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A] truncate">{{ $subtitle }}</p>
            @endif
            @isset($badge)
                <div class="mt-2">{{ $badge }}</div>
            @endisset
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        @isset($statusSlot)
            {{ $statusSlot }}
        @endisset
        @if ($showEdit)
            <button id="btn-edit" type="button"
                class="inline-flex items-center justify-center min-h-10 px-4 rounded-xl border border-[#f59e0b] text-[#f59e0b] hover:bg-[#f59e0b]/10 text-sm font-medium transition-colors">
                {{ $editLabel }}
            </button>
        @endif
        @isset($actions)
            {{ $actions }}
        @endisset
    </div>
</div>
