@extends('layouts.dashboard')

@section('title', $product->name)
@section('header_title', $product->name)

@push('styles')
    <style>
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid #7c8799;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }
        .dark .qty-stepper { border-color: #3E3E3A; background: #0a0a0a; }
        .qty-stepper button {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; transition: all 0.15s; font-size: 20px; line-height: 1; user-select: none;
        }
        .qty-stepper button:hover { background: #fef3c7; color: #f59e0b; }
        .dark .qty-stepper button:hover { background: #1D0002; }
        .qty-stepper input {
            width: 52px; height: 40px; text-align: center; border: none;
            border-left: 1px solid #7c8799; border-right: 1px solid #7c8799;
            background: transparent; color: #0f172a; font-weight: 600; -moz-appearance: textfield;
        }
        .dark .qty-stepper input { color: #EDEDEC; border-color: #3E3E3A; }
        .qty-stepper input::-webkit-outer-spin-button,
        .qty-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
@endpush

@section('content')
    @include('partials.product-detail', [
        'product' => $product,
        'backUrl' => route('suppliers.products.index', $supplier->id),
        'backLabel' => __('products.back_to_catalog'),
    ])

    <div class="max-w-5xl mx-auto mt-6">
        <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 flex flex-col sm:flex-row items-center justify-between gap-4"
            data-product-id="{{ $product->id }}"
            data-name="{{ $product->name }}"
            data-price="{{ $product->price !== null ? (float) $product->price : 0 }}"
            data-unit="{{ $product->unit }}">
            <div class="flex items-center gap-3">
                <span class="text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('products.quantity') }}</span>
                <div class="qty-stepper" data-qty-stepper>
                    <button type="button" data-qty-dec aria-label="-">−</button>
                    <input type="number" min="1" step="1" value="1" data-qty-input>
                    <button type="button" data-qty-inc aria-label="+">+</button>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" id="detail-add-to-cart"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-[#0f172a] dark:bg-[#EDEDEC] text-white dark:text-[#0a0a0a] text-sm font-medium hover:bg-[#f59e0b] hover:text-white dark:hover:bg-[#f59e0b] dark:hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    {{ __('products.add_to_cart') }}
                </button>
                <a href="{{ route('suppliers.products.index', $supplier->id) }}"
                    class="px-5 py-2.5 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">
                    {{ __('products.checkout') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const supplierId = {{ (int) $supplier->id }};
    const storageKey = 'catalog_cart_' + supplierId;
    const wrap = document.querySelector('[data-product-id]');
    const input = wrap.querySelector('[data-qty-input]');

    wrap.querySelector('[data-qty-dec]').addEventListener('click', () => {
        input.value = Math.max(1, (parseInt(input.value, 10) || 1) - 1);
    });
    wrap.querySelector('[data-qty-inc]').addEventListener('click', () => {
        input.value = (parseInt(input.value, 10) || 1) + 1;
    });
    input.addEventListener('change', () => { input.value = Math.max(1, parseInt(input.value, 10) || 1); });

    document.getElementById('detail-add-to-cart').addEventListener('click', function () {
        let cart = {};
        try { cart = JSON.parse(localStorage.getItem(storageKey)) || {}; } catch (e) { cart = {}; }
        const id = wrap.dataset.productId;
        const qty = Math.max(1, parseInt(input.value, 10) || 1);
        const prev = cart[id]?.qty || 0;
        cart[id] = {
            id: parseInt(id, 10),
            name: wrap.dataset.name,
            price: parseFloat(wrap.dataset.price) || 0,
            unit: wrap.dataset.unit || '',
            qty: prev + qty,
        };
        localStorage.setItem(storageKey, JSON.stringify(cart));
        const original = this.innerHTML;
        this.innerHTML = '{{ __('products.added') }}';
        setTimeout(() => { this.innerHTML = original; }, 1000);
    });
})();
</script>
@endsection
