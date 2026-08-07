# Mobile API — Design Portal

## Overview

Новый API мобильного кабинета дизайнера. Базовый URL: **`/api`**.

| Правило | Значение |
|---|---|
| Аутентификация | `Authorization: Bearer {Sanctum token}` |
| Основной доступ | Все business-маршруты требуют `auth:sanctum`, роль `designer` и активную подписку (`subscription.active`). |
| Исключения | Подписка доступна любому авторизованному пользователю; команда требует Corporate-подписку, а операции управления — права владельца/администратора. |
| Content-Type | `application/json`, кроме загрузки файлов/логотипа — `multipart/form-data`. |
| Конверт | Одиночный ресурс: `{ "data": {...} }`; коллекция с пагинацией: `{ "data": [...], "links": {...}, "meta": {...} }`. |
| Деньги | Строки с двумя знаками: `"29990.00"`, валюта `KZT`. Не преобразовывайте их в `float` (avoid floating-point rounding). |
| Статусы | Только системные ключи, например `new`, `in_progress`, `draft`; UI-локализацию делает клиент. |
| Даты | Дата-время — ISO 8601, например `2026-07-31T10:00:00+05:00`; поля поставок и дедлайнов, где явно возвращается дата, могут быть `YYYY-MM-DD`. |
| Ошибки | Laravel validation: HTTP `422`, `{ "message": "...", "errors": { "field": ["..."] } }`; доступ — `401/403`, отсутствующий ресурс — `404`. |

Документация Swagger UI: [`/docs/api`](/docs/api) и [`/api/documentation`](/api/documentation).  
OpenAPI JSON: [`/docs/api.json`](/docs/api.json), [`/api/documentation.json`](/api/documentation.json), а также файл `public/api-documentation.json`.

Во всех следующих URL показан путь после `/api`. Параметры `{id}`, `{client}`, `{projectId}`, `{supply}`, `{item}` и т. п. — целые числа.

## Auth (unchanged)

Формат авторизации **не изменён**. Эти маршруты не являются частью нового CRM-контракта, но мобильный клиент продолжает использовать их:

| Поле | Значение |
|---|---|
| Метод | `POST` |
| URL | `/login` |
| Авторизация | Не требуется |
| Роли | — |
| Content-Type | `application/json` |
| Назначение | Вход. Body: `email`, `password`, `portal` (`designer` или `supplier`). Ответ содержит `success`, `token`, `token_type: "Bearer"`, `user`. |

`POST /register` сохраняет прежний контракт регистрации. `POST /logout` отзывает текущий токен, а `GET /me` возвращает текущего пользователя; оба требуют Bearer token. Профиль: `PUT|PATCH /me/profile`; пароль: `POST /me/password`. Входной/выходной формат этих endpoint'ов не менялся.

## Общие модели ответа

### Pagination / пагинация

Списки клиентов, проектов и задач принимают `per_page` (по умолчанию 20) и возвращают стандартные Laravel `links`/`meta`. В `meta` коллекций проектов и задач дополнительно есть `count` — количество элементов на текущей странице.

### Project

| Поле | Тип / значение |
|---|---|
| `id`, `name`, `status`, `stage_id` | Идентификатор, название, системный статус, ID pipeline stage. |
| `client`, `responsible` | Объекты `{id, name}` либо `null`. |
| `stage` | `ProjectStage` либо `null`. |
| `start_date`, `planned_completion_date`, `actual_completion_date`, `planned_end_date`, `actual_end_date` | ISO 8601 или `null`. |
| `planned_cost`, `actual_cost`, `renovation_budget_plan`, `renovation_budget_fact` | Денежные строки или `null`. |
| `property` | Снимок объекта: `client_name`, `city`, `object_type`, `object_address`, `apartment_floor`, `apartment_entrance`, `apartment`, `area`, `repair_budget_planned`, `repair_budget_actual`; числовые значения сериализуются строками. |
| `checklist_progress` | `{total, done, percent}`. |
| `counts` | `{checklist_stages, tasks}`. |
| `comment`, `links`, `created_at`, `updated_at` | Комментарий, ссылки, ISO 8601 timestamps. |

