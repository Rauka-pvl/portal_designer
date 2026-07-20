{{-- QR modal shared by product list + product show --}}
<div id="product-qr-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="product-qr-modal-title">
    <div class="w-full max-w-md rounded-2xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
            <h3 id="product-qr-modal-title" class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('products.qr_title') }}</h3>
            <button type="button" id="product-qr-close" class="w-11 h-11 inline-flex items-center justify-center rounded-xl text-[#64748b] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]" aria-label="{{ __('products.cancel') }}">✕</button>
        </div>
        <div class="px-5 py-5 space-y-4">
            <div class="flex items-center gap-3 min-w-0">
                <img id="product-qr-image" src="" alt="" class="hidden w-12 h-12 rounded-xl object-cover border border-[#7c8799]/40 dark:border-[#3E3E3A]">
                <div class="min-w-0">
                    <div id="product-qr-name" class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC] truncate"></div>
                    <div id="product-qr-sku" class="text-xs text-[#64748b] dark:text-[#A1A09A] truncate"></div>
                </div>
            </div>

            <div id="product-qr-preview" class="mx-auto w-48 h-48 rounded-2xl border border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white p-3 flex items-center justify-center overflow-hidden" aria-label="{{ __('products.qr_aria') }}">
                <div class="text-xs text-[#94a3b8]">…</div>
            </div>

            <p class="text-sm text-center text-[#64748b] dark:text-[#A1A09A]">{{ __('products.qr_scan_modal_hint') }}</p>

            <div class="rounded-xl bg-[#f8fafc] dark:bg-[#0a0a0a] px-3 py-2">
                <input id="product-qr-url" type="text" readonly
                    class="w-full bg-transparent text-xs text-[#0f172a] dark:text-[#EDEDEC] truncate focus:outline-none"
                    aria-label="{{ __('products.qr_copy_link') }}">
            </div>

            <a id="product-qr-download-png"
                class="w-full inline-flex min-h-11 items-center justify-center rounded-xl bg-[#f59e0b] px-4 text-sm font-medium text-white hover:bg-[#d97706]">
                {{ __('products.qr_download_png') }}
            </a>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <button type="button" id="product-qr-copy"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] px-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:border-[#f59e0b]">
                    {{ __('products.qr_copy_link') }}
                </button>
                <a id="product-qr-download-svg"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] px-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:border-[#f59e0b]">
                    {{ __('products.qr_download_svg') }}
                </a>
                <a id="product-qr-print" target="_blank" rel="noopener"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] px-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] hover:border-[#f59e0b]">
                    {{ __('products.qr_print') }}
                </a>
            </div>

            <div class="flex items-center justify-between gap-3 pt-1">
                <a id="product-qr-open-link" class="text-sm text-[#f59e0b] hover:underline">{{ __('products.qr_open') }}</a>
                <details class="text-sm">
                    <summary class="cursor-pointer text-[#64748b] dark:text-[#A1A09A] list-none">{{ __('products.qr_more_actions') }}</summary>
                    <button type="button" id="product-qr-reissue" class="mt-2 text-[#ef4444] hover:underline">{{ __('products.qr_reissue') }}</button>
                </details>
            </div>

            <p class="text-[11px] text-[#94a3b8] dark:text-[#71717a]">{{ __('products.qr_stable_note') }}</p>
            <p id="product-qr-error" class="hidden text-sm text-[#ef4444]"></p>
        </div>
    </div>
</div>
