@php
    $product = $product;
    $backUrl = $backUrl ?? null;
    $backLabel = $backLabel ?? __('products.back');
    $priceFormatted = $product->price !== null ? rtrim(rtrim(number_format((float) $product->price, 2, '.', ' '), '0'), '.') : null;
@endphp

<div class="max-w-5xl mx-auto">
    @if ($backUrl)
        <div class="mb-6">
            @include('partials.back-link', [
                'fallback' => $backUrl,
                'label' => $backLabel,
            ])
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10">
        {{-- Изображение --}}
        <div class="rounded-2xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] overflow-hidden">
            <div class="aspect-square bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <img src="{{ $product->image_url ?? asset('images/placeholder-product.svg') }}"
                    alt="{{ $product->name }}"
                    class="w-full h-full object-cover {{ $product->image_url ? '' : 'opacity-40' }}"
                    onerror="this.src='{{ asset('images/placeholder-product.svg') }}';this.classList.add('opacity-40');">
            </div>
        </div>

        {{-- Информация --}}
        <div class="flex flex-col">
            @if ($product->category)
                <span class="self-start text-xs font-medium text-[#f59e0b] bg-[#f59e0b]/10 rounded-full px-3 py-1 mb-4">
                    {{ $product->category }}
                </span>
            @endif

            <h1 class="text-3xl font-semibold text-[#0f172a] dark:text-[#EDEDEC] leading-tight">
                {{ $product->name }}
            </h1>

            @if ($product->sku)
                <div class="mt-2 text-sm text-[#94a3b8] dark:text-[#71717a]">
                    {{ __('products.f_sku') }}: <span class="text-[#64748b] dark:text-[#A1A09A]">{{ $product->sku }}</span>
                </div>
            @endif

            @if ($priceFormatted !== null)
                <div class="mt-6 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $priceFormatted }} ₸</span>
                    @if ($product->unit)
                        <span class="text-base text-[#64748b] dark:text-[#A1A09A]">/ {{ $product->unit }}</span>
                    @endif
                </div>
            @endif

            <div class="my-6 h-px bg-[#7c8799]/40 dark:bg-[#3E3E3A]"></div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#64748b] dark:text-[#A1A09A] mb-2">
                    {{ __('products.f_description') }}
                </h2>
                @if ($product->description)
                    <p class="text-[15px] leading-relaxed text-[#334155] dark:text-[#C7C7C2] whitespace-pre-line">{{ $product->description }}</p>
                @else
                    <p class="text-[15px] text-[#94a3b8] dark:text-[#71717a] italic">{{ __('products.no_description') }}</p>
                @endif
            </div>

            {{-- Характеристики --}}
            <dl class="mt-8 grid grid-cols-2 gap-y-4 gap-x-6">
                @if ($product->category)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-[#94a3b8] dark:text-[#71717a]">{{ __('products.f_category') }}</dt>
                        <dd class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $product->category }}</dd>
                    </div>
                @endif
                @if ($product->unit)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-[#94a3b8] dark:text-[#71717a]">{{ __('products.f_unit') }}</dt>
                        <dd class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">{{ $product->unit }}</dd>
                    </div>
                @endif
            </dl>

            @isset($slot)
                {{ $slot }}
            @endisset
        </div>
    </div>
</div>
