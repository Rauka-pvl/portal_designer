{{-- Create/edit modal for a regular DesignerTask. No result fields — those belong to checklist steps only. --}}
<div id="task-form-modal" class="crm-modal-root" aria-hidden="true">
    <div class="crm-modal-backdrop" data-close-backdrop></div>
    <div class="crm-modal task-modal" role="dialog" aria-modal="true" aria-labelledby="task-form-title">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <div id="task-form-title" class="crm-modal-title-input truncate">{{ __('tasks.create_title') }}</div>
                    <p id="task-form-subtitle" class="text-xs text-[var(--crm-muted)] mt-0.5 truncate">{{ __('tasks.create_subtitle') }}</p>
                </div>
                <button type="button" id="task-form-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('tasks.close') }}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <form id="task-form" class="task-form-body" novalidate>
            <input type="hidden" id="tf-id" value="">
            <input type="hidden" id="tf-assignee-hidden" value="">

            <div class="mb-3">
                <label class="crm-label" for="tf-title">{{ __('tasks.field_title') }} *</label>
                <input type="text" id="tf-title" class="crm-input" maxlength="255" required>
                <div class="crm-field-error hidden" data-error="title"></div>
            </div>

            <div class="mb-3">
                <label class="crm-label" for="tf-description">{{ __('tasks.field_description') }}</label>
                <textarea id="tf-description" class="crm-input" rows="3" maxlength="5000"></textarea>
                <div class="crm-field-error hidden" data-error="description"></div>
            </div>

            <div class="crm-grid-2">
                <div>
                    <label class="crm-label" for="tf-assignee">{{ __('tasks.field_assignee') }}</label>
                    @if ($isCorporate ?? false)
                        <select id="tf-assignee" class="crm-input crm-select"></select>
                    @else
                        <input type="text" id="tf-assignee" class="crm-input" value="{{ $currentUser['name'] ?? '' }}" disabled>
                    @endif
                    <div class="crm-field-error hidden" data-error="assignee_id"></div>
                </div>
                <div>
                    <label class="crm-label" for="tf-status">{{ __('tasks.field_status') }}</label>
                    <select id="tf-status" class="crm-input crm-select"></select>
                    <div class="crm-field-error hidden" data-error="status"></div>
                </div>
            </div>

            <div class="crm-grid-2">
                <div>
                    <label class="crm-label" for="tf-project">{{ __('tasks.field_project') }} <span class="text-[var(--crm-muted)]">({{ __('tasks.project_optional') }})</span></label>
                    <select id="tf-project" class="crm-input crm-select"></select>
                    <div class="crm-field-error hidden" data-error="project_id"></div>
                </div>
                <div>
                    <label class="crm-label" for="tf-due">{{ __('tasks.field_due_at') }} *</label>
                    <input type="datetime-local" id="tf-due" class="crm-input" required>
                    <div class="crm-field-error hidden" data-error="due_at"></div>
                </div>
            </div>

            <div id="tf-meta" class="text-xs text-[var(--crm-muted)] mt-1 hidden"></div>
        </form>

        <div class="crm-modal-footer">
            <button type="button" id="task-form-delete" class="crm-btn crm-btn-secondary hidden" style="color:var(--crm-danger)">{{ __('tasks.delete') }}</button>
            <div style="flex:1 1 auto"></div>
            <button type="button" id="task-form-cancel" class="crm-btn crm-btn-secondary">{{ __('tasks.cancel') }}</button>
            <button type="submit" form="task-form" id="task-form-save" class="crm-btn crm-btn-primary">{{ __('tasks.save') }}</button>
        </div>
    </div>
</div>

{{-- Unsaved confirm --}}
<div id="task-unsaved-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-3">{{ __('tasks.unsaved_confirm') }}</h3>
        <div class="flex gap-2 justify-end">
            <button type="button" id="task-unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('tasks.continue_editing') }}</button>
            <button type="button" id="task-unsaved-leave" class="crm-btn crm-btn-primary">{{ __('tasks.leave_without_saving') }}</button>
        </div>
    </div>
</div>

{{-- Delete confirm --}}
<div id="task-delete-modal" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('tasks.delete') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('tasks.delete_confirm') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="task-delete-cancel" class="crm-btn crm-btn-secondary">{{ __('tasks.cancel') }}</button>
            <button type="button" id="task-delete-confirm" class="crm-btn crm-btn-primary" style="background:var(--crm-danger);border-color:var(--crm-danger)">{{ __('tasks.delete') }}</button>
        </div>
    </div>
</div>
