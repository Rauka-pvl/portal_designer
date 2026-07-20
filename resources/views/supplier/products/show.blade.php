@extends('layouts.supplier')

@section('title', $product->name)
@section('header_title', $product->name)

@section('content')
    @include('partials.product-detail', [
        'product' => $product,
        'backUrl' => route('supplier.products.index'),
        'backLabel' => __('products.back'),
    ])

    <div class="max-w-5xl mx-auto mt-8">
        <div class="rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.qr_title') }}</h2>
                    <p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A] max-w-xl">{{ __('products.qr_help') }}</p>
                </div>
                <button type="button"
                    id="product-qr-open"
                    data-product-id="{{ $product->id }}"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#f59e0b] px-4 text-sm font-medium text-white hover:bg-[#d97706]"
                    aria-label="{{ __('products.qr_code') }}">
                    {{ __('products.qr_code') }}
                </button>
            </div>
        </div>
    </div>

    @include('supplier.products.partials.qr-modal')
@endsection

@push('scripts')
    @include('supplier.products.partials.qr-script')
@endpush