### Other reusable resources

| Ресурс | Поля |
|---|---|
| `ProjectStage` | `id`, `system_key`, `name`, `color`, `position`, `is_system`, `is_active`. |
| `ActivityEvent` | `id`, `event_type`, `body`, `meta`, `actor: {id,name}\|null`, `created_at`. |
| `Checklist` | `id`, `project_id`, `stage_type`, `name`, `template_id`, `deadline`, `responsible_id`, `assign_task`, `order`, `items`, `created_at`, `updated_at`. |
| `ChecklistItem` | `id`, `checklist_id`, `title`, `position`, `order`, `deadline`, `responsible_id`, `link`, `completed`, `completed_at`, `result`, `result_comment`, `result_status`, `result_updated_at`, `has_result`, `created_at`, `updated_at`. |
| `SupplyItem` | `product_id`, `name`, `qty`, `price` (money string or `null`), `unit`. |

## Dashboard

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/dashboard` | Требуется | designer + subscription.active | — | Сводная аналитика dashboard. |

### Query

| Поле | Тип | Описание |
|---|---|---|
| `period` | `week\|month\|quarter\|year\|custom` | По умолчанию `month`. |
| `from`, `to` | date | Пользовательский диапазон; обязательны для `period=custom`. |
| `date_from`, `date_to` | date | Совместимые aliases для `from`/`to`. |
| `timezone` | IANA timezone | Часовой пояс расчёта. |

Ответ: `{data: <dashboard analytics object>}`. Набор показателей рассчитывается сервером; клиент должен читать поля как analytics payload, а не выводить значения по предположению.

## Clients

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/clients` | Требуется | designer + active | — | Список клиентов. |
| `POST` | `/clients` | Требуется | designer + active | `multipart/form-data` при `files`, иначе JSON | Создать клиента. |
| `GET` | `/clients/{client}` | Требуется | designer + active | — | Клиент с кратким списком проектов. |
| `PUT`, `PATCH` | `/clients/{client}` | Требуется | designer + active | multipart/JSON | Обновить клиента. |
| `DELETE` | `/clients/{client}` | Требуется | designer + active | — | Удалить; при связанных проектах передать `confirm=true`. |
| `GET` | `/clients/{client}/projects` | Требуется | designer + active | — | Проекты клиента. |
| `POST` | `/clients/{client}/files` | Требуется | designer + active | `multipart/form-data` | Добавить файлы (`files[]`). |
| `DELETE` | `/clients/{client}/files/{file}` | Требуется | designer + active | — | Удалить файл по его индексу. |

### Query: `GET /clients`

| Поле | Значение |
|---|---|
| `search` | Поиск по ФИО, телефону, email, комментарию, ссылке и названию проекта. |
| `status` | Системный ключ статуса клиента. |
| `type` | `person` или `company`. |
| `has_projects`, `without_projects` | Boolean-фильтры. |
| `sort` | `id`, `name`, `created_at`, `updated_at`; стандартный формат `ApiQuery`. |
| `per_page` | Размер страницы. |

### Body: create/update client

| Поле | Обязательно | Описание |
|---|---:|---|
| `full_name` (или alias `name`) | Да | Имя / название, до 255 символов. |
| `client_type` (или `type`) | Да | `person` или `company`; по умолчанию при alias — `person`. |
| `phone`, `email` | Да | Телефон и валидный email. |
| `status` | Да | Разрешённый для дизайнера системный ключ. |
| `comment`, `link` | Нет | Текст и URL. |
| `files[]` | Нет | Файлы; для этого использовать multipart. |
| `existing_files[]` | Update: нет | Пути сохраняемых файлов. |

### Response: `Client`

