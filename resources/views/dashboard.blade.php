@extends('layouts.dashboard')

@section('title', __('dashboard.dashboard'))

@push('styles')
<style>

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e2e8f0;
        border: 1px solid #e2e8f0;
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
        background: linear-gradient(135deg, #f59e0b, #ef4444, #ec4899);
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
        background: linear-gradient(135deg, #f59e0b, #ef4444, #ec4899);
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
        border: 1px solid #e2e8f0;
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
<!-- Карточки метрик -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Клиенты -->
    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.clients') }}</h3>
            <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['clients'] ?? 0 }}</p>
    </div>

    <!-- Поставки в работе -->
    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.orders_in_work') }}</h3>
            <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['orders_in_work'] ?? 0 }}</p>
    </div>

    <!-- Задачи на сегодня -->
    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.tasks_today') }}</h3>
            <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ $stats['tasks_today'] ?? 0 }}</p>
    </div>

    <!-- Накопленные бонусы -->
    <div class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.accumulated_bonuses') }}</h3>
            <svg class="w-5 h-5 text-[#64748b] dark:text-[#A1A09A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-3xl font-bold text-[#0f172a] dark:text-[#EDEDEC]">{{ number_format($stats['accumulated_bonuses'] ?? 0, 0, ',', ' ') }}</p>
    </div>
</div>

<!-- Переключатель вида: Календарь / Список -->
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

<!-- Контейнер календаря -->
<div id="calendar-container" class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6">
    <!-- Панель управления календарем -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <!-- Режимы отображения -->
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

        <!-- Навигация по календарю -->
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

    <!-- Мини календарь для выбора даты -->
    <div class="mb-6">
        <div class="max-w-xs mx-auto">
            <div id="mini-calendar" class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-4">
                <!-- Мини календарь будет вставлен через JavaScript -->
            </div>
        </div>
    </div>

    <!-- Основной календарь -->
    <div id="main-calendar" class="calendar-grid">
        <!-- Календарь будет вставлен через JavaScript -->
    </div>
</div>

