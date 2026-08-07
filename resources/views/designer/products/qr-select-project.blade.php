@extends('layouts.dashboard')

@section('title', __('products.qr_select_project_title'))
@section('header_title', __('products.qr_select_project_title'))

@section('content')
<div class="pb-16 max-w-3xl mx-auto">
    <div class="mb-5">
        @include('partials.back-link', [
            'fallback' => route('suppliers.products.show', [$supplier->id, $product->id]),
            'label' => __('products.qr_view_product'),
            'variant' => 'btn',
            'icon' => true,
        ])
    </div>

    <div class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 mb-5">
        <p class="text-xs uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-3">{{ __('products.qr_scanned_product') }}</p>
        <div class="flex items-start gap-4">
            <div class="w-20 h-20 rounded-xl overflow-hidden bg-[#f8fafc] dark:bg-[#0a0a0a] shrink-0 border border-[#7c8799]/30 dark:border-[#3E3E3A]">
                @if ($product->image_url)
                    <img src="{{ $product->image_url }}" alt="" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-[#94a3b8]">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $product->name }}</h1>
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">
                    {{ $supplier->name ?? '—' }}
                    @if ($product->sku)
                        · {{ __('products.sku') }}: {{ $product->sku }}
                    @endif
                </p>
                @if ($product->price !== null)
                    <p class="mt-2 text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">
                        {{ number_format((float) $product->price, 0, '.', ' ') }} ₸
                        @if ($product->unit)
                            <span class="text-sm font-normal text-[#64748b] dark:text-[#A1A09A]">/ {{ $product->unit }}</span>
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5">
        <h2 class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.qr_select_project_heading') }}</h2>
        <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A] mb-4">{{ __('products.qr_select_project_hint') }}</p>

        @if ($projects->isEmpty())
            <div class="rounded-xl border border-dashed border-[#7c8799]/50 dark:border-[#3E3E3A] px-4 py-8 text-center mb-4">
                <p class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.qr_no_projects_title') }}</p>
                <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('products.qr_no_projects_body') }}</p>
            </div>
        @else
            <div class="space-y-2 mb-4 max-h-[50vh] overflow-y-auto pr-1">
                @foreach ($projects as $project)
                    <form method="POST" action="{{ route('product.qr.assign-project', [$supplier->id, $product->id]) }}">
                        @csrf
                        <input type="hidden" name="action" value="select">
                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                        <button type="submit"
                            class="w-full text-left rounded-xl border border-[#7c8799]/40 dark:border-[#3E3E3A] px-4 py-3 hover:border-[#f59e0b] hover:bg-[#f59e0b]/5 transition-colors flex items-center justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block font-medium text-[#0f172a] dark:text-[#EDEDEC] truncate">{{ $project->name }}</span>
                                <span class="block text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">
                                    #{{ $project->id }}
                                    @if ($project->updated_at)
                                        · {{ $project->updated_at->format('d.m.Y') }}
                                    @endif
                                </span>
                            </span>
                            <svg class="w-5 h-5 shrink-0 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('product.qr.assign-project', [$supplier->id, $product->id]) }}">
            @csrf
            <input type="hidden" name="action" value="create">
            <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#f59e0b] to-[#fb923c] px-5 py-3 text-sm font-semibold text-white hover:opacity-95 transition-opacity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('products.qr_create_project') }}
            </button>
        </form>
    </div>
</div>
@endsection