`id`, `type`, `name`, `phone`, `email`, `status`, `comment`, `links`, `files` (public URLs), `projects_count`, `total_projects_budget`, `created_at`, `updated_at`. В `GET /clients/{client}` также `projects[]`: `id`, `name`, `status`, `planned_end_date`, `planned_cost`, `actual_cost`.

## Client stages (воронка клиентов)

Та же воронка, что на сайте (`/pipelines/client`). Включает системные и добавленные статусы (`custom_*`).

| Метод | URL | Авторизация | Роли | Назначение |
|---|---|---|---|---|
| `GET` | `/client-stages` | Требуется | designer + active | Список всех статусов (в т.ч. добавленных на сайте). |
| `POST` | `/client-stages` | Требуется | owner (canManageClientPipeline) | Создать статус. Body: `name`, `color?`. |
| `PUT`, `PATCH` | `/client-stages/{stageId}` | Требуется | owner | Обновить `name` / `color`. |
| `DELETE` | `/client-stages/{stageId}` | Требуется | owner | Удалить; если есть клиенты — `move_to_stage_id`. |
| `POST` | `/client-stages/reorder` | Требуется | owner | `stages: [{id, position}]` или `stage_ids[]`. |

Ответ этапа: `id`, `system_key`, `name`, `color`, `position`, `is_system`, `is_active`.  
Статус клиента задаётся через `PATCH /clients/{id}` полем `status` = `system_key` этапа.

## Projects

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/projects` | Требуется | designer + active | — | Пагинированные проекты workspace. |
| `POST` | `/projects` | Требуется | designer + active | multipart/JSON | Создать проект. |
| `GET` | `/projects/{projectId}` | Требуется | designer + active | — | Карточка проекта. |
| `PUT`, `PATCH` | `/projects/{projectId}` | Требуется | designer + active | multipart/JSON | Изменить проект и checklist stages. |
| `DELETE` | `/projects/{projectId}` | Требуется | designer + active | — | Удалить, ответ `204`. |
| `PATCH` | `/projects/{projectId}/stage` | Требуется | designer + active | JSON | Перенести по pipeline. |
| `GET` | `/projects/{projectId}/activity` | Требуется | designer + active | — | Лента activity. |
| `GET` | `/projects/{projectId}/comments` | Требуется | designer + active | — | Только comment events. |
| `POST` | `/projects/{projectId}/comments` | Требуется | designer + active | JSON | Добавить комментарий. |

### Query: lists

`GET /projects`: `per_page` (default 20).  
`GET /projects/{id}/activity` и `/comments`: `per_page` (default 30); activity также принимает `filter`.

### Body: create/update project

| Поле | Обязательно | Описание |
|---|---:|---|
| `name` | Да | Название. |
| `status` | Да на уровне validation; при создании автоматически используется первая pipeline stage, если не передан | Системный ключ. Предпочтительно задать `stage_id`. |
| `stage_id` | Нет | ID project pipeline stage. |
| `client_id` | Нет | Доступный клиент workspace. |
| `start_date`, `planned_end_date`, `actual_end_date` | Нет | Даты; aliases: `planned_completion_date`, `actual_completion_date`. |
| `comment` | Нет | Текст. |
| `city`, `object_type`, `object_address`, `apartment_floor`, `apartment_entrance`, `apartment`, `area` | Нет | Данные объекта; `object_type`: `apartment`, `house`, `commercial`, `office`, `other`. |
| `repair_budget_planned`, `repair_budget_actual` | Нет | Числа; aliases: `renovation_budget_plan`, `renovation_budget_fact`. |
| `links[]` | Нет | `{title?, url}`. |
| `files[]`, `existing_files[]` | Нет | Новые файлы / сохранённые пути. |
| `stages[]` | Нет | Вложенные чек-листы: `id?`, `stage_type`, `name?`, `template_id?`, `deadline?`, `assign_task?`, `responsible_id?`, `steps[]`. |
| `stages[].steps[]` | Нет | `id?`, `title?`, `deadline?`, `responsible_id?`, `link?`, `result_status: pending|done`, `result_comment?`. |

`PATCH /projects/{id}/stage` принимает один из `stage_id` или `status`. `POST /projects/{id}/comments` принимает `{ "body": "..." }`. Все project GET/create/update ответы используют ресурс `Project`.

## Project Stages (pipeline)

Управление pipeline доступно только пользователю с правом управления project pipeline (владелец/соответствующая роль).

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/project-stages` | Требуется | designer + active | — | Pipeline stages, отсортированные по `position`. |
| `POST` | `/project-stages` | Требуется | designer + active + manage pipeline | JSON | Создать пользовательский stage. |
| `PUT`, `PATCH` | `/project-stages/{stageId}` | Требуется | designer + active + manage pipeline | JSON | Переименовать/изменить цвет. |
| `DELETE` | `/project-stages/{stageId}` | Требуется | designer + active + manage pipeline | JSON | Удалить; можно перенести карточки. |
| `POST` | `/project-stages/reorder` | Требуется | designer + active + manage pipeline | JSON | Задать порядок. |

