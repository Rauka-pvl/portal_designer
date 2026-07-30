# Отчёт: исправление UI страницы «Проекты»

## Что изменено

### Blade
- `resources/views/designer/projects/crm.blade.php` — компактный toolbar, единый workspace, режимы канбан/список через `is-hidden`, информативные карточки, таблица с сортировкой, debounce поиска, сохранение режима.
- `resources/views/layouts/dashboard.blade.php` — `main_class` для full-height, корректные подписи переключателя темы.

### JavaScript (inline в `crm.blade.php`)
- Настоящее переключение режимов: `setView()` + `is-hidden` (не Tailwind `hidden`, который перекрывался `display:flex`).
- Сохранение режима: `localStorage` (`crm.projects.view`) + query `?view=`.
- Поиск с debounce 350ms, фильтр «Просроченные», клиентская сортировка списка.
- Карточки: клиент, ответственный, срок (locale), бюджет с ₸, прогресс чек-листов, индикаторы.
- DnD: drop zone на всю колонку, rollback при ошибке, обновление счётчиков через re-render.

### CSS
- `resources/css/app.css` — `.crm-main-fill`, `.crm-workspace`, `.crm-board*`, `.crm-list-panel`, меньше рамок, стабильная ширина колонок 280–300px, адаптив.

### Backend
- `ProjectController` — eager load `user`, поля `owner_name`, `responsible_name`, `has_delayed_supply` в payload.
- Бизнес-логика статусов/прав не менялась.

### Локализация
- `lang/{ru,en,kk}/projects.php` — meta-labels, empty state, currency.
- `lang/{ru,en,kk}/dashboard.php` — dark/light theme labels.

### Тесты
- `tests/Feature/CrmRedesignTest.php` — markup режимов, payload owner/progress, theme labels.

## Как работает переключение режимов
1. Кнопки `aria-pressed` + класс `active`.
2. Активная панель без `is-hidden`; неактивная — `display: none !important`.
3. В неактивном режиме DOM очищается (нет двойного рендера).
4. Режим восстанавливается из query → localStorage → default `kanban`.

## Высота канбана
```
.crm-main-fill { height: calc(100dvh - 4.75rem); overflow: hidden; flex column }
.crm-viewport / #crm-kanban.crm-board { flex: 1; min-height: 0; height: 100% }
.crm-board-col { height: 100%; body overflow-y: auto }
```
Горизонтальный scrollbar только у `#crm-kanban`.

## Оптимизация запросов
- Добавлен `user:id,name` в eager loading index/show.
- В режиме списка канбан-колонки не рендерятся и наоборот.

## Команды
```bash
npm run build
php artisan test --filter=CrmRedesignTest
```
Обновите страницу с hard refresh (`Ctrl+F5`).