<!-- Контейнер списка -->
<div id="list-container" class="bg-white dark:bg-[#161615] border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg p-6 hidden">
    <div id="tasks-list">
        <!-- Список задач будет вставлен через JavaScript -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDate = new Date();
    let currentView = 'month'; // month, week, day
    let currentDisplay = 'calendar'; // calendar, list

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

    // Переключение между календарем и списком
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

    // Переключение режимов календаря
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

    // Навигация по календарю
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
        const startingDayOfWeek = (firstDay.getDay() + 6) % 7; // Понедельник = 0

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

        // Пустые ячейки до первого дня
        for (let i = 0; i < startingDayOfWeek; i++) {
            html += `<div class="aspect-square"></div>`;
        }

        // Дни месяца
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

    function renderCalendar() {
        renderMiniCalendar();
        updateCurrentPeriod();
        
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

        // Заголовки дней
        dayNames.forEach(day => {
            html += `<div class="calendar-day-header">${day}</div>`;
        });

        // Дни календаря
        for (let week = 0; week < weeks; week++) {
            for (let day = 0; day < 7; day++) {
                const dayNumber = week * 7 + day - startingDayOfWeek + 1;
                const date = new Date(year, month, dayNumber);
                const isToday = date.toDateString() === new Date().toDateString();
                const isCurrentMonth = date.getMonth() === month;
                const tasks = getTasksForDate(date);
                
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
                        <div class="space-y-1">
                            ${renderTasksForDate(date)}
                        </div>
                    </div>
                `;
            }
        }

        container.innerHTML = html;
    }

    function getTasksForDate(date) {
        const tasks = [];
        if (date.getDate() % 3 === 0) {
            tasks.push({ title: '{{ __('dashboard.tasks') }} ' + (date.getDate() % 5 + 1), time: '10:00' });
        }
        if (date.getDate() % 5 === 0) {
            tasks.push({ title: '{{ __('dashboard.tasks') }} ' + (date.getDate() % 3 + 1), time: '14:00' });
        }
        return tasks;
    }

    function renderWeekView(container) {
        container.className = '';
        const weekStart = getWeekStart(currentDate);
        let html = `
            <div class="grid grid-cols-8 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div class="p-3 border-r border-[#e2e8f0] dark:border-[#3E3E3A]"></div>
        `;

        for (let i = 0; i < 7; i++) {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + i);
            const isToday = date.toDateString() === new Date().toDateString();
            html += `
                <div class="p-3 text-center border-r border-[#e2e8f0] dark:border-[#3E3E3A] last:border-r-0">
                    <div class="text-xs mb-1 text-[#64748b] dark:text-[#A1A09A] font-medium">${dayNames[i]}</div>
                    <div class="text-lg font-medium ${isToday ? 'text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC]'}">
                        ${date.getDate()}
                    </div>
                </div>
            `;
        }

        html += `</div>`;

        // Часы дня
        for (let hour = 0; hour < 24; hour++) {
            html += `<div class="grid grid-cols-8 border-b border-[#e2e8f0] dark:border-[#3E3E3A]">`;
            html += `<div class="p-2 text-xs text-[#64748b] dark:text-[#A1A09A] border-r border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a] font-medium">${hour.toString().padStart(2, '0')}:00</div>`;
            for (let i = 0; i < 7; i++) {
                const date = new Date(weekStart);
                date.setDate(date.getDate() + i);
                date.setHours(hour, 0, 0, 0);
                const isToday = date.toDateString() === new Date().toDateString();
                const dayClass = isToday ? 'calendar-day today' : 'calendar-day';
                html += `<div class="min-h-[60px] p-1 border-r border-[#e2e8f0] dark:border-[#3E3E3A] last:border-r-0 ${dayClass}">${renderTasksForDate(date, hour)}</div>`;
            }
            html += `</div>`;
        }

        container.innerHTML = html;
    }

    function renderDayView(container) {
        container.className = '';
        const date = currentDate;
        const isToday = date.toDateString() === new Date().toDateString();
        
        let html = `
            <div class="grid grid-cols-2 border-b border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a]">
                <div class="p-3 border-r border-[#e2e8f0] dark:border-[#3E3E3A]"></div>
                <div class="p-3 text-center">
                    <div class="text-xs mb-1 text-[#64748b] dark:text-[#A1A09A] font-medium">${dayNames[date.getDay() === 0 ? 6 : date.getDay() - 1]}</div>
                    <div class="text-lg font-medium ${isToday ? 'text-[#f59e0b]' : 'text-[#0f172a] dark:text-[#EDEDEC]'}">
                        ${date.getDate()} ${monthNames[date.getMonth()]} ${date.getFullYear()}
                    </div>
                </div>
            </div>
        `;

        for (let hour = 0; hour < 24; hour++) {
            const hourDate = new Date(date);
            hourDate.setHours(hour, 0, 0, 0);
            const hourIsToday = hourDate.toDateString() === new Date().toDateString();
            const dayClass = hourIsToday ? 'calendar-day today' : 'calendar-day';
            html += `
                <div class="grid grid-cols-2 border-b border-[#e2e8f0] dark:border-[#3E3E3A]">
                    <div class="p-2 text-xs text-[#64748b] dark:text-[#A1A09A] border-r border-[#e2e8f0] dark:border-[#3E3E3A] bg-[#f8fafc] dark:bg-[#0a0a0a] font-medium">${hour.toString().padStart(2, '0')}:00</div>
                    <div class="min-h-[80px] p-2 ${dayClass}">${renderTasksForDate(hourDate, hour)}</div>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    function renderTasksForDate(date, hour = null) {
        const tasks = getTasksForDate(date);
        
        if (tasks.length === 0) {
            return '';
        }

        return tasks.map(task => `
            <div class="event">
                ${task.time ? `<span class="event-time">${task.time}</span>` : ''}
                <span>${task.title}</span>
            </div>
        `).join('');
    }

    function renderList() {
        const listContainer = document.getElementById('tasks-list');
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        let html = `
            <div class="mb-4">
                <h2 class="text-xl font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${monthNames[month]} ${year}</h2>
            </div>
        `;

        // Примерный список задач
        for (let day = 1; day <= 31; day++) {
            const date = new Date(year, month, day);
            if (date.getMonth() !== month) break;
            
            if (day % 3 === 0 || day % 5 === 0) {
                html += `
                    <div class="mb-4 p-4 border border-[#e2e8f0] dark:border-[#3E3E3A] rounded-lg">
                        <div class="text-sm font-medium text-[#64748b] dark:text-[#A1A09A] mb-2">
                            ${day} ${monthNames[month]} ${year}
                        </div>
                        <div class="space-y-2">
                            ${day % 3 === 0 ? `<div class="event"><span class="event-time">10:00</span> <span>{{ __('dashboard.tasks') }} ${day}</span></div>` : ''}
                            ${day % 5 === 0 ? `<div class="event"><span class="event-time">14:00</span> <span>{{ __('dashboard.tasks') }} ${day + 1}</span></div>` : ''}
                        </div>
                    </div>
                `;
            }
        }

        if (html === `<div class="mb-4"><h2 class="text-xl font-medium text-[#0f172a] dark:text-[#EDEDEC] mb-2">${monthNames[month]} ${year}</h2></div>`) {
            html += `<div class="text-center py-8 text-[#64748b] dark:text-[#A1A09A]">{{ __('dashboard.no_tasks') }}</div>`;
        }

        listContainer.innerHTML = html;
    }

    // Инициализация активных кнопок
    const calendarBtn = document.getElementById('view-calendar');
    const monthBtn = document.querySelector('[data-view="month"]');
    
    if (calendarBtn) {
        calendarBtn.classList.add('active');
    }
    
    if (monthBtn) {
        monthBtn.classList.add('active');
    }

    // Инициализация
    renderCalendar();
});
</script>
@endsection
