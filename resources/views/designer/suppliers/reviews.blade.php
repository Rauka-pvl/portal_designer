@extends('layouts.dashboard')

@section('title', $supplierData['name'] ?? __('suppliers.supplier'))
@section('header_title', $supplierData['name'] ?? __('suppliers.supplier'))

@section('content')
    @include('partials.supplier-detail-tabs', ['active' => 'reviews', 'supplierId' => $supplierData['id'] ?? null])

    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $supplierData['name'] ?? '—' }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('reviews.subtitle_supplier') }}</p>
        </div>
        @include('partials.back-link', [
            'fallback' => route('suppliers.show', $supplierData['id'] ?? request()->route('supplierId')),
            'label' => __('suppliers.close'),
            'class' => 'px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors',
            'icon' => false,
        ])
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:max-w-md">
        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('reviews.average') }}</div>
            <div class="mt-2">@include('partials.stars', ['value' => $ratingSummary['average'] ?? 0, 'count' => 0, 'size' => 'w-5 h-5'])</div>
        </div>
        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('reviews.total') }}</div>
            <div class="mt-2 text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ (int) ($ratingSummary['count'] ?? 0) }}</div>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($reviews as $review)
            <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $review->reviewer->name ?? '—' }}</div>
                    @include('partials.stars', ['value' => (int) $review->rating, 'count' => 0, 'size' => 'w-4 h-4'])
                </div>
                <div class="mt-2 text-sm {{ $review->comment ? 'text-[#0f172a] dark:text-[#EDEDEC]' : 'text-[#94a3b8] dark:text-[#71717a] italic' }} whitespace-pre-line">
                    {{ $review->comment ?: __('reviews.no_comment') }}
                </div>
                <div class="mt-1 text-xs text-[#94a3b8] dark:text-[#71717a]">{{ optional($review->created_at)->format('Y-m-d') }}</div>
            </div>
        @empty
            <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-[#64748b] dark:text-[#A1A09A]">
                {{ __('reviews.empty') }}
            </div>
        @endforelse
    </div>

    @if ($reviews->hasPages())
        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
@endsection
