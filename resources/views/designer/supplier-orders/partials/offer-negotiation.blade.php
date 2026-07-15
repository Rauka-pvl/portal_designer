@php
    $offerInitial = [
        'id' => (int) ($orderData['id'] ?? 0),
        'offer_status' => $orderData['offer_status'] ?? null,
        'offer_ui_state' => $orderData['offer_ui_state'] ?? 'hidden',
        'has_offer_ui' => (bool) ($orderData['has_offer_ui'] ?? false),
        'offer_history' => $orderData['offer_history'] ?? [],
        'bonus_percent' => $orderData['bonus_percent'] ?? null,
        'bonus_amount' => $orderData['bonus_amount'] ?? null,
        'order_amount' => (int) ($orderData['order_amount'] ?? $orderData['summa'] ?? $orderData['amount'] ?? 0),
        'supplier_offer_percent' => $orderData['supplier_offer_percent'] ?? null,
        'supplier_offer_amount' => $orderData['supplier_offer_amount'] ?? null,
        'supplier_offer_at' => $orderData['supplier_offer_at'] ?? null,
        'designer_offer_percent' => $orderData['designer_offer_percent'] ?? null,
        'designer_offer_amount' => $orderData['designer_offer_amount'] ?? null,
        'designer_offer_at' => $orderData['designer_offer_at'] ?? null,
        'decided_at' => $orderData['decided_at'] ?? null,
        'can_accept' => (bool) ($orderData['can_accept'] ?? false),
        'can_reject' => (bool) ($orderData['can_reject'] ?? false),
        'can_counter_offer' => (bool) ($orderData['can_counter_offer'] ?? false),
        'can_view_history' => (bool) ($orderData['can_view_history'] ?? false),
        'status' => $orderData['status'] ?? null,
    ];
    $focusOffer = ! empty($focusOfferSection)
        || request()->query('section') === 'offer'
        || str_contains((string) request()->getRequestUri(), 'offer-negotiation');
@endphp

<div
    id="offer-negotiation"
    class="offer-nego mb-5 hidden"
    data-order-id="{{ $offerInitial['id'] }}"
    data-focus="{{ $focusOffer ? '1' : '0' }}"
>
    <div class="offer-nego__card">
        <div id="offer-negotiation-root"></div>
    </div>
</div>

{{-- Accept confirm --}}
<div id="offer-accept-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/45 offer-modal-backdrop" data-close="accept"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 shadow-lg">
            <h3 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.offer_accept_confirm_title') }}</h3>
            <p id="offer-accept-modal-body" class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A]"></p>
            <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" class="offer-btn offer-btn--ghost h-9 px-4" data-close="accept">{{ __('supplier-orders.offer_cancel') }}</button>
                <button type="button" id="offer-accept-confirm-btn" class="offer-btn offer-btn--primary h-9 px-4">{{ __('supplier-orders.offer_accept') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Reject confirm --}}
<div id="offer-reject-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/45 offer-modal-backdrop" data-close="reject"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 shadow-lg">
            <h3 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.offer_reject_confirm_title') }}</h3>
            <p id="offer-reject-modal-body" class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A]"></p>
            <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" class="offer-btn offer-btn--ghost h-9 px-4" data-close="reject">{{ __('supplier-orders.offer_reject_confirm_back') }}</button>
                <button type="button" id="offer-reject-confirm-btn" class="offer-btn offer-btn--danger h-9 px-4">{{ __('supplier-orders.offer_reject_confirm_btn') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Counter modal --}}
