# Аудит производительности

**Дата:** 2026-08-03

---

## Critical — N+1 и отсутствие пагинации

| Файл | Метод | Строки | Проблема | Исправление | До | После |
|------|-------|--------|----------|-------------|-----|-------|
| `ProjectController.php` | `index` | 52-65 | `->get()` без пагинации, `stages.steps`, `supplierOrders.supplier` | `->paginate(20)`, `withCount` | N+1, OOM | 1-2 запроса |
| `SupplierOrderController.php` | `index` | 43-47 | `->get()` без пагинации | `->paginate(20)` | N+1 | 1-2 запроса |
| `ProjectController.php` | `projectPayload` | 686-695 | `hasDelayedSupply` — PHP-фильтрация | SQL `whereExists` | N запросов | 1 запрос |

## High — Неэффективные запросы

| Файл | Метод | Строки | Проблема | Исправление |
|------|-------|--------|----------|-------------|
| `ProjectController.php` | `index` | 52-65 | `select *` для всех связей | `->select([...])` |
| `ProjectController.php` | `index` | 67-76 | 2 запроса для clients | Объединить с `WorkspaceAccess` |
| `TeamService.php` | `activeTeamFor` | 19-32 | 2 запроса при каждом вызове | Кешировать на запрос |
| `ProjectController.php` | `chatUnreadMapForDesigner` | 888-913 | Вызывается для каждого проекта | Вызывать один раз для всех orderIds |

## Medium — Frontend

| Файл | Проблема | Исправление |
|------|----------|-------------|
| `resources/views/designer/projects/crm.blade.php` | 1088 строк | Разбить на компоненты |
| `resources/views/designer/projects/partials/supply-scripts.blade.php` | Большой inline JS | Вынести в `resources/js` |
| `resources/views/designer/projects/partials/checklist-scripts.blade.php` | Аналогично | Вынести в `resources/js` |

## Индексы — рекомендации

| Таблица | Индекс | Причина |
|---------|--------|---------|
| `projects` | `(user_id, team_id)` | Фильтрация по владельцу и команде |
| `projects` | `(client_id)` | FK |
| `projects` | `(status)` | Фильтрация |
| `supplier_orders` | `(user_id, status)` | Фильтрация поставок |
| `supplier_orders` | `(project_id)` | FK |
| `supplier_orders` | `(supplier_id)` | FK |
| `designer_team_members` | `(team_id, status)` | Активные участники |
| `designer_team_members` | `(user_id, status)` | Команды пользователя |
| `community_posts` | `(user_id, status)` | Посты пользователя |
| `community_posts` | `(status, created_at)` | Лента |
| `supplier_order_messages` | `(supplier_order_id, read_by_designer_at)` | Непрочитанные |

## Измерения (baseline)

| Метрика | Значение |
|---------|----------|
| Тесты | 135 passed (665 assertions) |
| Маршруты | 349 |
| Миграции | 66 |
| Модели | 35 |
| Контроллеры | 53 |
| Frontend build | ✓ 4.56s |

*Количество SQL-запросов на страницах не измерялось (требуется Telescope/Debugbar)*
