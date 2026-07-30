{{-- Checklist create / view / unsaved modals for CRM project card --}}
<div id="checklist-modal-root" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-modal crm-checklist-modal" role="dialog" aria-modal="true" aria-labelledby="checklist-modal-title">
        <div class="crm-modal-header crm-checklist-modal-header">
            <div class="crm-checklist-modal-heading min-w-0">
                <h2 id="checklist-modal-title" class="text-base font-semibold truncate">{{ __('projects.new_checklist_title') }}</h2>
                <p id="checklist-modal-subtitle" class="text-xs text-[var(--crm-muted)] truncate mt-0.5"></p>
            </div>
            <button type="button" id="checklist-close" class="crm-checklist-close" aria-label="{{ __('projects.cancel') }}">✕</button>
        </div>

        <div class="crm-modal-body crm-checklist-modal-body" id="checklist-modal-body">
            <form id="checklist-form" autocomplete="off">
                <input type="hidden" id="checklist-editing-id" value="">

                <div id="checklist-stage-exists-box" class="crm-checklist-exists hidden">
                    <div id="checklist-stage-exists-text" class="mb-2 text-sm"></div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="checklist-open-existing" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('projects.checklist_open_existing') }}</button>
                        <button type="button" id="checklist-cancel-create" class="crm-btn crm-btn-ghost crm-btn-sm">{{ __('projects.checklist_cancel_create') }}</button>
                    </div>
                </div>

                <div id="checklist-form-fields" class="crm-checklist-form-grid">
                    <aside class="crm-checklist-form-left">
                        <div class="crm-checklist-panel">
                            <div>
                                <label class="crm-label" for="checklist-name">{{ __('projects.checklist_name') }}</label>
                                <input type="text" id="checklist-name" class="crm-input" placeholder="{{ __('projects.checklist_name_placeholder') }}" maxlength="255">
                                <div class="crm-field-error hidden" data-error="name"></div>
                            </div>

                            <div>
                                <label class="crm-label" for="checklist-stage-type">{{ __('projects.project_stages') }} *</label>
                                <select id="checklist-stage-type" class="crm-input" required>
                                    <option value="">{{ __('projects.select_stage_placeholder') }}</option>
                                </select>
                                <div class="crm-field-error hidden" data-error="stage_type"></div>
                            </div>

                            <div class="crm-checklist-meta-row">
                                <div>
                                    <label class="crm-label" for="checklist-responsible">{{ __('projects.responsible') }}</label>
                                    <select id="checklist-responsible" class="crm-input"></select>
                                </div>
                                <div>
                                    <label class="crm-label" for="checklist-deadline">{{ __('projects.deadline') }}</label>
                                    <input type="date" id="checklist-deadline" class="crm-input">
                                </div>
                            </div>

                            <div class="crm-checklist-templates-block">
                                <div class="crm-checklist-section-head">
                                    <label class="crm-label mb-0">{{ __('projects.checklist_templates') }}</label>
                                    <button type="button" id="checklist-use-empty" class="crm-btn crm-btn-ghost crm-btn-sm">{{ __('projects.checklist_empty_template') }}</button>
                                </div>
                                <div id="checklist-templates-list" class="crm-checklist-templates"></div>
                            </div>

                            <label class="crm-checklist-save-tpl">
                                <input type="checkbox" id="checklist-save-template" class="rounded">
                                <span>{{ __('projects.checklist_save_as_template') }}</span>
                            </label>
                            <div id="checklist-template-name-wrap" class="hidden">
                                <input type="text" id="checklist-template-name" class="crm-input" placeholder="{{ __('projects.template_name_placeholder') }}">
                            </div>
                        </div>
                    </aside>

                    <section class="crm-checklist-form-right">
                        <div class="crm-checklist-panel crm-checklist-steps-panel">
                            <div class="crm-checklist-section-head">
                                <div class="flex items-center gap-2 min-w-0">
                                    <h3 class="text-sm font-semibold mb-0">{{ __('projects.checklist_steps_heading') }}</h3>
                                    <span id="checklist-steps-count" class="crm-board-col-count">0</span>
                                </div>
                                <button type="button" id="checklist-add-step" class="crm-btn crm-btn-secondary crm-btn-sm">+ {{ __('projects.add_item') }}</button>
                            </div>
                            <div id="checklist-steps-editor" class="crm-checklist-steps-editor"></div>
                            <div class="crm-field-error hidden" data-error="steps"></div>
                        </div>
                    </section>
                </div>
            </form>
        </div>

        <div class="crm-modal-footer crm-checklist-modal-footer" id="checklist-create-footer">
            <button type="button" id="checklist-cancel" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
            <button type="button" id="checklist-submit" class="crm-btn crm-btn-primary">{{ __('projects.create_checklist') }}</button>
        </div>
    </div>
    <div class="crm-confirm-backdrop" data-checklist-close-backdrop></div>
