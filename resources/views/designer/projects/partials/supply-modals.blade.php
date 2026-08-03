{{-- Supply create / catalog / detail modals — portaled to body by JS --}}
<div id="supply-modal-root" class="crm-modal-root" aria-hidden="true" style="z-index:90">
    <div class="crm-modal-backdrop" data-supply-close-backdrop></div>
    <div class="crm-modal crm-supply-modal" role="dialog" aria-modal="true" aria-labelledby="supply-modal-title">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <h2 id="supply-modal-title" class="text-base font-semibold truncate">{{ __('projects.new_supply_title') }}</h2>
                    <p id="supply-modal-subtitle" class="text-xs text-[var(--crm-muted)] truncate mt-0.5"></p>
                </div>
                <button type="button" id="supply-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('projects.close') }}">✕</button>
            </div>
        </div>
        <form id="supply-form" class="crm-modal-work" style="display:flex;flex-direction:column" enctype="multipart/form-data" data-ajax="1">
            <input type="hidden" name="project_id" id="supply-project-id" value="">
            <input type="hidden" name="send_to_supplier" id="supply-send-flag" value="0">
            <input type="hidden" name="product_items" id="supply-product-items" value="">
            <input type="hidden" name="order_id" id="supply-order-id" value="">
            <div class="crm-modal-main" style="flex:1;overflow:auto">
                <div class="crm-section">
                    <div class="crm-section-title">{{ __('supplier-orders.section_main') }}</div>
                    <div class="text-sm space-y-1 mb-3">
                        <div><span class="text-[var(--crm-muted)]">{{ __('projects.supply_context_project') }}:</span> <strong id="supply-ctx-project">—</strong></div>
                        <div><span class="text-[var(--crm-muted)]">{{ __('projects.supply_context_client') }}:</span> <strong id="supply-ctx-client">—</strong></div>
                    </div>
                    <div class="mb-3">
                        <label class="crm-label" for="supply-supplier-id">{{ __('supplier-orders.supplier') }} *</label>
                        <select id="supply-supplier-id" name="supplier_id" class="crm-input crm-select" required></select>
                        <div class="crm-field-error hidden" data-error="supplier_id"></div>
                    </div>
                    <div class="crm-grid-2 mb-3">
                        <div>
                            <label class="crm-label" for="supply-summa">{{ __('supplier-orders.amount') }} *</label>
                            <input type="number" name="summa" id="supply-summa" min="0" step="1" required class="crm-input" placeholder="0">
                            <div class="crm-field-error hidden" data-error="summa"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="supply-bonus">{{ __('supplier-orders.bonus_percent') }}</label>
                            <input type="text" inputmode="decimal" name="bonus_percent" id="supply-bonus" class="crm-input" placeholder="0">
                            <div class="text-[10px] text-[var(--crm-muted)] mt-1" id="supply-bonus-hint"></div>
                        </div>
                    </div>
                    <div class="crm-grid-3 mb-3">
                        <div>
                            <label class="crm-label" for="supply-category">{{ __('supplier-orders.category') }}</label>
                            <select name="category" id="supply-category" class="crm-input crm-select"></select>
                        </div>
                        <div>
                            <label class="crm-label" for="supply-mark">{{ __('supplier-orders.mark_on_drawing') }}</label>
                            <input type="text" name="mark" id="supply-mark" class="crm-input">
                        </div>
                        <div>
                            <label class="crm-label" for="supply-room">{{ __('supplier-orders.room') }}</label>
                            <select name="room" id="supply-room" class="crm-input crm-select"></select>
                        </div>
                    </div>
                </div>

                <div class="crm-section" id="supply-products-section">
                    <div class="flex items-center justify-between mb-2">
                        <div class="crm-section-title mb-0">{{ __('projects.supply_products') }}</div>
                        <button type="button" id="supply-open-catalog" class="crm-btn crm-btn-secondary crm-btn-sm">+ {{ __('projects.supply_add_products') }}</button>
                    </div>
                    <div id="supply-products-list" class="space-y-2 text-sm"></div>
                    <div class="flex justify-between text-sm mt-2 pt-2 border-t border-[color-mix(in_srgb,var(--crm-border)_25%,transparent)]">
                        <span class="text-[var(--crm-muted)]">{{ __('projects.supply_total') }}</span>
                        <strong id="supply-products-total">0 ₸</strong>
                    </div>
                </div>

                <div class="crm-section">
                    <div class="crm-supply-steps-head">
                        <div class="crm-section-title crm-section-title-plain mb-0">{{ __('projects.supply_checklist_materials') }}</div>
                        <p class="crm-supply-steps-hint">{{ __('projects.supply_checklist_hint') }}</p>
                    </div>
                    <div id="supply-steps-box" class="crm-supply-steps-box"></div>
                </div>

                <div class="crm-section">
                    <div class="crm-section-title">{{ __('projects.supply_payment') }}</div>
                    <div class="crm-grid-2 mb-3">
                        <div>
                            <label class="crm-label" for="supply-date-planned">{{ __('supplier-orders.planned_date') }} *</label>
                            <input type="date" name="date_planned" id="supply-date-planned" required class="crm-input">
                            <div class="crm-field-error hidden" data-error="date_planned"></div>
                        </div>
                        <div>
                            <label class="crm-label" for="supply-date-actual">{{ __('supplier-orders.actual_date') }}</label>
                            <input type="date" name="date_actual" id="supply-date-actual" class="crm-input">
                        </div>
                    </div>
                    <div class="crm-grid-2 mb-3">
                        <div>
                            <label class="crm-label" for="supply-prepay-date">{{ __('projects.prepayment_date') }}</label>
                            <input type="date" name="prepayment_date" id="supply-prepay-date" class="crm-input">
                        </div>
                        <div>
                            <label class="crm-label" for="supply-prepay-amount">{{ __('projects.prepayment_amount') }}</label>
                            <input type="number" name="prepayment_amount" id="supply-prepay-amount" min="0" step="1" class="crm-input">
                        </div>
                    </div>
                    <div class="crm-grid-2">
                        <div>
                            <label class="crm-label" for="supply-pay-date">{{ __('projects.payment_date') }}</label>
                            <input type="date" name="payment_date" id="supply-pay-date" class="crm-input">
                        </div>
                        <div>
                            <label class="crm-label" for="supply-pay-amount">{{ __('projects.payment_amount') }}</label>
                            <input type="number" name="payment_amount" id="supply-pay-amount" min="0" step="1" class="crm-input">
                        </div>
                    </div>
                </div>

                <div class="crm-section">
                    <div class="crm-section-title">{{ __('supplier-orders.links') }} / {{ __('projects.files') }}</div>
                    <div id="supply-links" class="space-y-2 mb-3"></div>
                    <button type="button" id="supply-add-link" class="crm-btn crm-btn-ghost crm-btn-sm mb-3">+ {{ __('projects.add_link') }}</button>
                    <label class="crm-label" for="supply-files">{{ __('projects.files') }}</label>
                    <input type="file" name="files[]" id="supply-files" class="crm-input" multiple>
                    <div id="supply-files-list" class="mt-2 space-y-1 text-xs"></div>
                    <div class="mt-3">
                        <label class="crm-label" for="supply-comment">{{ __('supplier-orders.product_service') }}</label>
                        <textarea name="comment" id="supply-comment" rows="3" class="crm-input"></textarea>
                    </div>
                </div>
            </div>
            <div class="crm-modal-footer flex-wrap">
                <button type="button" id="supply-cancel" class="crm-btn crm-btn-ghost">{{ __('projects.cancel') }}</button>
                <div class="flex gap-2 ml-auto flex-wrap">
                    <button type="submit" name="action" value="save" id="supply-save-btn" class="crm-btn crm-btn-secondary">{{ __('supplier-orders.save_without_send') }}</button>
                    <button type="submit" name="action" value="send" id="supply-send-btn" class="crm-btn crm-btn-primary">{{ __('supplier-orders.send_to_supplier') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Nested product catalog --}}
