@php
    $pickerId = $pickerId ?? 'files';
    $inputName = $inputName ?? 'files[]';
    $multiple = ($multiple ?? true) ? 'true' : 'false';
    $title = $title ?? __('projects.files');
    $subtitle = $subtitle ?? __('projects.files_subtitle');
    $notSelectedText = $notSelectedText ?? __('projects.files_not_selected');
    $selectButtonText = $selectButtonText ?? __('projects.select_files');
    $filesSelectedSuffix = $filesSelectedSuffix ?? __('projects.files_selected');
    $viewLabel = $viewLabel ?? __('projects.view');
    $deleteLabel = $deleteLabel ?? __('objects.delete_file');
@endphp

<div
    data-modal-file-picker
    data-picker-id="{{ $pickerId }}"
    data-input-name="{{ $inputName }}"
    data-multiple="{{ $multiple }}"
    data-label-not-selected="{{ $notSelectedText }}"
    data-label-files-selected="{{ $filesSelectedSuffix }}"
    data-label-view="{{ $viewLabel }}"
    data-label-delete="{{ $deleteLabel }}"
>
    <h3 class="modal-section-title">{{ $title }}</h3>
    <p class="modal-section-subtitle">{{ $subtitle }}</p>
    <div class="flex gap-0 overflow-hidden rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a]">
        <label class="flex-1 flex items-center gap-2 px-4 py-2.5 text-[#64748b] dark:text-[#A1A09A] text-sm cursor-pointer min-h-[2.5rem]">
            <svg class="w-5 h-5 text-[#f59e0b] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.172-1.172a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.172 1.172a4 4 0 01-5.656 0L9.172 7.172a4 4 0 015.656 0l1.172 1.172a4 4 0 010 5.656z"/>
            </svg>
            <span id="{{ $pickerId }}-files-label">{{ $notSelectedText }}</span>
            <input type="file" name="{{ $inputName }}" id="{{ $pickerId }}-files-input" @if($multiple === 'true') multiple @endif class="hidden">
        </label>
        <label for="{{ $pickerId }}-files-input" class="px-4 py-2.5 bg-[#f59e0b] hover:bg-[#d97706] text-white font-medium text-sm cursor-pointer transition-colors shrink-0 flex items-center">
            {{ $selectButtonText }}
        </label>
    </div>
    <div id="{{ $pickerId }}-existing-files" class="mt-3 space-y-2"></div>
    <div id="{{ $pickerId }}-new-files-preview" class="mt-2 space-y-1 text-sm text-[#64748b] dark:text-[#A1A09A]"></div>
</div>