</div>

<div id="checklist-detail-root" class="crm-confirm-root" aria-hidden="true">
    <div class="crm-modal crm-checklist-modal" role="dialog" aria-modal="true">
        <div class="crm-modal-header crm-checklist-modal-header">
            <div class="crm-checklist-modal-heading min-w-0">
                <h2 id="checklist-detail-title" class="text-base font-semibold truncate"></h2>
                <p id="checklist-detail-meta" class="text-xs text-[var(--crm-muted)] truncate mt-0.5"></p>
            </div>
            <div class="crm-checklist-header-actions">
                <a href="#" id="checklist-detail-open-project" class="crm-btn crm-btn-ghost crm-btn-sm hidden">{{ __('projects.checklist_open_project') }}</a>
                <button type="button" id="checklist-detail-edit" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('projects.checklist_edit') }}</button>
                <button type="button" id="checklist-detail-close" class="crm-checklist-close" aria-label="{{ __('projects.cancel') }}">✕</button>
            </div>
        </div>
        <div class="crm-modal-body crm-checklist-detail-layout">
            <div class="crm-checklist-detail-main">
                <div id="checklist-detail-progress" class="mb-3"></div>
                <div id="checklist-detail-steps" class="space-y-1"></div>
            </div>
            <aside class="crm-checklist-detail-side">
                <h3 class="text-sm font-semibold mb-2">{{ __('projects.checklist_result') }}</h3>
                <div id="checklist-detail-result-empty" class="text-xs text-[var(--crm-muted)]">{{ __('projects.checklist_select_step') }}</div>
                <div id="checklist-detail-result-box" class="hidden space-y-2">
                    <div id="checklist-detail-step-title" class="text-sm font-medium"></div>
                    <textarea id="checklist-detail-result" class="crm-input" rows="8" placeholder="{{ __('projects.step_result_comment_placeholder') }}"></textarea>
                    <div id="checklist-detail-result-hint" class="text-xs text-[var(--crm-muted)]"></div>
                </div>
            </aside>
        </div>
        <div class="crm-modal-footer hidden" id="checklist-edit-footer">
            <button type="button" id="checklist-detail-cancel-edit" class="crm-btn crm-btn-secondary">{{ __('projects.cancel') }}</button>
            <button type="button" id="checklist-detail-save" class="crm-btn crm-btn-primary">{{ __('projects.save') }}</button>
        </div>
    </div>
    <div class="crm-confirm-backdrop" data-checklist-detail-backdrop></div>
</div>

<div id="checklist-unsaved-modal" class="crm-confirm-root" aria-hidden="true" style="z-index:95">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('projects.unsaved_leave_title') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('projects.unsaved_leave_body') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="checklist-unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('projects.continue_editing') }}</button>
            <button type="button" id="checklist-unsaved-leave" class="crm-btn crm-btn-primary">{{ __('projects.leave_without_saving') }}</button>
        </div>
    </div>
</div>
