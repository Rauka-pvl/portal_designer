@extends($layout)

@section('title', __('reviews.title'))
@section('header_title', __('reviews.title'))

@php
    $renderStars = function (int $value) {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $filled = $i <= $value;
            $color = $filled ? '#f59e0b' : 'currentColor';
            $opacity = $filled ? '' : ' opacity-30';
            $out .= '<svg class="w-4 h-4' . $opacity . '" fill="' . $color . '" viewBox="0 0 20 20" aria-hidden="true"><path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.28 3.94a1 1 0 00.95.69h4.15c.97 0 1.37 1.24.59 1.81l-3.36 2.44a1 1 0 00-.36 1.12l1.28 3.94c.3.92-.75 1.69-1.54 1.12l-3.35-2.44a1 1 0 00-1.18 0l-3.35 2.44c-.79.57-1.84-.2-1.54-1.12l1.28-3.94a1 1 0 00-.36-1.12L1.93 9.37c-.78-.57-.38-1.81.59-1.81h4.15a1 1 0 00.95-.69L9.05 2.93z"/></svg>';
        }
        return $out;
    };
@endphp

@section('content')
    @include('partials.profile-tabs', ['active' => 'reviews'])

    <div class="mb-6">
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A]">
            {{ $isSupplier ? __('reviews.subtitle_supplier') : __('reviews.subtitle_designer') }}
        </p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:max-w-md">
        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('reviews.average') }}</div>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ $averageRating !== null ? number_format($averageRating, 1) : '—' }}</span>
                <span class="flex items-center text-[#94a3b8] dark:text-[#71717a]">{!! $renderStars((int) round($averageRating ?? 0)) !!}</span>
            </div>
        </div>
        <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
            <div class="text-xs text-[#64748b] dark:text-[#A1A09A] uppercase tracking-wide">{{ __('reviews.total') }}</div>
            <div class="mt-2 text-2xl font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ (int) $totalReviews }}</div>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($reviews as $review)
            <div class="rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3">
                        @php
                            $author = trim((string) ($review->reviewer->name ?? ''));
                            $initials = collect(preg_split('/\s+/', $author))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $initials = $initials !== '' ? $initials : '?';
                        @endphp
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold text-sm shrink-0">
                            {{ $initials }}
                        </div>
                        <div>
                            <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $author !== '' ? $author : '—' }}</div>
                            <div class="text-xs text-[#94a3b8] dark:text-[#71717a] mt-0.5">
                                @if ($review->supplier_order_id)
                                    {{ __('reviews.order', ['order' => $review->supplier_order_id]) }} ·
                                @endif
                                {{ optional($review->created_at)->format('Y-m-d') }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 text-[#94a3b8] dark:text-[#71717a]">{!! $renderStars((int) $review->rating) !!}</div>
                </div>
                <div class="mt-3 text-sm {{ $review->comment ? 'text-[#0f172a] dark:text-[#EDEDEC]' : 'text-[#94a3b8] dark:text-[#71717a] italic' }} whitespace-pre-line">
                    {{ $review->comment ?: __('reviews.no_comment') }}
                </div>
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
