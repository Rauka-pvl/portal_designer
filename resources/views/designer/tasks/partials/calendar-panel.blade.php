{{-- Simplified month calendar for the Tasks CRM page. Fetches route('tasks.events') (same data source as
     dashboard.events): checklist steps, supplier order dates and designer tasks. Designer task events open the
     task modal; checklist step events open the existing checklist detail via window.CrmChecklists.openByIds. --}}
<div class="tasks-calendar-head">
    <div class="tasks-calendar-nav">
        <button type="button" id="tasks-cal-prev" class="crm-btn crm-btn-ghost crm-btn-sm" aria-label="{{ __('dashboard.previous') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" id="tasks-cal-today" class="crm-btn crm-btn-secondary crm-btn-sm">{{ __('dashboard.today') }}</button>
        <button type="button" id="tasks-cal-next" class="crm-btn crm-btn-ghost crm-btn-sm" aria-label="{{ __('dashboard.next') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg>
        </button>
        <div id="tasks-cal-title" class="tasks-calendar-title"></div>
    </div>
</div>
<div id="tasks-calendar-grid" class="tasks-calendar-grid" role="grid" aria-label="{{ __('tasks.view_calendar') }}"></div>

<div id="tasks-day-drawer-overlay" class="tasks-day-drawer-overlay hidden"></div>
<div id="tasks-day-drawer" class="tasks-day-drawer hidden" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="tasks-day-drawer-head">
        <div id="tasks-day-drawer-title" class="font-semibold text-sm">—</div>
        <button type="button" id="tasks-day-drawer-close" class="crm-btn crm-btn-ghost crm-btn-sm" aria-label="{{ __('tasks.close') }}">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="tasks-day-drawer-body" class="tasks-day-drawer-body"></div>
</div>
