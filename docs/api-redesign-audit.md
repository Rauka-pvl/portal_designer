# API Redesign Audit — portal_designer

Дата: 2026-07-31  
Цель: актуальный designer mobile API без изменения auth и без `/v1`/`/v2`.

---

## 1. Текущий auth (не менять)

| Элемент | Как есть |
|---|---|
| Guard / middleware | `auth:sanctum` |
| Токены | Sanctum `personal_access_tokens`, имя `mobile` |
| Login | `POST /api/login` → `token`, `token_type: Bearer`, `user` |
| Logout | `POST /api/logout` — удаляет current token |
| Register / forgot / reset | без изменений |
| Refresh | отсутствует — не добавлять |
| Profile / me | `GET /api/me`, `PUT/PATCH /api/me/profile`, `POST /api/me/password` |
| Notifications | полный набор под `auth:sanctum` |

Middleware доступа: `subscription.active` (дизайнер), `deposit.paid` (поставщик).

---

## 2. Существующие API endpoints

### Сохранить как есть
- Auth: login, register, forgot/reset, me, logout, profile, password
- Notifications (все)
- Community (все)
- Chat supplier-orders (все)
- Supplier portal: `/api/supplier/orders*` (кабинет поставщика — не расширять)

### Устарели / не соответствуют web CRM
| Endpoint | Проблема |
|---|---|
| `GET /api/clients`, `GET /api/clients/{id}` | Старый payload, нет WorkspaceAccess/команды, нет пагинации meta |
| `POST/PATCH/DELETE /api/clients*` | Forward в web, формат `{success}` |
| `GET/POST /api/objects*` | Паспорт объекта больше не обязателен для проектов |
| `GET/POST /api/projects*` | Нет pipeline stage API, activity, comments; payload legacy |
| `GET/POST /api/supplier-orders*` | Нет nested project supplies, checklist results, единый Resource |
| `GET/POST /api/suppliers*` | Нет favorites endpoints, catalog pagination standards |
| `GET/POST /api/templates*` | Должны быть checklist-templates |
| Offer endpoints | Оставить совместимость, обернуть в `/api/supplies/{id}/percentage-proposals*` |

### Отсутствуют полностью
- Dashboard analytics
- Tasks CRUD / kanban / calendar
- Checklists + items + results + templates (как отдельный модуль)
- Project stages pipeline management
- Subscription plans/checkout/change/cancel
- Team members / invitations / assignees
- `/api/supplies` (алиас актуальной логики поставок)
- Единые API Resources / Policies / OpenAPI

---

## 3. Что переиспользовать

| Компонент | Путь |
|---|---|
| Dashboard metrics | `App\Services\Crm\DashboardAnalyticsService` |
| Pipeline | `App\Services\Crm\PipelineService` |
| Activity | `App\Services\Crm\ActivityFeedService` |
| Team | `App\Services\Team\TeamService` |
| Assignments notify | `App\Services\Team\AssignmentNotifier` |
| Access | `App\Support\WorkspaceAccess` |
| Permissions | `App\Support\AccountPermissions` |
| Subscriptions | `App\Support\DesignerSubscription` |
| Files | `App\Support\PublicFileStorage` |
| Offers notify | `App\Support\OrderOfferNotifier` |
| Project save request | `ProjectSaveRequest` |
| Task save request | `DesignerTaskSaveRequest` |
| Offer rules | `Supplier_orders` model methods |

---

## 4. Что вынести из web-контроллеров

1. `ProjectController` → `ProjectService` (`fillAndSave`, `saveObjectDetails`, `saveStages`, payload)
2. `SupplierOrderController` → `SupplyService` (`fillAndSave`, offers, products)
3. `ClientController` → `ClientService` (save, files, status)
4. `SupplierController` → `SupplierService` (visibility, favorites, CRUD)
5. `DashboardCalendarController` + task checklist cards → `TaskCalendarService` / methods in `TaskService`
6. Checklist step results → `ChecklistService`

Web-контроллеры после выноса вызывают те же Services (поведение сайта не менять).

---

## 5. Конфликты маршрутов

| Старый | Новый | Решение |
|---|---|---|
| `/api/clients` | тот же URL, новый Resource | Заменить handler; breaking change формата |
| `/api/projects` | тот же URL | Заменить handler |
| `/api/suppliers` | тот же URL | Заменить handler |
| `/api/supplier-orders` | `/api/supplies` + aliases | Новый модуль + сохранить offer aliases |
| `/api/templates` | `/api/checklist-templates` | Новый; старый deprecated redirect/alias |
| `/api/objects` | — | Deprecated read/write, не обязателен для проектов |

---

## 6. Риски совместимости мобильного приложения

1. Формат списка: было `{clients:[]}` / `{success:true}` → станет `{data, meta, links}`.
2. Статусы: только system keys, без `status_label`.
3. Деньги: string decimal `"29990.00"`, не float.
4. `object_id` больше не обязателен.
5. Tasks/checklists — новые модули; deep links календаря должны открывать checklist, не legacy step page.
6. Corporate team scopes: часть web ещё на `user_id` — API исправляет через WorkspaceAccess где уже есть, иначе зеркалит текущие правила и постепенно выравнивает.

---

## 7. План реализации

1. Инфраструктура: Api helpers, exception JSON, pagination, money, Scramble OpenAPI. ✅
2. Extract Services + thin web wrappers. ✅
3. Resources + Form Requests + WorkspaceAccess checks. ✅
4. Controllers + routes (без версии). ✅
5. Feature tests (`MobileApiRedesignTest`, обновлён `DesignerApiCrudTest`). ✅
6. `docs/mobile-api.md` + Swagger UI (`/docs/api`, aliases `/api/documentation*`). ✅
7. Финальный отчёт — в ответе ассистента.

---

## 8. Вне scope этой задачи

- Кабинет поставщика (`/api/supplier/*` оставить, не расширять)
- Перепись notifications / profile / settings / auth
- migrate:fresh / truncate / смена token schema

---

## 9. Статус после реализации (2026-07-31)

Реализовано. Auth/Sanctum не трогали. Business endpoints на `/api/*` без versioning.  
Команды: `composer require dedoc/scramble`, `php artisan migrate` (client_id на supplier_orders), `php artisan scramble:export`.
