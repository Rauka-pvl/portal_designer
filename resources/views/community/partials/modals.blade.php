@php
    $userName = trim((string) ($currentUser->name ?? ''));
    $roleLabel = __('community.roles.'.($currentUser->role ?? 'designer'));
@endphp

{{-- Create / Edit modal --}}
<div id="community-post-modal" class="hidden fixed inset-0 z-[80]" role="dialog" aria-modal="true" aria-labelledby="community-post-modal-title">
    <div class="absolute inset-0 bg-black/60 community-modal-backdrop"></div>
    <div class="relative z-10 w-full h-full md:h-auto md:max-h-[90vh] md:max-w-xl md:mx-auto md:mt-[5vh] bg-white dark:bg-[#161615] md:rounded-2xl border-0 md:border border-[#7c8799]/50 dark:border-[#3E3E3A] flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#7c8799]/40 dark:border-[#3E3E3A]">
            <h2 id="community-post-modal-title" class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.new_post') }}</h2>
            <button type="button" class="community-close-post-modal w-10 h-10 inline-flex items-center justify-center rounded-lg text-[#64748b] hover:bg-[#F8FAFC] dark:hover:bg-[#0a0a0a]" aria-label="{{ __('community.cancel') }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="community-post-form" class="flex-1 overflow-y-auto px-5 py-4 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="post_id" id="community-post-id" value="">
            <div class="flex items-center gap-3">
                @include('community.partials.avatar', ['name' => $userName])
                <div>
                    <div class="text-sm font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $userName }}</div>
                    <div class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ $roleLabel }}</div>
                </div>
            </div>
            <textarea id="community-post-text" name="text" rows="5" maxlength="{{ $maxText }}"
                placeholder="{{ __('community.post_placeholder') }}"
                class="w-full rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-transparent px-3 py-3 text-sm text-[#0f172a] dark:text-[#EDEDEC] focus:outline-none focus:ring-2 focus:ring-[#f59e0b]/40 resize-y"></textarea>
            <div id="community-chars" class="hidden text-xs text-[#64748b] dark:text-[#A1A09A] text-right"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-[#64748b] dark:text-[#A1A09A] mb-1" for="community-post-category">{{ __('community.category') }}</label>
                    <select id="community-post-category" name="category" class="w-full h-10 px-2 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-sm">
                        <option value="">{{ __('community.category_optional') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}">{{ __('community.categories.'.$cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-[#64748b] dark:text-[#A1A09A] mb-1" for="community-post-city">{{ __('community.city') }}</label>
                    <input id="community-post-city" name="city" type="text" maxlength="120" value="{{ $currentUser->city ?? '' }}"
                        class="w-full h-10 px-3 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-transparent text-sm">
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('community.add_photos') }} ({{ $maxImages }})</span>
                    <label class="inline-flex items-center gap-1 text-sm text-[#f59e0b] cursor-pointer">
                        <input type="file" id="community-post-images" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" multiple class="hidden">
                        + {{ __('community.composer_photo') }}
                    </label>
                </div>
                <div id="community-image-previews" class="grid grid-cols-3 gap-2"></div>
                <div id="community-upload-progress" class="hidden mt-2 h-1.5 rounded-full bg-[#F8FAFC] dark:bg-[#0a0a0a] overflow-hidden">
                    <div class="h-full bg-[#f59e0b] transition-all" style="width:0%"></div>
                </div>
            </div>
        </form>
        <div class="px-5 py-4 border-t border-[#7c8799]/40 dark:border-[#3E3E3A] flex items-center justify-end gap-2">
            <button type="button" class="community-close-post-modal h-11 px-4 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] text-sm">{{ __('community.cancel') }}</button>
            <button type="button" id="community-submit-post" disabled class="h-11 px-4 rounded-xl bg-[#f59e0b] text-white text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                <span class="label">{{ __('community.publish') }}</span>
            </button>
        </div>
    </div>
</div>

{{-- Delete confirm --}}
<div id="community-delete-modal" class="hidden fixed inset-0 z-[90]" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/60 community-modal-backdrop"></div>
    <div class="relative z-10 w-[min(100%-2rem,28rem)] mx-auto mt-[20vh] rounded-2xl bg-white dark:bg-[#161615] border border-[#7c8799]/50 dark:border-[#3E3E3A] p-5">
        <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.delete_title') }}</h3>
        <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-2">{{ __('community.delete_text') }}</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="community-close-delete h-10 px-4 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] text-sm">{{ __('community.cancel') }}</button>
            <button type="button" id="community-confirm-delete" class="h-10 px-4 rounded-xl bg-red-600 text-white text-sm">{{ __('community.delete_confirm') }}</button>
        </div>
    </div>
</div>

{{-- Report --}}
<div id="community-report-modal" class="hidden fixed inset-0 z-[90]" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/60 community-modal-backdrop"></div>
    <div class="relative z-10 w-[min(100%-2rem,28rem)] mx-auto mt-[15vh] rounded-2xl bg-white dark:bg-[#161615] border border-[#7c8799]/50 dark:border-[#3E3E3A] p-5">
        <h3 class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('community.report_title') }}</h3>
        <form id="community-report-form" class="mt-4 space-y-3">
            <div>
                <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('community.report_reason') }}</label>
                <select name="reason" required class="mt-1 w-full h-10 px-2 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-transparent text-sm">
                    @foreach (__('community.report_reasons') as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-[#64748b] dark:text-[#A1A09A]">{{ __('community.report_comment') }}</label>
                <textarea name="comment" rows="3" class="mt-1 w-full rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] bg-transparent px-3 py-2 text-sm"></textarea>
            </div>
        </form>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="community-close-report h-10 px-4 rounded-xl border border-[#7c8799]/50 dark:border-[#3E3E3A] text-sm">{{ __('community.cancel') }}</button>
            <button type="button" id="community-submit-report" class="h-10 px-4 rounded-xl bg-[#f59e0b] text-white text-sm">{{ __('community.report_submit') }}</button>
        </div>
    </div>
</div>

{{-- Lightbox --}}
<div id="community-lightbox" class="hidden fixed inset-0 z-[100] bg-black/90" role="dialog" aria-modal="true">
    <button type="button" id="community-lightbox-close" class="absolute top-4 right-4 w-11 h-11 text-white" aria-label="{{ __('community.cancel') }}">
        <svg class="w-7 h-7 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <button type="button" id="community-lightbox-prev" class="absolute left-2 md:left-6 top-1/2 -translate-y-1/2 w-11 h-11 text-white" aria-label="prev">‹</button>
    <button type="button" id="community-lightbox-next" class="absolute right-2 md:right-6 top-1/2 -translate-y-1/2 w-11 h-11 text-white" aria-label="next">›</button>
    <div class="w-full h-full flex items-center justify-center p-4">
        <img id="community-lightbox-img" src="" alt="" class="max-w-full max-h-full object-contain select-none">
    </div>
</div>