<div id="offer-counter-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/45 offer-modal-backdrop" data-close="counter"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 shadow-lg">
            <h3 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.offer_propose_modal_title') }}</h3>
            <label class="mt-4 block text-sm text-[#64748b] dark:text-[#A1A09A]" for="offer-counter-percent">{{ __('supplier-orders.offer_percent_field') }}</label>
            <div class="relative mt-1.5 max-w-[160px]">
                <input
                    type="text"
                    inputmode="decimal"
                    id="offer-counter-percent"
                    class="w-full h-10 px-3 pr-8 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#0a0a0a] text-[#0f172a] dark:text-[#EDEDEC]"
                    autocomplete="off"
                >
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-[#64748b] dark:text-[#A1A09A]">%</span>
            </div>
            <p id="offer-counter-preview" class="mt-2 text-sm text-[#64748b] dark:text-[#A1A09A]"></p>
            <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" class="offer-btn offer-btn--ghost h-9 px-4" data-close="counter">{{ __('supplier-orders.offer_cancel') }}</button>
                <button type="button" id="offer-counter-submit-btn" class="offer-btn offer-btn--primary h-9 px-4" disabled>{{ __('supplier-orders.offer_counter_send') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- History modal --}}
<div id="offer-history-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/45 offer-modal-backdrop" data-close="history"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-5 shadow-lg max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('supplier-orders.offer_history_title') }}</h3>
                <button type="button" class="text-sm text-[#64748b] dark:text-[#A1A09A] hover:text-[#f59e0b]" data-close="history">{{ __('supplier-orders.close') }}</button>
            </div>
            <div id="offer-history-modal-list" class="space-y-3"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .offer-nego__card {
        border: 1px solid #7c8799;
        border-radius: 13px;
        padding: 18px;
        background: #f8fafc;
        border-left: 3px solid #f59e0b;
        transition: background-color .35s ease, box-shadow .35s ease, border-color .35s ease;
    }
    .dark .offer-nego__card {
        background: #1a1a18;
        border-color: #3E3E3A;
        border-left-color: #f59e0b;
    }
    .offer-nego.is-highlight .offer-nego__card {
        background: rgba(245, 158, 11, 0.08);
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.25);
    }
    .dark .offer-nego.is-highlight .offer-nego__card {
        background: rgba(245, 158, 11, 0.1);
    }
    .offer-nego__badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        line-height: 1;
        padding: 0.35rem 0.55rem;
        border-radius: 999px;
        font-weight: 500;
        white-space: nowrap;
    }
    .offer-nego__badge--pending {
        background: rgba(245, 158, 11, 0.14);
        color: #d97706;
    }
    .dark .offer-nego__badge--pending { color: #fbbf24; }
    .offer-nego__badge--ok {
        background: rgba(34, 197, 94, 0.12);
        color: #16a34a;
    }
    .dark .offer-nego__badge--ok { color: #4ade80; }
    .offer-nego__badge--no {
        background: rgba(100, 116, 139, 0.14);
        color: #64748b;
    }
    .dark .offer-nego__badge--no { color: #A1A09A; }
    .offer-nego__dot {
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #f59e0b;
        flex-shrink: 0;
    }
    .offer-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all .15s ease;
        cursor: pointer;
    }
    .offer-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .offer-btn--primary {
        background: #f59e0b;
        color: #fff;
        border: 1px solid #f59e0b;
    }
    .offer-btn--primary:hover:not(:disabled) { background: #d97706; border-color: #d97706; }
    .offer-btn--secondary {
        background: transparent;
        border: 1px solid #7c8799;
        color: #64748b;
    }
    .dark .offer-btn--secondary {
        border-color: #3E3E3A;
        color: #A1A09A;
    }
    .offer-btn--secondary:hover:not(:disabled) {
        border-color: #f59e0b;
        color: #f59e0b;
    }
    .offer-btn--ghost {
        background: transparent;
        border: 1px solid transparent;
        color: #64748b;
    }
    .dark .offer-btn--ghost { color: #A1A09A; }
    .offer-btn--ghost:hover:not(:disabled) { color: #f59e0b; }
    .offer-btn--danger {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.35);
        color: #dc2626;
    }
    .offer-btn--danger:hover:not(:disabled) { background: rgba(239, 68, 68, 0.2); }
    .offer-timeline-item { position: relative; padding-left: 0; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const wrap = document.getElementById('offer-negotiation');
    const root = document.getElementById('offer-negotiation-root');
    if (!wrap || !root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const locale = '{{ str_replace('_', '-', app()->getLocale()) }}';
    const dateLocale = locale.startsWith('kk') ? 'kk-KZ' : (locale.startsWith('en') ? 'en-GB' : 'ru-RU');
    let order = @json($offerInitial);
    let busy = false;
    let historyExpanded = false;

    const i18n = {
        title: @json(__('supplier-orders.offer_terms_title')),
        subtitle: @json(__('supplier-orders.offer_bonus_percent_subtitle')),
        awaitingYou: @json(__('supplier-orders.offer_awaiting_your_response')),
        awaitingSupplier: @json(__('supplier-orders.offer_awaiting_supplier_response')),
        termsAgreed: @json(__('supplier-orders.offer_terms_agreed')),
        termsRejected: @json(__('supplier-orders.offer_terms_rejected')),
        badgeAgreed: @json(__('supplier-orders.offer_badge_agreed')),
        badgeRejected: @json(__('supplier-orders.offer_badge_rejected')),
        supplierProposed: @json(__('supplier-orders.offer_supplier_proposed')),
        yourProposal: @json(__('supplier-orders.offer_your_proposal')),
        noDesignerProposal: @json(__('supplier-orders.offer_no_designer_proposal')),
        orderSum: @json(__('supplier-orders.offer_order_sum')),
        amountLabel: @json(__('supplier-orders.amount')),
        finalPercent: @json(__('supplier-orders.offer_final_percent')),
        designerBonus: @json(__('supplier-orders.offer_designer_bonus')),
        agreedDate: @json(__('supplier-orders.offer_agreed_date')),
        rejectedDate: @json(__('supplier-orders.offer_rejected_date')),
        acceptPercent: @json(__('supplier-orders.offer_accept_percent')),
        proposeOther: @json(__('supplier-orders.offer_propose_other')),
        reject: @json(__('supplier-orders.offer_reject')),
        showHistory: @json(__('supplier-orders.offer_show_history')),
        byDesigner: @json(__('supplier-orders.offer_by_designer')),
        bySupplier: @json(__('supplier-orders.offer_by_supplier')),
        acceptConfirmBody: @json(__('supplier-orders.offer_accept_confirm_body')),
        rejectConfirmBody: @json(__('supplier-orders.offer_reject_confirm_body')),
        percentPreview: @json(__('supplier-orders.offer_percent_preview')),
        percentSame: @json(__('supplier-orders.offer_percent_same')),
        unavailable: @json(__('supplier-orders.offer_unavailable')),
        lastChange: @json(__('supplier-orders.offer_last_change')),
        lastSupplier: @json(__('supplier-orders.offer_last_supplier_change')),
        lastDesigner: @json(__('supplier-orders.offer_last_designer_change')),
        toastAccepted: @json(__('supplier-orders.offer_accepted')),
        toastRejected: @json(__('supplier-orders.offer_rejected')),
        toastCounter: @json(__('supplier-orders.offer_counter_sent')),
        error: @json(__('supplier-orders.error')),
        acceptedMsg: 'accepted',
        rejectedMsg: 'rejected',
    };

    function money(n) {
        const v = Math.round(Number(n) || 0);
        return v.toLocaleString('kk-KZ') + ' ₸';
    }

    function fmtPercent(p) {
        if (p == null || p === '') return '—';
        const n = Number(p);
        if (Number.isNaN(n)) return '—';
        return (Number.isInteger(n) ? String(n) : String(n)) + '%';
    }

    function fmtDateTime(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        return d.toLocaleString(dateLocale, {
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function fmtDate(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '—';
        return d.toLocaleDateString(dateLocale, { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function relativeFrom(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        if (Number.isNaN(d.getTime())) return '';
        const diffSec = Math.round((d.getTime() - Date.now()) / 1000);
        const rtf = new Intl.RelativeTimeFormat(dateLocale, { numeric: 'auto' });
        const abs = Math.abs(diffSec);
        if (abs < 60) return rtf.format(diffSec, 'second');
        const min = Math.round(diffSec / 60);
        if (Math.abs(min) < 60) return rtf.format(min, 'minute');
        const hr = Math.round(diffSec / 3600);
        if (Math.abs(hr) < 48) return rtf.format(hr, 'hour');
        const day = Math.round(diffSec / 86400);
        return rtf.format(day, 'day');
    }

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function calcBonus(amount, percent) {
        if (percent == null || percent === '' || Number.isNaN(Number(percent))) return null;
        return Math.round(Number(amount || 0) * Number(percent) / 100);
    }

    function proposalHistory() {
        const history = Array.isArray(order.offer_history) ? order.offer_history : [];
        return history.filter((h) => {
            const msg = String(h.message || '').toLowerCase();
            return !['accepted', 'rejected'].includes(msg);
        });
    }

    function lastHistoryEntry() {
        const list = proposalHistory();
        return list.length ? list[list.length - 1] : null;
    }

    function badgeHtml(state) {
        if (state === 'pending_designer') {
            return `<span class="offer-nego__badge offer-nego__badge--pending"><span class="offer-nego__dot"></span>${esc(i18n.awaitingYou)}</span>`;
        }
        if (state === 'pending_supplier') {
            return `<span class="offer-nego__badge offer-nego__badge--pending"><span class="offer-nego__dot"></span>${esc(i18n.awaitingSupplier)}</span>`;
        }
        if (state === 'accepted') {
            return `<span class="offer-nego__badge offer-nego__badge--ok">${esc(i18n.badgeAgreed)}</span>`;
        }
        if (state === 'rejected') {
            return `<span class="offer-nego__badge offer-nego__badge--no">${esc(i18n.badgeRejected)}</span>`;
        }
        return `<span class="offer-nego__badge offer-nego__badge--no">${esc(i18n.unavailable)}</span>`;
    }

    function timelineHtml(items, limit) {
        const slice = typeof limit === 'number' ? items.slice(-limit) : items;
        if (!slice.length) return '';
        return `<div class="mt-3 space-y-2">
            ${slice.map((h, idx) => {
                const who = h.by === 'supplier' ? i18n.bySupplier : i18n.byDesigner;
                const line = `${fmtPercent(h.percent)}${h.at ? ' · ' + fmtDateTime(h.at) : ''}`;
                const arrow = idx < slice.length - 1
                    ? `<div class="pl-0.5 text-[#94a3b8] dark:text-[#706f6c] text-xs leading-none my-0.5">↓</div>`
                    : '';
                return `<div class="offer-timeline-item">
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(who)}</p>
                    <p class="text-sm text-[#0f172a] dark:text-[#EDEDEC]">${esc(line)}</p>
                    ${arrow}
                </div>`;
            }).join('')}
        </div>`;
    }

    function lastChangeHtml() {
        const last = lastHistoryEntry();
        if (!last) return '';
        const tpl = last.by === 'supplier' ? i18n.lastSupplier : i18n.lastDesigner;
        const text = tpl.replace(':percent', String(last.percent ?? '—'));
        const rel = relativeFrom(last.at);
        const body = i18n.lastChange.replace(':text', text + (rel ? ' · ' + rel : ''));
        return `<p class="mt-3 text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(body)}</p>`;
    }

    function render() {
        const state = order.offer_ui_state || 'hidden';
        if (!order.has_offer_ui || state === 'hidden') {
            wrap.classList.add('hidden');
            return;
        }
        wrap.classList.remove('hidden');

        if (state === 'unavailable') {
            root.innerHTML = `
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1">
                    <div>
                        <h2 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">${esc(i18n.title)}</h2>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">${esc(i18n.subtitle)}</p>
                    </div>
                    ${badgeHtml(state)}
                </div>
                <p class="mt-3 text-sm text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.unavailable)}</p>`;
            return;
        }

        const amount = Number(order.order_amount || 0);
        const supplierPct = order.supplier_offer_percent;
        const designerPct = order.designer_offer_percent;
        const pendingPct = order.bonus_percent;
        const history = proposalHistory();
        const previewCount = 2;
        const showHistoryBtn = history.length > previewCount && order.can_view_history;

        let metrics = '';
        if (state === 'accepted') {
            metrics = `
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.finalPercent)}</p>
                        <p class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">${esc(fmtPercent(order.bonus_percent))}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.designerBonus)}</p>
                        <p class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">${esc(money(order.bonus_amount))}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.agreedDate)}</p>
                        <p class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${esc(fmtDate(order.decided_at))}</p>
                    </div>
                </div>`;
        } else if (state === 'rejected') {
            metrics = `
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.supplierProposed)}</p>
                        <p class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                            ${esc(fmtPercent(supplierPct ?? pendingPct))}
                            ${supplierPct != null || pendingPct != null ? `<span class="text-sm font-normal text-[#64748b] dark:text-[#A1A09A] ml-1">${esc(money(calcBonus(amount, supplierPct ?? pendingPct)))}</span>` : ''}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.rejectedDate)}</p>
                        <p class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${esc(fmtDate(order.decided_at))}</p>
                    </div>
                </div>`;
        } else {
            const designerBlock = designerPct != null
                ? `<p class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                        ${esc(fmtPercent(designerPct))}
                        <span class="text-sm font-normal text-[#64748b] dark:text-[#A1A09A] ml-1">${esc(money(order.designer_offer_amount ?? calcBonus(amount, designerPct)))}</span>
                   </p>`
                : `<p class="mt-1 text-sm text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.noDesignerProposal)}</p>`;

            metrics = `
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.supplierProposed)}</p>
                        <p class="mt-1 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">
                            ${esc(fmtPercent(state === 'pending_designer' ? (supplierPct ?? pendingPct) : supplierPct))}
                            ${(state === 'pending_designer' ? (supplierPct ?? pendingPct) : supplierPct) != null
                                ? `<span class="text-sm font-normal text-[#64748b] dark:text-[#A1A09A] ml-1">${esc(money(order.supplier_offer_amount ?? calcBonus(amount, supplierPct ?? pendingPct)))}</span>`
                                : ''}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.yourProposal)}</p>
                        ${designerBlock}
                    </div>
                    <div>
                        <p class="text-xs text-[#64748b] dark:text-[#A1A09A]">${esc(i18n.amountLabel)}</p>
                        <p class="mt-1 text-sm text-[#0f172a] dark:text-[#EDEDEC]">${esc(money(amount))}</p>
                    </div>
                </div>`;
        }

        const actions = (state === 'pending_designer' && (order.can_accept || order.can_reject || order.can_counter_offer))
            ? `<div class="mt-4 flex flex-col sm:flex-row gap-2">
                    ${order.can_accept ? `<button type="button" id="offer-btn-accept" class="offer-btn offer-btn--primary h-9 sm:h-10 px-4 w-full sm:w-auto">${esc(i18n.acceptPercent.replace(':percent', String(pendingPct ?? supplierPct ?? '')))}</button>` : ''}
                    ${order.can_counter_offer ? `<button type="button" id="offer-btn-counter" class="offer-btn offer-btn--secondary h-9 sm:h-10 px-4 w-full sm:w-auto">${esc(i18n.proposeOther)}</button>` : ''}
                    ${order.can_reject ? `<button type="button" id="offer-btn-reject" class="offer-btn offer-btn--ghost h-9 sm:h-10 px-2 w-full sm:w-auto">${esc(i18n.reject)}</button>` : ''}
               </div>`
            : '';

        root.innerHTML = `
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-medium text-[#0f172a] dark:text-[#EDEDEC]">${esc(i18n.title)}</h2>
                    <p class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5">${esc(i18n.subtitle)}</p>
                </div>
                <div class="sm:pt-0.5">${badgeHtml(state)}</div>
            </div>
            ${metrics}
            ${lastChangeHtml()}
            ${timelineHtml(history, historyExpanded ? undefined : previewCount)}
            ${showHistoryBtn ? `<button type="button" id="offer-btn-history" class="mt-2 text-sm text-[#f59e0b] hover:underline">${esc(i18n.showHistory)}</button>` : ''}
            ${actions}
        `;

        document.getElementById('offer-btn-accept')?.addEventListener('click', openAcceptModal);
        document.getElementById('offer-btn-reject')?.addEventListener('click', openRejectModal);
        document.getElementById('offer-btn-counter')?.addEventListener('click', openCounterModal);
        document.getElementById('offer-btn-history')?.addEventListener('click', openHistoryModal);
    }

    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.setAttribute('aria-hidden', 'false');
    }
    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.setAttribute('aria-hidden', 'true');
    }

    function openAcceptModal() {
        const pct = order.bonus_percent ?? order.supplier_offer_percent;
        const bonus = order.bonus_amount ?? calcBonus(order.order_amount, pct);
        const body = i18n.acceptConfirmBody
            .replace(':percent', String(pct ?? '—'))
            .replace(':bonus', money(bonus))
            .replace(':amount', money(order.order_amount));
        document.getElementById('offer-accept-modal-body').textContent = body;
        openModal('offer-accept-modal');
    }

    function openRejectModal() {
        const pct = order.bonus_percent ?? order.supplier_offer_percent;
        const body = i18n.rejectConfirmBody.replace(':percent', String(pct ?? '—'));
        document.getElementById('offer-reject-modal-body').textContent = body;
        openModal('offer-reject-modal');
    }

    function openCounterModal() {
        const input = document.getElementById('offer-counter-percent');
        if (input) input.value = '';
        updateCounterPreview();
        openModal('offer-counter-modal');
        setTimeout(() => input?.focus(), 50);
    }

    function openHistoryModal() {
        const list = document.getElementById('offer-history-modal-list');
        if (list) list.innerHTML = timelineHtml(proposalHistory()) || `<p class="text-sm text-[#64748b]">—</p>`;
        openModal('offer-history-modal');
    }

    function updateCounterPreview() {
        const raw = (document.getElementById('offer-counter-percent')?.value || '').replace(',', '.').trim();
        const preview = document.getElementById('offer-counter-preview');
        const btn = document.getElementById('offer-counter-submit-btn');
        const current = order.bonus_percent != null ? Number(order.bonus_percent) : null;
        let percent = raw === '' ? NaN : Number(raw);
        let valid = !Number.isNaN(percent) && percent >= 0 && percent <= 100;
        if (valid && current != null && Math.abs(current - percent) < 0.0001) {
            valid = false;
            if (preview) preview.textContent = i18n.percentSame;
        } else if (preview) {
            if (!raw) {
                preview.textContent = '';
            } else if (!valid) {
                preview.textContent = i18n.error;
            } else {
                const amt = calcBonus(order.order_amount, percent);
                preview.textContent = i18n.percentPreview
                    .replace(':percent', String(percent))
                    .replace(':amount', money(amt));
            }
        }
        if (btn) btn.disabled = !valid || busy || raw === '';
    }

    async function offerFetch(path, body) {
        const res = await fetch(`{{ url('supplier-orders') }}/${order.id}/offer/${path}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : '{}',
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    }

    function applyOrder(next) {
        if (!next || typeof next !== 'object') return;
        order = {
            ...order,
            ...next,
            order_amount: next.order_amount ?? next.summa ?? next.amount ?? order.order_amount,
        };
        if (next.status != null) {
            const statusSelect = document.querySelector('#supplier-order-details-form select[name="status"]');
            if (statusSelect) statusSelect.value = next.status;
        }
        const summaInput = document.querySelector('#supplier-order-details-form input[name="summa"]');
        if (summaInput && next.summa != null) summaInput.value = next.summa;
        render();
        refreshUnreadBadge();
    }

    async function refreshUnreadBadge() {
        try {
            const res = await fetch(@json(route('notifications.unread_count')), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json().catch(() => ({}));
            const count = typeof data.count === 'number' ? data.count
                : (typeof data.unread_count === 'number' ? data.unread_count : null);
            if (count == null) return;
            document.querySelectorAll('[data-unread-notifications]').forEach((el) => {
                if (count > 0) {
                    el.textContent = String(count);
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
        } catch (_) {}
    }

    function toast(type, msg) {
        if (typeof projectAlert === 'function') projectAlert(type, msg, '', 2600);
    }

    async function runAction(fn, successMsg) {
        if (busy) return;
        busy = true;
        try {
            const { ok, data } = await fn();
            if (!ok || !data.success) {
                toast('error', data.message || i18n.error);
                return;
            }
            if (data.order) applyOrder(data.order);
            toast('success', successMsg || data.message || '');
            closeModal('offer-accept-modal');
            closeModal('offer-reject-modal');
            closeModal('offer-counter-modal');
        } catch (_) {
            toast('error', i18n.error);
        } finally {
            busy = false;
            updateCounterPreview();
        }
    }

    document.getElementById('offer-accept-confirm-btn')?.addEventListener('click', () => {
        runAction(() => offerFetch('accept'), i18n.toastAccepted);
    });
    document.getElementById('offer-reject-confirm-btn')?.addEventListener('click', () => {
        runAction(() => offerFetch('reject'), i18n.toastRejected);
    });
    document.getElementById('offer-counter-submit-btn')?.addEventListener('click', () => {
        const raw = (document.getElementById('offer-counter-percent')?.value || '').replace(',', '.').trim();
        const percent = Number(raw);
        runAction(() => offerFetch('counter', { bonus_percent: percent }), i18n.toastCounter);
    });
    document.getElementById('offer-counter-percent')?.addEventListener('input', updateCounterPreview);

    document.querySelectorAll('[data-close]').forEach((el) => {
        el.addEventListener('click', () => {
            const key = el.getAttribute('data-close');
            if (key === 'accept') closeModal('offer-accept-modal');
            if (key === 'reject') closeModal('offer-reject-modal');
            if (key === 'counter') closeModal('offer-counter-modal');
            if (key === 'history') closeModal('offer-history-modal');
        });
    });

    function focusBlock() {
        const should = wrap.dataset.focus === '1'
            || window.location.hash === '#offer-negotiation'
            || new URLSearchParams(window.location.search).get('section') === 'offer';
        if (!should || wrap.classList.contains('hidden')) return;
        requestAnimationFrame(() => {
            wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
            wrap.classList.add('is-highlight');
            setTimeout(() => wrap.classList.remove('is-highlight'), 1600);
        });
    }

    render();
    focusBlock();
})();
</script>
@endpush