| Endpoint | Body |
|---|---|
| Create | `name` required, `color?`. |
| Update | `name?`, `color?`. |
| Delete | `move_to_stage_id?`; если в stage есть карточки и поле не задано: `422` с `{data:null, meta:{card_count, move_required:true}}`. |
| Reorder | `stage_ids: integer[]` required; compatibility input: `stages: [{id, position?}]`. |

Ответы — `ProjectStage` или их коллекция.

## Tasks

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/tasks/kanban` | Требуется | designer + active | — | Канбан, сгруппированный по status. |
| `GET` | `/tasks/calendar` | Требуется | designer + active | — | События календаря. |
| `GET` | `/tasks` | Требуется | designer + active | — | Пагинированный список. |
| `POST` | `/tasks` | Требуется | designer + active | JSON | Создать задачу. |
| `GET` | `/tasks/{taskId}` | Требуется | designer + active | — | Детали. |
| `PUT`, `PATCH` | `/tasks/{taskId}` | Требуется | designer + active + edit permission | JSON | Изменить задачу. |
| `DELETE` | `/tasks/{taskId}` | Требуется | designer + active + edit permission | — | Удалить, `204`. |
| `PATCH` | `/tasks/{taskId}/status` | Требуется | designer + active + status permission | JSON | Изменить status. |

### Query / body

| Endpoint | Поля |
|---|---|
| `GET /tasks` | `project_id` (число или `none`), `assignee_id`, `per_page`. |
| `GET /tasks/calendar` | `date_from`/`date_to`; aliases `start`/`end`. |
| create/update | `title` required, `description?`, `assignee_id?`, `project_id?`, `status?`, `due_at` required date. |
| status | `status` required: `new`, `in_progress`, `completed` или `cancelled`. |

### Response: `Task`

`id`, `source_type: "designer_task"`, `title`, `description`, `status`, `due_at`, `completed_at`, `project: {id,name}|null`, `assignee: {id,name}`, `creator: {id,name}`, `is_overdue`, `permissions: {edit,change_status}`, `created_at`, `updated_at`.

`GET /tasks/kanban` возвращает `{data: {new:{count,items}, in_progress:{count,items}, completed:{count,items}, cancelled:{count,items}}}`. Calendar добавляет `{meta:{date_from,date_to}}`.

## Checklists

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/projects/{project}/checklists` | Требуется | designer + active | — | Checklist stages проекта. |
| `POST` | `/projects/{project}/checklists` | Требуется | designer + active | JSON | Создать checklist. |
| `GET` | `/projects/{project}/checklist-results` | Требуется | designer + active | — | Сгруппированные результаты. |
| `GET` | `/checklists/{checklist}` | Требуется | designer + active | — | Checklist с `items`. |
| `PUT`, `PATCH` | `/checklists/{checklist}` | Требуется | designer + active | JSON | Обновить. |
| `DELETE` | `/checklists/{checklist}` | Требуется | designer + active | — | Удалить, `204`. |
| `POST` | `/checklists/{checklist}/items` | Требуется | designer + active | JSON | Добавить item. |
| `POST` | `/checklist-items/reorder` | Требуется | designer + active | JSON | Переупорядочить items. |
| `PUT`, `PATCH` | `/checklist-items/{item}` | Требуется | designer + active | JSON | Изменить item. |
| `DELETE` | `/checklist-items/{item}` | Требуется | designer + active | — | Удалить item, `204`. |
| `PATCH` | `/checklist-items/{item}/completion` | Требуется | designer + active | JSON | Установить completion. |
| `PUT` | `/checklist-items/{item}/result` | Требуется | designer + active | JSON | Сохранить результат/комментарий. |

