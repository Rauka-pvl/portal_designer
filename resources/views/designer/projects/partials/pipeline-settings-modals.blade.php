{{-- Pipeline settings modal (account owner only) --}}
@if ($canManage)
<div id="pipeline-modal" class="crm-confirm-root crm-pipeline-root" style="z-index:85" aria-hidden="true">
    <div class="crm-pipeline-modal" role="dialog" aria-modal="true" aria-labelledby="pipeline-modal-title">
        <div class="crm-pipeline-header">
            <div class="min-w-0">
                <h3 id="pipeline-modal-title" class="crm-pipeline-heading">{{ __('projects.pipeline_settings') }}</h3>
                <p class="crm-pipeline-subtitle">{{ __('projects.pipeline_settings_subtitle') }}</p>
            </div>
            <button type="button" id="pipeline-close" class="crm-pipeline-icon-btn" aria-label="{{ __('projects.pipeline_close') }}" title="{{ __('projects.pipeline_close') }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="crm-pipeline-body">
            <div id="pipeline-stages-list" class="crm-pipeline-list"></div>

            <div class="crm-pipeline-add-block">
                <div class="crm-pipeline-add-label">{{ __('projects.pipeline_add_stage') }}</div>
                <div class="crm-pipeline-add-row">
                    <label class="crm-pipeline-swatch" title="{{ __('projects.pipeline_color') }}">
                        <input type="color" id="pipeline-new-color" value="#64748b" aria-label="{{ __('projects.pipeline_color') }}">
                        <span class="crm-pipeline-swatch-face" id="pipeline-new-swatch" style="--swatch:#64748b"></span>
                    </label>
                    <input type="text" id="pipeline-new-name" class="crm-input crm-pipeline-name-input" maxlength="120" placeholder="{{ __('projects.pipeline_new_stage_placeholder') }}" autocomplete="off">
                    <button type="button" id="pipeline-add" class="crm-btn crm-btn-secondary crm-btn-sm crm-pipeline-add-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        <span>{{ __('projects.pipeline_add') }}</span>
                    </button>
                </div>
                <div id="pipeline-new-error" class="crm-pipeline-row-error hidden"></div>
            </div>
        </div>

        <div class="crm-pipeline-footer">
            <button type="button" id="pipeline-cancel" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
            <button type="button" id="pipeline-save" class="crm-btn crm-btn-primary">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                <span>{{ __('projects.save') }}</span>
            </button>
        </div>
    </div>
    <div class="crm-confirm-backdrop" data-pipeline-backdrop></div>
</div>

<div id="pipeline-unsaved-modal" class="crm-confirm-root" aria-hidden="true" style="z-index:95">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('projects.unsaved_leave_title') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('projects.unsaved_leave_body') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="pipeline-unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('projects.continue_editing') }}</button>
            <button type="button" id="pipeline-unsaved-leave" class="crm-btn crm-btn-primary">{{ __('projects.leave_without_saving') }}</button>
        </div>
    </div>
</div>

<div id="pipeline-move-modal" class="crm-confirm-root" aria-hidden="true" style="z-index:96">
    <div class="crm-card p-5 w-[min(440px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('projects.pipeline_delete_stage') }}</h3>
        <p id="pipeline-move-text" class="text-sm text-[var(--crm-muted)] mb-2"></p>
        <p id="pipeline-move-count" class="text-xs text-[var(--crm-muted)] mb-3"></p>
        <label class="crm-label" for="pipeline-move-target">{{ __('projects.pipeline_move_target') }}</label>
        <select id="pipeline-move-target" class="crm-input mb-4"></select>
        <div class="flex gap-2 justify-end">
            <button type="button" id="pipeline-move-cancel" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
            <button type="button" id="pipeline-move-confirm" class="crm-btn crm-btn-primary">{{ __('projects.pipeline_move_confirm') }}</button>
        </div>
    </div>
</div>

<div id="pipeline-confirm-modal" class="crm-confirm-root" aria-hidden="true" style="z-index:96">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('projects.pipeline_delete_stage') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('projects.pipeline_delete_confirm') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="pipeline-confirm-cancel" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
            <button type="button" id="pipeline-confirm-ok" class="crm-btn crm-btn-primary">{{ __('projects.pipeline_delete_stage') }}</button>
        </div>
    </div>
</div>
@endif
