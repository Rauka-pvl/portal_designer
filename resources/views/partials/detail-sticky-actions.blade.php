{{-- Sticky unsaved changes bar --}}
<div id="detail-sticky-actions"
    class="hidden fixed bottom-0 inset-x-0 z-40 border-t border-[#7c8799]/40 dark:border-[#3E3E3A] bg-white/95 dark:bg-[#161615]/95 backdrop-blur px-4 py-3 lg:pl-[calc(16rem+1rem)]">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC]" data-unsaved-hint>{{ $message ?? __('detail.unsaved_changes') }}</p>
        <div class="flex gap-2">
            <button type="button" id="btn-cancel"
                class="min-h-10 px-4 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                {{ $cancelLabel ?? __('detail.cancel') }}
            </button>
            <button type="submit" form="{{ $formId }}" id="btn-save" disabled
                class="min-h-10 px-4 rounded-xl bg-[#f59e0b] text-white text-sm font-medium hover:opacity-95 disabled:opacity-50 transition-opacity">
                {{ $saveLabel ?? __('detail.save_changes') }}
            </button>
        </div>
    </div>
</div>
