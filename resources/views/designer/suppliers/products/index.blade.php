@extends('layouts.dashboard')

@section('title', $supplier->name)
@section('header_title', $supplier->name)

@push('styles')
    <style>
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            height: 40px;
        }
        .dark .qty-stepper { border-color: #f59e0b; background: #0a0a0a; }
        .qty-stepper button {
            width: 40px;
            height: 100%;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
            transition: all 0.15s;
            font-size: 20px;
            line-height: 1;
            user-select: none;
        }
        .qty-stepper button:hover { background: #f59e0b; color: #ffffff; }
        .qty-stepper input {
            width: 44px;
            height: 100%;
            text-align: center;
            border: none;
            background: transparent;
            color: #0f172a;
            font-weight: 600;
            font-size: 15px;
            -moz-appearance: textfield;
        }
        .dark .qty-stepper input { color: #EDEDEC; }
        .qty-stepper.w-full input { flex: 1 1 auto; width: auto; }
        .qty-stepper input::-webkit-outer-spin-button,
        .qty-stepper input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .catalog-cart-bar {
            position: sticky;
            bottom: 1rem;
            z-index: 30;
        }
    </style>
@endpush

@section('content')
    @php
        $name = trim((string) $supplier->name);
        $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
        $initials = $initials !== '' ? $initials : 'S';
    @endphp

    @include('partials.supplier-detail-tabs', ['active' => 'products', 'supplierId' => $supplier->id])

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#ef4444] text-white flex items-center justify-center font-semibold">
                {{ $initials }}
            </div>
            <div>
                <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $name ?: '—' }}</h1>
                <div class="mt-1 flex items-center gap-2">
                    @include('partials.stars', ['value' => $ratingSummary['average'] ?? 0, 'count' => $ratingSummary['count'] ?? 0, 'size' => 'w-4 h-4'])
                    <span class="text-sm text-[#64748b] dark:text-[#A1A09A]">· {{ __('products.catalog_count', ['count' => $products->count()]) }}</span>
                </div>
            </div>
        </div>
        @include('partials.back-link', [
            'fallback' => route('suppliers.show', $supplier->id),
            'label' => __('suppliers.close'),
            'class' => 'px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors self-start',
            'icon' => false,
        ])
    </div>

    @if ($products->isEmpty())
        <div class="rounded-xl border border-dashed border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-10 text-center text-[#64748b] dark:text-[#A1A09A]">
            {{ __('products.catalog_empty') }}
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($products as $product)
                @php
                    $priceFormatted = $product->price !== null ? rtrim(rtrim(number_format((float) $product->price, 2, '.', ' '), '0'), '.') : null;
                @endphp
                <div class="catalog-card group flex flex-col rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] overflow-hidden"
                    data-product-id="{{ $product->id }}"
                    data-name="{{ $product->name }}"
                    data-price="{{ $product->price !== null ? (float) $product->price : 0 }}"
                    data-unit="{{ $product->unit }}">
                    <a href="{{ route('suppliers.products.show', [$supplier->id, $product->id]) }}" class="block aspect-[4/3] bg-[#f8fafc] dark:bg-[#0a0a0a] overflow-hidden">
                        <img src="{{ $product->image_url ?? asset('images/placeholder-product.svg') }}"
                            alt="{{ $product->name }}"
                            class="w-full h-full object-cover transition-transform group-hover:scale-105 {{ $product->image_url ? '' : 'opacity-40' }}"
                            onerror="this.src='{{ asset('images/placeholder-product.svg') }}';this.classList.add('opacity-40');">
                    </a>
                    <div class="flex flex-col gap-2 p-4 flex-1">
                        <a href="{{ route('suppliers.products.show', [$supplier->id, $product->id]) }}" class="font-medium text-[#0f172a] dark:text-[#EDEDEC] hover:text-[#f59e0b] transition-colors">{{ $product->name }}</a>
                        @if ($product->category)
                            <span class="self-start text-xs text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a] rounded-full px-2.5 py-1">{{ $product->category }}</span>
                        @endif
                        @if ($priceFormatted !== null)
                            <div class="flex items-baseline gap-1">
                                <span class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ $priceFormatted }} ₸</span>
                                @if ($product->unit)<span class="text-sm text-[#64748b] dark:text-[#A1A09A]">/ {{ $product->unit }}</span>@endif
                            </div>
                        @endif

                        <div class="mt-auto pt-3">
                            <button type="button" data-add-to-cart
                                class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-[#0f172a] dark:bg-[#EDEDEC] text-white dark:text-[#0a0a0a] text-sm font-medium hover:bg-[#f59e0b] hover:text-white dark:hover:bg-[#f59e0b] dark:hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ __('products.add_to_cart') }}
                            </button>
                            <div class="qty-stepper hidden w-full justify-between" data-cart-stepper>
                                <button type="button" data-qty-dec aria-label="-">−</button>
                                <input type="number" min="1" step="1" value="1" data-qty-input>
                                <button type="button" data-qty-inc aria-label="+">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Плавающая корзина --}}
    <div id="cart-bar" class="catalog-cart-bar mt-6 hidden">
        <div class="rounded-xl border border-[#f59e0b] bg-white dark:bg-[#161615] shadow-lg px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 text-[#0f172a] dark:text-[#EDEDEC]">
                <svg class="w-6 h-6 text-[#f59e0b]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span class="font-medium"><span id="cart-count">0</span> {{ __('products.cart_items') }}</span>
                <span class="text-[#64748b] dark:text-[#A1A09A]">·</span>
                <span class="font-semibold"><span id="cart-total">0</span> ₸</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="cart-clear" class="px-3 py-2 rounded-lg text-sm text-[#64748b] dark:text-[#A1A09A] hover:text-[#ef4444] transition-colors">{{ __('products.cart_clear') }}</button>
                <button type="button" id="cart-checkout" class="px-5 py-2.5 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706] transition-colors">{{ __('products.checkout') }}</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const supplierId = {{ (int) $supplier->id }};
    const storageKey = 'catalog_cart_' + supplierId;
    const ordersUrl = @json(route('supplier-orders.index'));

    const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Math.round(n));

    function readCart() {
        try { return JSON.parse(localStorage.getItem(storageKey)) || {}; } catch (e) { return {}; }
    }
    function writeCart(cart) {
        localStorage.setItem(storageKey, JSON.stringify(cart));
        renderCartBar();
    }
    function cartTotals(cart) {
        let count = 0, total = 0;
        Object.values(cart).forEach((i) => { count += i.qty; total += i.qty * (i.price || 0); });
        return { count, total };
    }

    // --- Синхронизация карточки с корзиной ---
    // Количество показываем только для товаров, которые уже есть в корзине.
    function syncCard(card) {
        const id = card.dataset.productId;
        const cart = readCart();
        const item = cart[id];
        const addBtn = card.querySelector('[data-add-to-cart]');
        const stepper = card.querySelector('[data-cart-stepper]');
        const input = card.querySelector('[data-qty-input]');
        if (item) {
            addBtn.style.display = 'none';
            stepper.style.display = 'flex';
            input.value = item.qty;
        } else {
            addBtn.style.display = 'inline-flex';
            stepper.style.display = 'none';
        }
    }
    function syncAllCards() {
        document.querySelectorAll('.catalog-card').forEach(syncCard);
    }

    function setCartQty(card, qty) {
        const id = card.dataset.productId;
        const cart = readCart();
        if (qty <= 0) {
            delete cart[id];
        } else {
            cart[id] = {
                id: parseInt(id, 10),
                name: card.dataset.name,
                price: parseFloat(card.dataset.price) || 0,
                unit: card.dataset.unit || '',
                qty: qty,
            };
        }
        writeCart(cart);
        syncCard(card);
    }

    // --- В корзину ---
    document.querySelectorAll('[data-add-to-cart]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const card = btn.closest('.catalog-card');
            setCartQty(card, (readCart()[card.dataset.productId]?.qty || 0) + 1);
        });
    });

    // --- Степперы на карточках (для товаров в корзине) ---
    document.querySelectorAll('[data-cart-stepper]').forEach((stepper) => {
        const card = stepper.closest('.catalog-card');
        const input = stepper.querySelector('[data-qty-input]');
        stepper.querySelector('[data-qty-dec]').addEventListener('click', () => {
            setCartQty(card, (readCart()[card.dataset.productId]?.qty || 1) - 1);
        });
        stepper.querySelector('[data-qty-inc]').addEventListener('click', () => {
            setCartQty(card, (readCart()[card.dataset.productId]?.qty || 0) + 1);
        });
        input.addEventListener('change', () => {
            setCartQty(card, Math.max(0, parseInt(input.value, 10) || 0));
        });
    });

    // --- Плавающая корзина ---
    const cartBar = document.getElementById('cart-bar');
    function renderCartBar() {
        const cart = readCart();
        const { count, total } = cartTotals(cart);
        document.getElementById('cart-count').textContent = count;
        document.getElementById('cart-total').textContent = fmt(total);
        cartBar.classList.toggle('hidden', count === 0);
    }
    document.getElementById('cart-clear')?.addEventListener('click', () => {
        localStorage.removeItem(storageKey);
        renderCartBar();
        syncAllCards();
    });

    // --- Оформить поставку: открываем стандартную модалку на странице поставок ---
    document.getElementById('cart-checkout')?.addEventListener('click', () => {
        const url = new URL(ordersUrl, window.location.origin);
        url.searchParams.set('open_order', '1');
        url.searchParams.set('supplier_id', supplierId);
        window.location.href = url.toString();
    });

    renderCartBar();
    syncAllCards();
})();
</script>
@endsection