### Body

| Endpoint | Поля |
|---|---|
| create checklist | `stage_type` required (значение `ProjectStageType`); `stage_id` — compatibility alias; `name?`, `template_id?`, `deadline?`, `responsible_id?`, `assign_task?`, `save_as_template?`, `template_name?`, `items[]` или alias `steps[]`. Каждый item имеет `title` required и `position?`. |
| update checklist | `stage_type?`, `name?`, `template_id?`, `deadline?`, `responsible_id?`, `assign_task?`, `order?`, `items[]`. |
| create item | `title` required, `deadline?`, `responsible_id?`, `link?`, `order?` или `position?`. |
| update item | Те же поля; дополнительно `result_status: pending|done`, `result_comment?`. |
| reorder | `item_ids: integer[]` или compatibility `items: [{id, position?}]`. |
| completion | `done` required boolean; accepted alias `completed`. |
| result | `result_comment?`; accepted alias `result`. |

Ответы create/update — `Checklist` / `ChecklistItem`. `checklist-results` возвращает `{data: ...}` в серверной группировке результатов.

### Checklist templates

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/checklist-templates` | Требуется | designer + active | — | Общие и собственные шаблоны. |
| `POST` | `/checklist-templates` | Требуется | designer + active | JSON | Создать собственный шаблон. |
| `GET` | `/checklist-templates/{id}` | Требуется | designer + active | — | Получить шаблон. |
| `PUT`, `PATCH` | `/checklist-templates/{id}` | Требуется | designer + active | JSON | Изменить собственный шаблон. |
| `DELETE` | `/checklist-templates/{id}` | Требуется | designer + active | — | Удалить собственный шаблон, `204`. |

Query list: `stage_id` или `stage_type`, `search`. Body create: `name`, `type`, `steps` — обязательны; `steps` — непустой массив. Update: те же поля, все optional. Ресурс: `id`, `name`, `type`, `steps`, `is_shared`, `created_at`, `updated_at`.

## Supplies

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/projects/{project}/supplies` | Требуется | designer + active | — | Поставки проекта. |
| `POST` | `/projects/{project}/supplies` | Требуется | designer + active | multipart/JSON | Создать поставку. |
| `GET` | `/supplies/{supply}` | Требуется | designer + active | — | Карточка поставки. |
| `PUT`, `PATCH` | `/supplies/{supply}` | Требуется | designer + active | multipart/JSON | Изменить поставку. |
| `DELETE` | `/supplies/{supply}` | Требуется | designer + active | — | Удалить, `204`. |
| `PATCH` | `/supplies/{supply}/status` | Требуется | designer + active | JSON | Изменить status. |
| `POST` | `/supplies/{supply}/send` | Требуется | designer + active | JSON | Отправить поставщику с текущими offer data. |
| `GET` | `/supplies/{supply}/comments` | Требуется | designer + active | — | Текущий комментарий как comment list. |
| `POST` | `/supplies/{supply}/comments` | Требуется | designer + active | JSON | Сохранить комментарий. |
| `POST` | `/supplies/{supply}/percentage-proposals` | Требуется | designer + active | JSON | Отправить предложение процента. |
| `POST` | `/supplies/{supply}/percentage-proposals/accept` | Требуется | designer + active | JSON | Принять предложение. |
| `POST` | `/supplies/{supply}/percentage-proposals/reject` | Требуется | designer + active | JSON | Отклонить предложение. |
| `POST` | `/supplies/{supply}/percentage-proposals/counter` | Требуется | designer + active | JSON | Контрпредложение. |
| `POST` | `/supplies/{supply}/items` | Требуется | designer + active | JSON | Добавить товар. |
| `PUT`, `PATCH` | `/supply-items/{item}` | Требуется | designer + active | JSON | Изменить товар; обязательно передать supply ID. |
| `DELETE` | `/supply-items/{item}` | Требуется | designer + active | JSON/query | Удалить товар; обязательно передать supply ID. |

