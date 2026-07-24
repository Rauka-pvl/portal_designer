{{-- Unsaved changes confirm --}}
<div id="detail-unsaved-modal" class="hidden fixed inset-0 z-[80] items-center justify-center p-4 bg-black/50" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#161615] border border-[#7c8799]/50 dark:border-[#3E3E3A] p-5 shadow-xl">
        <h3 class="text-lg font-semibold text-[#0f172a] dark:text-[#EDEDEC]">{{ __('detail.unsaved_title') }}</h3>
        <p class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A]">{{ __('detail.unsaved_body') }}</p>
        <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" data-unsaved-stay
                class="min-h-10 px-4 rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] text-sm hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                {{ __('detail.continue_editing') }}
            </button>
            <button type="button" data-unsaved-leave
                class="min-h-10 px-4 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-500 transition-colors">
                {{ __('detail.leave_without_saving') }}
            </button>
        </div>
    </div>
</div>