<div id="supply-catalog-root" class="crm-modal-root" aria-hidden="true" style="z-index:95">
    <div class="crm-modal-backdrop" data-catalog-close></div>
    <div class="crm-modal crm-supply-modal" role="dialog" aria-modal="true">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold">{{ __('projects.supply_catalog_title') }}</h2>
                    <p id="catalog-supplier-name" class="text-xs text-[var(--crm-muted)]"></p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="catalog-selected-count" class="text-xs text-[var(--crm-muted)]"></span>
                    <button type="button" id="catalog-close" class="crm-btn crm-btn-ghost">✕</button>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                <input type="search" id="catalog-search" class="crm-input crm-toolbar-search" placeholder="{{ __('projects.search') }}">
                <select id="catalog-category" class="crm-input" style="width:auto;min-width:10rem">
                    <option value="">{{ __('supplier-orders.select_category') }}</option>
                </select>
            </div>
        </div>
        <div class="crm-modal-work is-split" style="grid-template-columns:1fr 280px">
            <div class="crm-modal-main" id="catalog-grid"></div>
            <aside class="crm-modal-feed" id="catalog-cart-panel">
                <h3 class="text-sm font-semibold mb-2">{{ __('projects.supply_products') }}</h3>
                <div id="catalog-cart" class="space-y-2 text-sm flex-1 overflow-auto"></div>
                <div class="mt-3 pt-2 border-t border-[color-mix(in_srgb,var(--crm-border)_25%,transparent)]">
                    <div class="flex justify-between text-sm mb-2">
                        <span>{{ __('projects.supply_total') }}</span>
                        <strong id="catalog-cart-total">0 ₸</strong>
                    </div>
                    <button type="button" id="catalog-apply" class="crm-btn crm-btn-primary w-full">{{ __('projects.supply_add_selected') }}</button>
                </div>
            </aside>
        </div>
    </div>