### Body

| Endpoint | Поля |
|---|---|
| create supply | `supplier_id` required; `summa?` (alias `amount`), `category?`, `room?`, `date_planned?` (alias `planned_delivery_date`), `status?`, `bonus_percent?` (alias `percentage`), `comment?`, `included_step_ids?` (alias `checklist_item_result_ids`), `items[]`, `files[]`, `send_to_supplier?`, `submit_action: draft|send`. |
| update supply | Те же поля; `supplier_id` становится optional. |
| status | `{status}`: `draft`, `order_created`, `order_confirmed`, `advance_payment`, `full_payment`, `delivery_completed`. |
| comment | `{comment}` required, max 5000. |
| percentage proposal | `bonus_percent?` (alias `percentage`) от 0 до 100, `message?`; для `/counter` процент фактически обязателен. |
| item create/update | `product_id` required, `quantity?` (alias `qty`), `price?`; update/delete требуют `supply_id` в JSON или query. |

### Response: `Supply`

`id`, `project_id`, `client_id`, `user_id`, `supplier_id`, `supplier_name`, `status`, `is_sent_to_supplier`, `summa`, `bonus_percent`, `category`, `mark`, `room`, `date_planned`, `date_actual`, `prepayment_date`, `payment_date`, `prepayment_amount`, `payment_amount`, `links`, `files`, `comment`, `items`, `checklist_item_ids`, `offer`, `created_at`, `updated_at`.

`GET /supplies/{id}/comments` возвращает `data[]` из `{id, body, author_type: "designer", created_at}`. Поле `offer` — текущий offer payload для роли designer; его конкретный набор зависит от состояния сделки.

## Suppliers

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/suppliers` | Требуется | designer + active | — | Каталог доступных поставщиков. |
| `POST` | `/suppliers` | Требуется | designer + active | `multipart/form-data` при `logo` | Создать поставщика дизайнера. |
| `GET` | `/suppliers/{supplier}` | Требуется | designer + active | — | Профиль. |
| `PUT`, `PATCH` | `/suppliers/{supplier}` | Требуется | designer + active | multipart/JSON | Обновить. |
| `DELETE` | `/suppliers/{supplier}` | Требуется | designer + active | — | Удалить, `204`. |
| `GET` | `/suppliers/{supplier}/products` | Требуется | designer + active | — | Товары поставщика. |
| `POST`, `DELETE` | `/suppliers/{supplier}/favorite` | Требуется | designer + active | — | Переключить избранное; оба метода toggle. |

### Query / body / response

`GET /suppliers`: `search`, `city`, `sphere`, `favorite`, `status`, `brand`.  
`GET /suppliers/{id}/products`: `search`, `category`, `category_id`.

Create/update body: `name`, `email` required; `phone?`, `website?`, `brands?: array`, `cities_presence?: array`, `logo?: image`, `inn?`. Resource `Supplier`: `id`, `user_id`, `name`, `email`, `phone`, `telegram`, `whatsapp`, `website`, `city`, `address`, `sphere`, `brands`, `cities_presence`, `recommend`, `profile_status`, `moderation_status`, `logo_url`, `is_owned_by_designer`, `is_favorite`, `created_at`, `updated_at`.

Ресурс товара: `id`, `supplier_id`, `name`, `sku`, `category`, `description`, `price`, `unit`, `image_url`, `created_at`, `updated_at`. Favorite ответ: `{data:{is_favorite:boolean}}`.

## Subscription

Все маршруты требуют Bearer token, но **не** `subscription.active`. Изменения биллинга требуют `can_manage_billing`.

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/subscription/plans` | Требуется | Авторизованный | — | Тарифы. |
| `GET` | `/subscription` | Требуется | Авторизованный | — | Текущий subscription state. |
| `GET` | `/subscription/history` | Требуется | Авторизованный | — | До 20 платежей. |
| `POST` | `/subscription/checkout` | Требуется | Billing manager | JSON | Оформить оплату. |
| `POST` | `/subscription/change-plan` | Требуется | Billing manager | JSON | Сменить тариф. |
| `POST` | `/subscription/renew` | Требуется | Billing manager | JSON | Возобновить автопродление. |
| `POST` | `/subscription/resume` | Требуется | Billing manager | JSON | Alias для renew. |
| `POST` | `/subscription/cancel` | Требуется | Billing manager | JSON | Отменить. |

