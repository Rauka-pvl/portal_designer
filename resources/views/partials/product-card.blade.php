@php
    $editable = $editable ?? false;
    $product = $product;
    $detailUrl = $detailUrl ?? null;
    $priceFormatted = $product->price !== null ? rtrim(rtrim(number_format((float) $product->price, 2, '.', ' '), '0'), '.') : '';
@endphp

<div class="product-card group relative flex flex-col rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] overflow-hidden"
    data-product-id="{{ $product->id }}">

    <div class="product-card__media relative aspect-[4/3] bg-[#f8fafc] dark:bg-[#0a0a0a] overflow-hidden">
        <img src="{{ $product->image_url ?? asset('images/placeholder-product.svg') }}"
            alt="{{ $product->name }}"
            class="product-card__img w-full h-full object-cover {{ $product->image_url ? '' : 'opacity-40' }}"
            onerror="this.src='{{ asset('images/placeholder-product.svg') }}';this.classList.add('opacity-40');">

        @if ($editable)
            <label class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/40 opacity-0 group-hover:opacity-100 transition cursor-pointer text-white text-sm font-medium">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ __('products.change_photo') }}
                </span>
                <input type="file" accept="image/*" class="hidden product-image-input">
            </label>
        @endif
    </div>

    <div class="product-card__body flex flex-col gap-2 p-4 flex-1">
        @if ($editable)
            <input type="text" data-field="name" value="{{ $product->name }}"
                placeholder="{{ __('products.f_name') }}"
                class="product-edit product-edit--title w-full bg-transparent font-medium text-[#0f172a] dark:text-[#EDEDEC] text-base rounded px-1 -mx-1 focus:outline-none focus:ring-1 focus:ring-[#f59e0b]">

            <div class="flex items-center gap-2 flex-wrap">
                <input type="text" data-field="category" value="{{ $product->category }}"
                    placeholder="{{ __('products.f_category') }}"
                    class="product-edit text-xs text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a] rounded-full px-2.5 py-1 focus:outline-none focus:ring-1 focus:ring-[#f59e0b] max-w-[55%]">
            </div>

            <div class="flex items-baseline gap-1.5">
                <input type="number" step="0.01" min="0" data-field="price" value="{{ $priceFormatted }}"
                    placeholder="0"
                    class="product-edit w-28 text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC] bg-transparent rounded px-1 -mx-1 focus:outline-none focus:ring-1 focus:ring-[#f59e0b]">
                <span class="text-sm text-[#64748b] dark:text-[#A1A09A]">₸</span>
                <span class="text-[#94a3b8]">/</span>
                <input type="text" data-field="unit" value="{{ $product->unit }}"
                    placeholder="{{ __('products.f_unit') }}"
                    class="product-edit w-16 text-sm text-[#64748b] dark:text-[#A1A09A] bg-transparent rounded px-1 focus:outline-none focus:ring-1 focus:ring-[#f59e0b]">
            </div>

            <textarea data-field="description" rows="3"
                placeholder="{{ __('products.f_description') }}"
                class="product-edit w-full text-sm text-[#475569] dark:text-[#A1A09A] bg-transparent rounded px-1 -mx-1 resize-none focus:outline-none focus:ring-1 focus:ring-[#f59e0b]">{{ $product->description }}</textarea>

            <div class="mt-auto pt-2 flex items-center justify-between">
                <span class="product-save-hint text-xs text-[#22c55e] opacity-0 transition-opacity">{{ __('products.saved') }}</span>
                <div class="flex items-center gap-3">
                    @if ($detailUrl)
                        <a href="{{ $detailUrl }}" class="text-xs text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b] hover:underline">{{ __('products.details') }}</a>
                    @endif
                    <button type="button" class="product-delete text-xs text-[#ef4444] hover:underline">{{ __('products.delete') }}</button>
                </div>
            </div>
        @else
            <h3 class="font-medium text-[#0f172a] dark:text-[#EDEDEC] text-base">{{ $product->name }}</h3>
            @if ($product->category)
                <span class="self-start text-xs text-[#64748b] dark:text-[#A1A09A] bg-[#f8fafc] dark:bg-[#0a0a0a] rounded-full px-2.5 py-1">{{ $product->category }}</span>
            @endif
            @if ($product->price !== null)
                <div class="flex items-baseline gap-1">
                    <span class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ $priceFormatted }} ₸</span>
                    @if ($product->unit)<span class="text-sm text-[#64748b] dark:text-[#A1A09A]">/ {{ $product->unit }}</span>@endif
                </div>
            @endif
            @if ($product->description)
                <p class="text-sm text-[#475569] dark:text-[#A1A09A] whitespace-pre-line line-clamp-3">{{ $product->description }}</p>
            @endif
            @if ($detailUrl)
                <a href="{{ $detailUrl }}" class="mt-auto pt-2 inline-flex items-center gap-1 text-sm text-[#f59e0b] hover:underline">
                    {{ __('products.details') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        @endif
    </div>
</div>