</div>

{{-- Supply detail / offer --}}
<div id="supply-detail-root" class="crm-modal-root" aria-hidden="true" style="z-index:90">
    <div class="crm-modal-backdrop" data-detail-close></div>
    <div class="crm-modal crm-supply-modal" role="dialog" aria-modal="true">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <h2 id="supply-detail-title" class="text-base font-semibold truncate"></h2>
                    <p id="supply-detail-meta" class="text-xs text-[var(--crm-muted)] truncate mt-0.5"></p>
                </div>
                <button type="button" id="supply-detail-close" class="crm-btn crm-btn-ghost">✕</button>
            </div>
        </div>
        <div class="crm-modal-main" id="supply-detail-body" style="flex:1;overflow:auto;padding:0.85rem 1rem"></div>
        <div class="crm-modal-footer">
            <button type="button" id="supply-detail-chat" class="crm-btn crm-btn-secondary relative">
                {{ __('supplier-orders.chat_open') }}
                <span id="supply-detail-chat-badge" class="crm-supply-chat-badge is-hidden">0</span>
            </button>
            <button type="button" id="supply-detail-edit" class="crm-btn crm-btn-secondary">{{ __('supplier-orders.edit_action') }}</button>
            <button type="button" id="supply-detail-close-2" class="crm-btn crm-btn-ghost ml-auto">{{ __('projects.close') }}</button>
        </div>
    </div>
</div>

{{-- Supply chat with supplier --}}
<div id="supply-chat-root" class="crm-modal-root" aria-hidden="true" style="z-index:95">
    <div class="crm-modal-backdrop" data-supply-chat-close></div>
    <div class="crm-supply-chat-panel" role="dialog" aria-modal="true" aria-labelledby="supply-chat-title">
        <div class="crm-modal-header">
            <div class="crm-modal-header-row">
                <div class="min-w-0">
                    <h2 id="supply-chat-title" class="text-base font-semibold truncate">{{ __('supplier-orders.chat_title') }}</h2>
                    <p id="supply-chat-subtitle" class="text-xs text-[var(--crm-muted)] truncate mt-0.5"></p>
                </div>
                <button type="button" id="supply-chat-close" class="crm-btn crm-btn-ghost" aria-label="{{ __('projects.close') }}">✕</button>
            </div>
            <div class="flex items-center justify-between gap-2 mt-2">
                <p class="text-xs text-[var(--crm-muted)]">{{ __('supplier-orders.chat_hint_manual_refresh') }}</p>
                <button type="button" id="supply-chat-refresh" class="crm-btn crm-btn-ghost crm-btn-sm">{{ __('supplier-orders.chat_refresh') }}</button>
            </div>
        </div>
        <div id="supply-chat-messages" class="crm-supply-chat-messages"></div>
        <form id="supply-chat-form" class="crm-supply-chat-form">
            <input type="hidden" id="supply-chat-order-id" value="">
            <textarea id="supply-chat-input" rows="2" maxlength="5000" class="crm-input flex-1" placeholder="{{ __('supplier-orders.chat_placeholder') }}"></textarea>
            <button type="submit" class="crm-btn crm-btn-primary">{{ __('supplier-orders.chat_send') }}</button>
        </form>
    </div>
</div>

<div id="supply-unsaved-modal" class="crm-confirm-root" aria-hidden="true" style="z-index:100">
    <div class="crm-card p-5 w-[min(420px,92vw)] relative z-10">
        <h3 class="font-semibold mb-1">{{ __('supplier-orders.unsaved_title') }}</h3>
        <p class="text-sm text-[var(--crm-muted)] mb-4">{{ __('projects.unsaved_leave_body') }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="supply-unsaved-continue" class="crm-btn crm-btn-secondary">{{ __('projects.continue_editing') }}</button>
            <button type="button" id="supply-unsaved-leave" class="crm-btn crm-btn-primary">{{ __('projects.leave_without_saving') }}</button>
        </div>
    </div>
</div>