`Plan`: `key`, `name`, `price`, `currency: "KZT"`, `billing_period: "month"`, `period_days`, `included_seats`, `max_members`, `recommended`, `features`, `feature_keys`.

`Subscription`: `plan`, `status`, `has_access`, `is_on_trial`, `trial_days_left`, `access_ends_at`, `next_charge_at`, `next_charge_amount`, `auto_renew`, `payment_method`, `can_manage_billing`, `team_seats_used`, `team_seats_max`.

`Payment`: `id`, `plan`, `amount`, `period_days`, `starts_at`, `ends_at`, `status`, `payment_method`, `created_at`.

| Endpoint | Body |
|---|---|
| checkout | `plan`, `payment_method` required (`kaspi`, `card`, `promo`); `promo_code?`, `card_number?`, `card_expiry?`. |
| change-plan | `plan` required, `confirm_team_downgrade?` boolean. Corporate downgrade may return `422` until confirmation. |
| cancel | `reason?`: `expensive`, `not_using`, `missing_features`, `tech_issues`, `other`. |

Checkout response: `{data:{subscription: Subscription, payment: Payment}}`; plans: `{data:{plans:[Plan]}}`; history: `{data:{payments:[Payment]}}`.

## Team

Для team routes требуется Corporate access. `invitations`, создание аккаунтов, смена роли, удаление и управление приглашением дополнительно требуют permission управлять участниками.

| Метод | URL | Авторизация | Роли | Content-Type | Назначение |
|---|---|---|---|---|---|
| `GET` | `/team` | Требуется | Corporate team | — | Активная команда или `data:null`. |
| `GET` | `/team/members` | Требуется | Corporate team | — | Активные участники. |
| `POST` | `/team/invitations` | Требуется | Team manager | JSON | Пригласить существующего пользователя. |
| `GET` | `/team/invitations` | Требуется | Team manager | — | Приглашения. |
| `POST` | `/team/members/create-account` | Требуется | Team manager | JSON | Создать designer account и добавить в команду. |
| `PATCH` | `/team/members/{member}/role` | Требуется | Team manager | JSON | Изменить роль. |
| `DELETE` | `/team/members/{member}` | Требуется | Team manager | — | Деактивировать участника. |
| `POST` | `/team/invitations/{invitation}/resend` | Требуется | Team manager | JSON | Продлить pending invitation + повторное уведомление. |
| `POST` | `/team/invitations/{invitation}/accept` | Требуется | Invitee (без личной подписки) | — | Принять приглашение → Active member. |
| `POST` | `/team/invitations/{invitation}/decline` | Требуется | Invitee (без личной подписки) | — | Отклонить приглашение, освободить seat. |
| `DELETE` | `/team/invitations/{invitation}` | Требуется | Team manager | — | Отменить invitation. |
| `GET` | `/team/assignees` | Требуется | Corporate team | — | Допустимые исполнители задач. |

