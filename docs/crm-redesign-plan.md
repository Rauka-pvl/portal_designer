# CRM Redesign Plan — Кабинет дизайнера

## 1. Текущее состояние / Current state

| Параметр | Значение |
|---|---|
| Laravel | 12.x, PHP 8.2 |
| Frontend | Blade + Tailwind CSS v4 + vanilla JS (Vite) |
| Нет | Livewire, Vue, React, Alpine, SortableJS |
| Тема | `localStorage.theme` + класс `dark` на `<html>` |
| Акцент | `#f59e0b` (amber) |
| Auth | roles: `designer` / `supplier` / `moderator` |
| Tenancy | soft: фильтр `user_id` (владелец = текущий designer) |
| DnD | native HTML5 drag-and-drop |
| Календарь | виджет на dashboard, нет отдельной таблицы событий |

### Ключевые сущности

- **Projects** — статус-строка (`contract_negotiation` … `in_work`) + этапы-чек-листы (`measurement` … `visualization`)
- **PassportObject** — отдельный раздел `/objects`, FK `projects.object_id`
- **Supplier_orders (Поставки)** — отдельный раздел `/supplier-orders`, статусы `order_created` … `delivery_completed`
- **Clients / Suppliers** — отдельные разделы
- **Шаблоны чек-листов** — `templates` + `project_stages` + `project_stages_steps`
- **Activity log** — отсутствует (только comment-поля и chat поставок)

### Статусы проектов (сохранить)

1. `contract_negotiation` — Заключение договора  
2. `contract_signed` — Договор подписан  
3. `prepayment_received` — Предоплата поступила  
4. `tz_signed` — ТЗ Подписано  
5. `documents_signed` — Документы подписаны  
6. `in_work` — Проект взят в работу  

### Статусы поставок (сохранить)

`draft`, `order_created`, `order_confirmed`, `advance_payment`, `full_payment`, `delivery_completed`  
Автоматизация «Доплата»: календарь помечает событие `done` при `full_payment` / `delivery_completed`.

---

## 2. Найденные проблемы / Issues

1. Статусы и цвета захардкожены в контроллерах + Blade/JS.
2. Нет настраиваемых колонок воронки (pipeline stages в БД).
3. Нет CRM activity feed.
4. Паспорт объекта — отдельный UX-модуль, хотя концептуально часть проекта.
5. Поставки — глобальный раздел вместо вкладки проекта.
6. Календарь на dashboard перегружает панель.
7. Нет CSS design tokens — цвета дублируются hex-ами.
8. Sidebar широкий (`w-64`), кнопки крупные (`.add-btn`).
9. N+1 риск на канбане при росте данных (сейчас грузятся все проекты сразу).
10. Пустой stub `ProjectStagesStep.php`.
11. Нет Policies / Enums / Services для CRM-домена.
12. Сохранение проекта удаляет и пересоздаёт stages/steps (риск для `included_step_ids`).

---

## 3. План изменений / Change plan

### Phase A — Foundation
- Design tokens (CSS variables) + CRM UI components
- Enums: `ProjectStatus`, `SupplyStatus`, `ProjectStageType`
- Tables: `pipelines`, `pipeline_stages`, `activity_events`, `project_object_details` (view-compat)
- Account owner permission helper (extensible)
- Artisan verify command

### Phase B — Navigation & chrome
- Compact sidebar CRM
- Menu: Dashboard, Projects, Tasks, Clients, Suppliers (+ existing secondary)
- Remove Objects & Supplies from main menu (routes kept for API/compat redirects)

### Phase C — Dashboard & Tasks
- Analytics cards + charts (real queries, Chart.js)
- Calendar moved to `/tasks`

### Phase D — Projects CRM
- Kanban-first funnel with configurable columns
- Overlay project card (2-column: info + activity feed)
- Tabs: General (object data collapsible), Supplies, Checklists, existing
- Explicit Save / Cancel / unsaved warning

### Phase E — Supplies in project
- Per-project supply kanban
- Preserve offer negotiation, chat, cashback, full_payment calendar logic
- Redirect global `/supplier-orders` → projects context

### Phase F — Clients / Suppliers / rest
- Compact CRM cards, related projects list
- Unify forms/tables/empty states across site

### Phase G — Tests & docs
- Feature tests for funnel, migration integrity, permissions
- Production checklist in final report

---

## 4. Миграция данных / Data migration

1. Create `pipelines` (type: project|supply, owner_user_id) + `pipeline_stages` (system_key, name, color, position, is_system).
2. For each designer user: seed default project pipeline from current status keys.
3. For each project: ensure supply pipeline (or shared account-level supply stage templates + project cards keep `status` string).
4. Backfill `activity_events` from existing projects (created) — optional light seed.
5. `project_object_details`: one-to-one mirror of passport fields for UI; keep `passport_objects` as source of truth initially; dual-write during transition.
6. Verify counts via `php artisan crm:verify-migration`.
7. Cleanup migration later (not in same deploy).

**Запрещено:** migrate:fresh, смена ID, удаление passport_objects в этой итерации.

---

## 5. Риски / Risks

| Риск | Митигация |
|---|---|
| Потеря данных паспорта | Dual-read/write; verify command |
| Ломается API мобильного клиента | Сохранить status string keys; API routes intact |
| DnD ломает business rules offer | Проверять `isInFunnel` как сейчас |
| Большие Blade-файлы | Новые CRM views + постепенный decommission legacy |
| Step ID recreation | Не менять save stages logic в этой фазе без тестов |

---

## 6. Затронутые модули / Affected modules

- Layouts / nav / CSS
- Dashboard, Projects, SupplierOrders, PassportObject (UI hide), Clients, Suppliers
- Models: Project, PassportObject, Supplier_orders + new Pipeline*
- New: Enums, Services, Policies, Activity feed, Tasks controller
- Migrations + Artisan verify
- Tests Feature/Crm*
- Lang files ru/en/kk

---

## 7. Принцип UX

Bitrix24-like: funnel, compact cards, overlay entity card, tabs, activity timeline.  
Not a Bitrix clone: keep amber palette, existing brand, designer domain language («Проекты», not «Сделки»).
