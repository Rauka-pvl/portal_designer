@extends('layouts.supplier')

@section('title', __('supplier-portal.nav_dashboard'))

@section('header_title', __('supplier-portal.nav_dashboard'))

@push('styles')
<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #d1d5db;
        border: 1px solid #7c8799;
        border-radius: 8px;
        overflow: hidden;
    }

    .calendar-day-header {
        background: #f8fafc;
        padding: 0.8rem;
        text-align: center;
        font-weight: 600;
        color: #64748b;
        font-size: 0.85rem;
    }

    .calendar-day {
        background: #ffffff;
        min-height: 120px;
        padding: 0.5rem;
        position: relative;
    }

    .day-number {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }

    .calendar-day.other-month {
        background: #f8fafc;
        opacity: 0.5;
    }

    .calendar-day.today {
        background: #fef3c7;
    }

    .event {
        background: linear-gradient(135deg, #f59e0b, #fb923c);
        color: white;
        padding: 0.3rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .event-time {
        font-weight: 600;
    }

    .day-badge {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: linear-gradient(135deg, #f59e0b, #fb923c);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .filter-btn {
        background: #ffffff;
        border: 1px solid #7c8799;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
        transition: all 0.3s;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .filter-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .filter-btn.active {
        background: #f1f5f9;
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .calendar-day {
        background: #161615;
    }

    .dark .calendar-day-header {
        background: #0a0a0a;
        color: #A1A09A;
    }

    .dark .calendar-day.other-month {
        background: #0a0a0a;
    }

    .dark .calendar-day.today {
        background: #1D0002;
    }

    .dark .day-number {
        color: #EDEDEC;
    }

    .dark .calendar-grid {
        background: #3E3E3A;
        border-color: #3E3E3A;
    }

    .event.done {
        border: 1px solid rgba(255, 255, 255, 0.4);
    }

    .done-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 9999px;
        background: rgba(34, 197, 94, 1);
        flex-shrink: 0;
    }

    .task-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .event .task-icon {
        color: #ffffff !important;
    }

    .dark .filter-btn {
        background: #161615;
        border-color: #3E3E3A;
        color: #A1A09A;
    }

    .dark .filter-btn:hover {
        border-color: #f59e0b;
        color: #f59e0b;
    }

    .dark .filter-btn.active {
        background: #0a0a0a;
        border-color: #f59e0b;
        color: #f59e0b;
    }
</style>
@endpush

@section('content')
    @unless($supplier)
        <div class="rounded-xl border border-dashed border-[#f59e0b]/50 bg-amber-50/50 dark:bg-amber-950/20 px-6 py-8 text-center">
            <p class="text-[#0f172a] dark:text-[#EDEDEC] font-medium">{{ __('supplier-portal.orders_need_company') }}</p>
            <a href="{{ route('supplier.company') }}" class="inline-flex mt-4 px-4 py-2 rounded-lg bg-[#f59e0b] text-white text-sm font-medium hover:bg-[#d97706]">
                {{ __('supplier-portal.nav_company') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.orders_in_work') }}</h3>
                    <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['orders_in_work'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.tasks_today') }}</h3>
                    <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['tasks_today'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.planned_deliveries') }}</h3>
                    <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['planned_deliveries'] ?? 0 }}</p>
            </div>

            <div class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('supplier-portal.overdue_deliveries') }}</h3>
                    <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['overdue_deliveries'] ?? 0 }}</p>
            </div>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <div class="flex gap-2">
                <button id="view-calendar" class="filter-btn active">
                    {{ __('dashboard.calendar') }}
                </button>
                <button id="view-list" class="filter-btn">
                    {{ __('dashboard.list') }}
                </button>
            </div>
        </div>

        <div id="calendar-container" class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
                <div class="flex gap-2">
                    <button data-view="month" class="view-mode-btn filter-btn active">
                        {{ __('dashboard.month') }}
                    </button>
                    <button data-view="week" class="view-mode-btn filter-btn">
                        {{ __('dashboard.week') }}
                    </button>
                    <button data-view="day" class="view-mode-btn filter-btn">
                        {{ __('dashboard.day') }}
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <button id="prev-period" class="filter-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button id="today-btn" class="filter-btn">
                        {{ __('dashboard.today') }}
                    </button>
                    <button id="next-period" class="filter-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div id="current-period" class="px-4 py-2 text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC] min-w-[200px] text-center"></div>
                </div>
            </div>

            <div class="mb-6">
                <div class="max-w-xs mx-auto">
                    <div id="mini-calendar" class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-4">
                    </div>
                </div>
            </div>

            <div id="main-calendar" class="calendar-grid"></div>
        </div>

        <div id="list-container" class="bg-white dark:bg-[#161615] border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg p-6 hidden">
            <div id="tasks-list"></div>
        </div>

        <div id="day-drawer-overlay" class="fixed inset-0 bg-black/40 hidden z-50" onclick="closeDayDrawer()"></div>
        <div id="day-drawer" class="fixed top-0 right-0 h-full w-full max-w-md bg-white dark:bg-[#161615] border-l border-[#7c8799] dark:border-[#3E3E3A] shadow-2xl hidden z-50 flex flex-col">
            <div class="p-4 flex items-center justify-between border-b border-[#7c8799] dark:border-[#3E3E3A] flex-none">
                <div class="text-base font-semibold text-[#0f172a] dark:text-[#EDEDEC]" id="day-drawer-title">—</div>
                <button type="button" class="p-2 rounded-lg hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a] transition-colors" onclick="closeDayDrawer()" aria-label="Закрыть">
                    <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="day-drawer-content" class="p-4 overflow-y-auto space-y-3 flex-1 min-h-0"></div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentDate = new Date();
            let currentView = 'month';
            let currentDisplay = 'calendar';

            const months = {
                kk: ['Қаңтар', 'Ақпан', 'Наурыз', 'Сәуір', 'Мамыр', 'Маусым', 'Шілде', 'Тамыз', 'Қыркүйек', 'Қазан', 'Қараша', 'Желтоқсан'],
                ru: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                en: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
            };

            const weekdays = {
                kk: ['Дс', 'Сс', 'Ср', 'Бс', 'Жм', 'Сб', 'Жс'],
                ru: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
                en: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            };

            const locale = '{{ app()->getLocale() }}';
            const monthNames = months[locale] || months.en;
            const dayNames = weekdays[locale] || weekdays.en;
            const eventsEndpoint = @json(route('supplier.calendar.events'));

            let eventsByDate = {};
            let currentEvents = [];
            let activeRangeToken = 0;

            function toISODate(d) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function formatAmount(amount) {
                const n = Number(amount ?? 0);
                if (!Number.isFinite(n) || n === 0) return '';
                const loc = locale === 'ru' ? 'ru-RU' : (locale === 'kk' ? 'kk-KZ' : 'en-US');
                try {
                    return new Intl.NumberFormat(loc, { maximumFractionDigits: 0 }).format(n);
                } catch (e) {
                    return String(n);
                }
            }

            function eventIconHtml() {
                return `
                    <svg class="task-icon text-[#f59e0b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h13v14H3z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11h5l2 3v7h-7z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                    </svg>
                `;
            }

            function doneCheckHtml(task) {
                if (!task?.done) return '';
                return `
                    <span class="done-check" title="Выполнено">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 6L9 17l-5-5"/>
                        </svg>
                    </span>
                `;
            }

            function getTaskHour(task) {
                if (!task?.time || typeof task.time !== 'string') return null;
                const parts = task.time.split(':');
                const h = parseInt(parts[0] ?? '', 10);
                return Number.isFinite(h) ? h : null;
            }

            function renderTaskMini(task, { showTime = true } = {}) {
                const href = task?.url_show ? escapeHtml(task.url_show) : '#';
                const time = showTime && task?.time ? `<span class="event-time">${escapeHtml(task.time)}</span>` : '';
                const title = escapeHtml(task?.title);
                const subtitle = task?.subtitle ? `<div class="text-[11px] opacity-90 mt-0.5">${escapeHtml(task.subtitle)}</div>` : '';
                const amount = task?.amount ? formatAmount(task.amount) : '';
                const amountHtml = amount ? `<div class="text-[11px] opacity-90 mt-0.5">${amount}</div>` : '';

                const icon = eventIconHtml(task);
                const doneMark = doneCheckHtml(task);
                return `
                    <a href="${href}" class="event flex items-start gap-2 ${task?.done ? 'done' : ''}" style="padding:0.25rem 0.4rem; margin-bottom:0.25rem;">
                        ${icon}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <span class="leading-tight font-medium break-words">${time}${title ? ` ${title}` : ''}</span>
                                ${doneMark}
                            </div>
                            ${subtitle}
                            ${amountHtml}
                        </div>
                    </a>
                `;
            }

            function renderTaskListItem(task) {
                const href = task?.url_show ? escapeHtml(task.url_show) : '#';
                const title = escapeHtml(task?.title);
                const subtitle = task?.subtitle ? escapeHtml(task.subtitle) : '';
                const doneMark = doneCheckHtml(task);
                const amount = task?.amount ? formatAmount(task.amount) : '';
                const amountHtml = amount ? `<div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-1">${amount}</div>` : '';
                return `
                    <div class="p-3 border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5">${eventIconHtml(task)}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="${href}" class="font-medium text-[#0f172a] dark:text-[#EDEDEC] hover:underline break-words">${title}</a>
                                    ${doneMark}
                                </div>
                                ${subtitle ? `<div class="text-xs text-[#64748b] dark:text-[#A1A09A] mt-0.5 break-words">${subtitle}</div>` : ''}
                                ${amountHtml}
                                ${task?.time ? `<div class="text-[11px] text-[#64748b] dark:text-[#A1A09A] mt-1">${escapeHtml(task.time)}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            async function loadEventsForRange(startISO, endISO) {
                try {
                    const token = ++activeRangeToken;
                    const res = await fetch(`${eventsEndpoint}?start=${encodeURIComponent(startISO)}&end=${encodeURIComponent(endISO)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Events fetch failed');
                    const data = await res.json();
                    if (token !== activeRangeToken) return;

                    currentEvents = Array.isArray(data?.events) ? data.events : [];
                    eventsByDate = {};
                    currentEvents.forEach(ev => {
                        const key = ev?.date ? String(ev.date) : null;
                        if (!key) return;
                        if (!eventsByDate[key]) eventsByDate[key] = [];
                        eventsByDate[key].push(ev);
                    });

                    Object.keys(eventsByDate).forEach(k => {
                        eventsByDate[k].sort((a, b) => {
                            const da = a?.done ? 1 : 0;
                            const db = b?.done ? 1 : 0;
                            if (da !== db) return da - db;
                            const ta = getTaskHour(a);
                            const tb = getTaskHour(b);
                            if (ta == null && tb == null) return 0;
                            if (ta == null) return 1;
                            if (tb == null) return -1;
                            return ta - tb;
                        });
                    });
                } catch (e) {
                    eventsByDate = {};
                    currentEvents = [];
                }
            }

            function calcCurrentRangeISO() {
                if (currentView === 'day') {
                    const key = toISODate(currentDate);
                    return { startISO: key, endISO: key };
                }

                if (currentView === 'week') {
                    const weekStart = getWeekStart(currentDate);
                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekEnd.getDate() + 6);
                    return { startISO: toISODate(weekStart), endISO: toISODate(weekEnd) };
                }

                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const firstDay = getFirstDayOfMonth(currentDate);
                const lastDay = getLastDayOfMonth(currentDate);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = (firstDay.getDay() + 6) % 7;
                const weeks = Math.ceil((startingDayOfWeek + daysInMonth) / 7);

                const gridStart = new Date(year, month, 1 - startingDayOfWeek);
                const gridEnd = new Date(year, month, 1 - startingDayOfWeek + (weeks * 7) - 1);
                return { startISO: toISODate(gridStart), endISO: toISODate(gridEnd) };
            }

            function openDayDrawer(dateISO) {
                const overlay = document.getElementById('day-drawer-overlay');
                const drawer = document.getElementById('day-drawer');
                const titleEl = document.getElementById('day-drawer-title');
                const contentEl = document.getElementById('day-drawer-content');
                if (!drawer || !overlay || !contentEl) return;

                const dt = new Date(`${dateISO}T00:00:00`);
                const isValid = !Number.isNaN(dt.getTime());
                titleEl.textContent = isValid ? `${dt.getDate()} ${monthNames[dt.getMonth()]} ${dt.getFullYear()}` : dateISO;

                const tasks = eventsByDate?.[dateISO] || [];
                if (!tasks.length) {
                    contentEl.innerHTML = `<div class="text-center text-[#64748b] dark:text-[#A1A09A] py-8">{{ __('dashboard.no_tasks') }}</div>`;
                } else {
                    contentEl.innerHTML = tasks.map(renderTaskListItem).join('');
                }

                overlay.classList.remove('hidden');
                drawer.classList.remove('hidden');
            }

            window.openDayDrawer = openDayDrawer;

            function closeDayDrawer() {
                const overlay = document.getElementById('day-drawer-overlay');
                const drawer = document.getElementById('day-drawer');
                if (overlay) overlay.classList.add('hidden');
                if (drawer) drawer.classList.add('hidden');
            }

            window.closeDayDrawer = closeDayDrawer;

            document.getElementById('view-calendar').addEventListener('click', function() {
                currentDisplay = 'calendar';
                document.getElementById('calendar-container').classList.remove('hidden');
                document.getElementById('list-container').classList.add('hidden');
                this.classList.add('active');
                document.getElementById('view-list').classList.remove('active');
                renderCalendar();
            });

            document.getElementById('view-list').addEventListener('click', function() {
                currentDisplay = 'list';
                document.getElementById('calendar-container').classList.add('hidden');
                document.getElementById('list-container').classList.remove('hidden');
                this.classList.add('active');
                document.getElementById('view-calendar').classList.remove('active');
                renderList();
            });

            document.querySelectorAll('.view-mode-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentView = this.dataset.view;
                    document.querySelectorAll('.view-mode-btn').forEach(b => {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    renderCalendar();
                });
            });

            document.getElementById('prev-period').addEventListener('click', function() {
                if (currentView === 'month') {
                    currentDate.setMonth(currentDate.getMonth() - 1);
                } else if (currentView === 'week') {
                    currentDate.setDate(currentDate.getDate() - 7);
                } else {
                    currentDate.setDate(currentDate.getDate() - 1);
                }
                renderCalendar();
            });

            document.getElementById('next-period').addEventListener('click', function() {
                if (currentView === 'month') {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                } else if (currentView === 'week') {
                    currentDate.setDate(currentDate.getDate() + 7);
                } else {
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                renderCalendar();
            });

            document.getElementById('today-btn').addEventListener('click', function() {
                currentDate = new Date();
                renderCalendar();
            });

            function getFirstDayOfMonth(date) {
                return new Date(date.getFullYear(), date.getMonth(), 1);
            }

            function getLastDayOfMonth(date) {
                return new Date(date.getFullYear(), date.getMonth() + 1, 0);
            }

            function getWeekStart(date) {
                const d = new Date(date);
                const day = d.getDay();
                const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                return new Date(d.setDate(diff));
            }

            function renderMiniCalendar() {
                const miniCal = document.getElementById('mini-calendar');
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const firstDay = getFirstDayOfMonth(currentDate);
                const lastDay = getLastDayOfMonth(currentDate);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = (firstDay.getDay() + 6) % 7;

                let html = `
                    <div class="text-center mb-4">
                        <div class="text-lg font-medium text-[#0f172a] dark:text-[#EDEDEC]">${monthNames[month]} ${year}</div>
                    </div>
                    <div class="grid grid-cols-7 gap-1 mb-2">
                `;

                dayNames.forEach(day => {
                    html += `<div class="text-center text-xs font-medium text-[#64748b] dark:text-[#A1A09A] py-1">${day}</div>`;
                });

                html += `</div><div class="grid grid-cols-7 gap-1">`;

                for (let i = 0; i < startingDayOfWeek; i++) {
                    html += `<div class="aspect-square"></div>`;
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const isToday = date.toDateString() === new Date().toDateString();
                    const isSelected = date.toDateString() === currentDate.toDateString();
                    html += `
                        <button onclick="selectDate(${year}, ${month}, ${day})"
                            class="aspect-square rounded text-sm transition-colors ${
                                isToday ? 'bg-gradient-to-br from-[#fef3c7] to-white dark:from-[#1D0002] dark:to-[#161615] text-[#f59e0b] dark:text-[#f59e0b] font-medium' :
                                isSelected ? 'bg-[#f1f5f9] dark:bg-[#0a0a0a] border-2 border-[#f59e0b] dark:border-[#f59e0b] text-[#f59e0b] dark:text-[#f59e0b]' :
                                'text-[#0f172a] dark:text-[#EDEDEC] hover:bg-[#f1f5f9] dark:hover:bg-[#0a0a0a]'
                            }">
                            ${day}
                        </button>
                    `;
                }

                html += `</div>`;
                miniCal.innerHTML = html;
            }

            function selectDate(year, month, day) {
                currentDate = new Date(year, month, day);
                renderCalendar();
            }

            window.selectDate = selectDate;

            async function renderCalendar() {
                renderMiniCalendar();
                updateCurrentPeriod();

                const { startISO, endISO } = calcCurrentRangeISO();
                await loadEventsForRange(startISO, endISO);

                const mainCal = document.getElementById('main-calendar');

                if (currentView === 'month') {
                    renderMonthView(mainCal);
                } else if (currentView === 'week') {
                    renderWeekView(mainCal);
                } else {
                    renderDayView(mainCal);
                }
            }

            function updateCurrentPeriod() {
                const periodEl = document.getElementById('current-period');
                if (currentView === 'month') {
                    periodEl.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
                } else if (currentView === 'week') {
                    const weekStart = getWeekStart(currentDate);
                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekEnd.getDate() + 6);
                    periodEl.textContent = `${weekStart.getDate()} ${monthNames[weekStart.getMonth()]} - ${weekEnd.getDate()} ${monthNames[weekEnd.getMonth()]} ${weekEnd.getFullYear()}`;
                } else {
                    periodEl.textContent = `${currentDate.getDate()} ${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
                }
            }

            function renderMonthView(container) {
                container.className = 'calendar-grid';
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const firstDay = getFirstDayOfMonth(currentDate);
                const lastDay = getLastDayOfMonth(currentDate);
                const daysInMonth = lastDay.getDate();
                const startingDayOfWeek = (firstDay.getDay() + 6) % 7;
                const weeks = Math.ceil((startingDayOfWeek + daysInMonth) / 7);

                let html = '';

                dayNames.forEach(day => {
                    html += `<div class="calendar-day-header">${day}</div>`;
                });

                for (let week = 0; week < weeks; week++) {
                    for (let day = 0; day < 7; day++) {
                        const dayNumber = week * 7 + day - startingDayOfWeek + 1;
                        const date = new Date(year, month, dayNumber);
                        const isToday = date.toDateString() === new Date().toDateString();
                        const isCurrentMonth = date.getMonth() === month;
                        const key = toISODate(date);
                        const tasks = eventsByDate[key] || [];
                        const tasksFirst = tasks.slice(0, 2);
                        const restCount = Math.max(0, tasks.length - 2);
                        const tasksMiniHtml = tasksFirst.map(t => renderTaskMini(t, { showTime: false })).join('') + (
                            restCount > 0
                                ? `<button type="button" onclick="openDayDrawer('${key}')" class="text-[12px] text-[#f59e0b] hover:underline mt-0.5">+${restCount}</button>`
                                : ''
                        );

                        let dayClass = 'calendar-day';
                        if (!isCurrentMonth) {
                            dayClass += ' other-month';
                        }
                        if (isToday) {
                            dayClass += ' today';
                        }

                        html += `
                            <div class="${dayClass}">
                                <div class="day-number">${dayNumber > 0 && dayNumber <= daysInMonth ? dayNumber : ''}</div>
                                ${tasks.length > 0 ? `<div class="day-badge">${tasks.length}</div>` : ''}
                                <div class="space-y-1">${tasksMiniHtml}</div>
                            </div>
                        `;
                    }
                }

                container.innerHTML = html;
            }

            function renderWeekView(container) {
                container.className = '';
                const weekStart = getWeekStart(currentDate);
                let html = `
                    <div class="grid grid-cols-8 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <div class="p-3 border-r border-[#7c8799] dark:border-[#3E3E3A]"></div>
                `;

                for (let i = 0; i < 7; i++) {
                    const date = new Date(weekStart);
                    date.setDate(date.getDate() + i);
                    const isToday = date.toDateString() === new Date().toDateString();
                    html += `
                        <div class="p-3 text-center border-r border-[#7c8799] dark:border-[#3E3E3A] last:border-r-0">
                            <div class="text-xs mb-1 text-[#64748b] dark:text-[#A1A09A] font-medium">${dayNames[i]}</div>
                            <div class="text-lg font-medium ${isToday ? 'text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC]'}">
                                ${date.getDate()}
                            </div>
                        </div>
                    `;
                }

                html += `</div>`;

                for (let hour = 0; hour < 24; hour++) {
                    html += `<div class="grid grid-cols-8 border-b border-[#7c8799] dark:border-[#3E3E3A]">`;
                    html += `<div class="p-2 text-xs text-[#64748b] dark:text-[#A1A09A] border-r border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a] font-medium">${hour.toString().padStart(2, '0')}:00</div>`;
                    for (let i = 0; i < 7; i++) {
                        const date = new Date(weekStart);
                        date.setDate(date.getDate() + i);
                        date.setHours(hour, 0, 0, 0);
                        const isToday = date.toDateString() === new Date().toDateString();
                        const dayClass = isToday ? 'calendar-day today' : 'calendar-day';
                        html += `<div class="min-h-[60px] p-1 border-r border-[#7c8799] dark:border-[#3E3E3A] last:border-r-0 ${dayClass}">${renderTasksForDate(date, hour)}</div>`;
                    }
                    html += `</div>`;
                }

                container.innerHTML = html;
            }

            function renderDayView(container) {
                container.className = '';
                const date = currentDate;
                const isToday = date.toDateString() === new Date().toDateString();

                const key = toISODate(date);
                const tasks = eventsByDate?.[key] || [];
                const tasksFirst = tasks.slice(0, 2);
                const restCount = Math.max(0, tasks.length - 2);

                let html = `
                    <div class="grid grid-cols-2 border-b border-[#7c8799] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                        <div class="p-3 border-r border-[#7c8799] dark:border-[#3E3E3A]"></div>
                        <div class="p-3 text-center">
                            <div class="text-xs mb-1 text-[#64748b] dark:text-[#A1A09A] font-medium">${dayNames[date.getDay() === 0 ? 6 : date.getDay() - 1]}</div>
                            <div class="text-lg font-medium ${isToday ? 'text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC]'}">
                                ${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        ${
                            !tasks.length
                                ? `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.no_tasks') }}</div>`
                                : `
                                    <div class="space-y-1">
                                        ${tasksFirst.map(t => renderTaskMini(t, { showTime: true })).join('')}
                                        ${
                                            restCount > 0
                                                ? `<button type="button" onclick="openDayDrawer('${key}')" class="text-[12px] text-[#f59e0b] hover:underline mt-1">+${restCount}</button>`
                                                : ''
                                        }
                                    </div>
                                `
                        }
                    </div>
                `;

                container.innerHTML = html;
            }

            function renderTasksForDate(date, hour = null) {
                const key = toISODate(date);
                let tasks = eventsByDate?.[key] || [];

                if (hour !== null) {
                    tasks = tasks.filter(t => getTaskHour(t) === hour);
                }

                if (!tasks.length) return '';

                const tasksFirst = tasks.slice(0, 2);
                const restCount = Math.max(0, tasks.length - 2);
                return tasksFirst.map(t => renderTaskMini(t, { showTime: false })).join('') + (
                    restCount > 0
                        ? `<button type="button" onclick="openDayDrawer('${key}')" class="text-[12px] text-[#f59e0b] hover:underline mt-0.5">+${restCount}</button>`
                        : ''
                );
            }

            function renderList() {
                const listContainer = document.getElementById('tasks-list');
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const daysInMonth = new Date(year, month + 1, 0).getDate();

                let html = `
                    <div class="mb-4">
                        <h2 class="text-xl font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${monthNames[month]} ${year}</h2>
                    </div>
                `;

                let hasAny = false;

                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month, day);
                    const key = toISODate(date);
                    const tasks = eventsByDate?.[key] || [];
                    if (!tasks.length) continue;

                    hasAny = true;

                    const tasksFirst = tasks.slice(0, 2);
                    const restCount = Math.max(0, tasks.length - 2);

                    const tasksHtml = tasksFirst.map(t => renderTaskMini(t, { showTime: true })).join('') + (
                        restCount > 0
                            ? `<button type="button" onclick="openDayDrawer('${key}')" class="text-[12px] text-[#f59e0b] hover:underline mt-1">+${restCount}</button>`
                            : ''
                    );

                    html += `
                        <div class="mb-4 p-4 border border-[#7c8799] dark:border-[#3E3E3A] rounded-lg">
                            <div class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">
                                ${day} ${monthNames[month]} ${year}
                            </div>
                            <div class="space-y-1">
                                ${tasksHtml}
                            </div>
                        </div>
                    `;
                }

                if (!hasAny) {
                    html += `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.no_tasks') }}</div>`;
                }

                listContainer.innerHTML = html;
            }

            document.getElementById('view-calendar').classList.add('active');
            document.querySelector('[data-view="month"]').classList.add('active');
            renderCalendar();
        });
        </script>
    @endunless
@endsection