| Endpoint | Body / response |
|---|---|
| invite | `{email, role}`; role: `admin` или `designer`. Ответ `TeamInvitation`, `201`. |
| create-account | `{name, email, password, password_confirmation, role}`; ответ `TeamMember`, `201`. |
| change role | `{role: "admin"\|"designer"}`; ответ `TeamMember`. |
| remove member | `{data:{id, status:"inactive"}}`. |

`Team`: `id`, `name`, `status`, `owner_id`, `max_members`, `seats_used`, `seats_remaining`, `can_manage_billing`.  
`TeamMember`: `id`, `user_id`, `name`, `email`, `role`, `status`, `joined_at`.  
`TeamInvitation`: `id`, `email`, `role`, `status`, `expires_at`, `created_at`.  
`Assignee`: `id`, `name`, `email`, `role`.

## Legacy aliases / compatibility

Не используйте эти endpoint'ы в новом мобильном коде, если есть основной маршрут. Они сохранены для обратной совместимости.

| Alias | Статус | Замена / примечание |
|---|---|---|
| `GET|POST /objects`, `GET|PUT|PATCH|DELETE /objects/{id}` | Deprecated | Старый passport objects API. Используйте `/projects` и `/clients`. |
| `GET /supplier-orders`, `GET /supplier-orders/{id}` | Compat | Старое представление поставок. Новое создание/работа — `/projects/{project}/supplies` и `/supplies/{supply}`. |
| `POST /supplier-orders/{supply}/offer/send` | Compat | `/supplies/{supply}/percentage-proposals`. |
| `POST /supplier-orders/{supply}/offer/accept` | Compat | `/supplies/{supply}/percentage-proposals/accept`. |
| `POST /supplier-orders/{supply}/offer/reject` | Compat | `/supplies/{supply}/percentage-proposals/reject`. |
| `POST /supplier-orders/{supply}/offer/counter` | Compat | `/supplies/{supply}/percentage-proposals/counter`. |
| `GET|POST /templates`, `DELETE /templates/{id}` | Alias | Alias для соответствующих операций `/checklist-templates`. Полные show/update доступны только на `/checklist-templates/{id}`. |

## Examples

Все примеры предполагают заголовки:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

### Create task / создать задачу

```http
POST /api/tasks

{
  "title": "Согласовать раскладку плитки",
  "description": "Проверить санузел и кухню",
  "project_id": 42,
  "assignee_id": 17,
  "status": "new",
  "due_at": "2026-08-05T18:00:00+05:00"
}
```

### Create project / создать проект

```http
POST /api/projects

{
  "name": "Квартира Абая",
  "client_id": 12,
  "stage_id": 3,
  "start_date": "2026-08-01",
  "planned_end_date": "2026-10-30",
  "city": "Алматы",
  "object_type": "apartment",
  "object_address": "пр. Абая, 10",
  "area": 84.5,
  "repair_budget_planned": 2999000,
  "links": [
    { "title": "Moodboard", "url": "https://example.com/moodboard" }
  ]
}
```

### Create supply / создать поставку

Для файлов вместо JSON используйте `multipart/form-data`; поля `items` передаются как form-array.

```http
POST /api/projects/42/supplies

{
  "supplier_id": 9,
  "summa": 145000,
  "category": "Сантехника",
  "room": "Санузел",
  "date_planned": "2026-08-12",
  "status": "draft",
  "bonus_percent": 5,
  "comment": "Нужна доставка до обеда",
  "included_step_ids": [101, 102],
  "items": [
    { "product_id": 501, "qty": 2, "price": 72500 }
  ],
  "submit_action": "draft"
}
```

### Invite team member / пригласить участника

```http
POST /api/team/invitations

{
  "email": "designer@example.com",
  "role": "designer"
}
```

Успешный ответ: `201 { "data": { "id": 7, "email": "designer@example.com", "role": "designer", "status": "pending", "expires_at": "...", "created_at": "..." } }`.
