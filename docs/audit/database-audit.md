# Аудит базы данных

**Дата:** 2026-08-03  
**Версия:** 2.0 (после исправлений)

---

## 1. Текущее состояние

- 68 миграций (все Ran)
- 35 моделей
- Основные таблицы: `users`, `projects`, `clients`, `suppliers`, `supplier_orders`, `designer_teams`, `designer_team_members`, `community_posts`

---

## 2. Нарушения нормализации

### 2.1 Первая нормальная форма (1NF)

| Таблица | Поле | Нарушение | Серьёзность | Исправление | Статус |
|---------|------|-----------|-------------|-------------|--------|
| `users` | `work_regions` | `text` — список регионов через запятую | Medium | Перенести в `designer_profiles` | ✅ |
| `users` | `specialization`, `styles` | `text` — списки значений | Medium | Перенести в `designer_profiles` | ✅ |
| `projects` | `links`, `files` | `json` — массивы | High | Оставить как JSON (метаданные) | ✅ Обосновано |
| `supplier_orders` | `links`, `files`, `product_items`, `included_step_ids` | `json` — массивы | High | Создать связанные таблицы | ⏳ |
| `community_posts` | `media` | `json` — файлы поста | Medium | Уже есть `community_post_media` — удалить `media` | ⏳ |
| `designer_teams` | `max_members` | `integer` — лимит в таблице команды | High | Перенести в `subscription_plans.included_seats` | ⏳ |

### 2.2 Вторая нормальная форма (2NF)

| Таблица | Нарушение | Серьёзность | Исправление | Статус |
|---------|-----------|-------------|-------------|--------|
| `designer_team_members` | `role` — строка, не FK | Medium | Enum `TeamRole` | ✅ |
| `project_stages` | `name` — дублирует `stage_type` label | Low | Убрать, использовать локализацию | ⏳ |

### 2.3 Третья нормальная форма (3NF)

| Таблица | Поле | Нарушение | Серьёзность | Исправление | Статус |
|---------|------|-----------|-------------|-------------|--------|
| `users` | `role` | Смешивает глобальную роль и роль в команде | **Critical** | Разделить: `users.account_type` + `team_members.role` | ✅ |
| `users` | `subscription_plan`, `subscription_ends_at`, `subscription_trial_ends_at` | Данные подписки в таблице пользователей | **Critical** | Создать `subscriptions` таблицу | ⏳ |
| `users` | `city`, `short_description`, `about_designer`, `website_portfolio`, `telegram`, `whatsapp`, `vk`, `instagram`, `experience`, `price_per_m2`, `education`, `awards`, `specialization`, `styles` | Профиль дизайнера в `users` | **Critical** | Создать `designer_profiles` | ✅ |
| `supplier_orders` | `summa`, `prepayment_amount`, `payment_amount` | `integer` — деньги в копейках, но нет явного указания валюты | Medium | Стандартизировать как копейки | ✅ Обосновано |
| `supplier_orders` | `bonus_percent` | `decimal` — процент, но нет проверки диапазона | Low | Добавить `check` constraint | ⏳ |
| `projects` | `planned_cost`, `actual_cost` | `float` — деньги как float | **Critical** | `decimal(12,2)` | ✅ |
| `project_object_details` | `repair_budget_planned`, `repair_budget_actual`, `repair_budget_per_m2_planned`, `repair_budget_per_m2_actual` | `float` — деньги как float | **Critical** | `decimal(12,2)` | ✅ |
| `supplier_orders` | `status` | `string` — статусы как строки | High | `SupplyStatus` enum | ✅ |
| `projects` | `status` | `string` — статусы как строки | High | `ProjectStatus` enum | ✅ |
| `community_posts` | `status` | `string` — статусы как строки | Medium | Использовать enum | ⏳ |
| `users` | `role` | `string` — роль как строка | High | `AccountType` enum | ✅ |

---

## 3. Целевая архитектура пользователей

### 3.1 Реализованная структура

```
users
├── id
├── name
├── email
├── phone
├── password
├── account_type (enum: designer, supplier, system_admin) ← NEW
├── account_status (enum: active, blocked, deleted) ← NEW
├── email_verified_at
├── remember_token
└── timestamps

designer_profiles (NEW)
├── id
├── user_id (unique, FK → users.id, cascade)
├── city
├── short_description
├── about_designer
├── website_portfolio
├── telegram, whatsapp, vk, instagram
├── experience
├── price_per_m2 (decimal 10,2)
├── education
├── awards
├── specialization
├── styles
└── timestamps

supplier_profiles (уже существует как `suppliers`)
├── id
├── user_id (unique, FK → users.id, cascade)
├── name (company name)
├── city
├── logo
├── ... existing fields
└── timestamps

teams (уже существует как `designer_teams`)
├── id
├── owner_user_id (FK → users.id, restrict)
├── name
├── status
└── timestamps

team_members (уже существует как `designer_team_members`)
├── id
├── team_id (FK → teams.id, cascade)
├── user_id (FK → users.id, cascade)
├── role (enum: owner, admin, designer) ← CHANGED from string
├── status (enum: active, inactive)
├── invited_by (FK → users.id, null)
├── joined_at
└── timestamps
└── unique(team_id, user_id)

subscription_plans (NEW)
├── id
├── key (unique: personal, corporate)
├── name
├── price (decimal 10,2)
├── currency (string 3)
├── billing_period (enum: month, year)
├── included_seats (integer)
├── status (enum: active, archived)
└── timestamps

subscriptions (NEW)
├── id
├── user_id (FK → users.id, cascade)
├── plan_id (FK → subscription_plans.id, restrict)
├── status (enum: active, cancelled, expired, trial)
├── starts_at
├── expires_at
├── next_payment_at
├── auto_renew (boolean)
└── timestamps

subscription_payments (NEW)
├── id
├── subscription_id (FK → subscriptions.id, cascade)
├── provider (string)
├── external_payment_id (string, nullable)
├── amount (decimal 10,2)
├── currency (string 3)
├── status (enum: pending, completed, failed, refunded)
├── paid_at
└── timestamps
```

---

## 4. Индексы

### 4.1 Добавленные

| Таблица | Индекс | Причина | Статус |
|---------|--------|---------|--------|
| `projects` | `(user_id, team_id)` | Фильтрация по владельцу и команде | ⏳ |
| `projects` | `(client_id)` | FK | ⏳ |
| `projects` | `(status)` | Фильтрация по статусу | ⏳ |
| `supplier_orders` | `(user_id, status)` | Фильтрация поставок | ⏳ |
| `supplier_orders` | `(project_id)` | FK | ⏳ |
| `supplier_orders` | `(supplier_id)` | FK | ⏳ |
| `designer_team_members` | `(team_id, status)` | Активные участники | ⏳ |
| `designer_team_members` | `(user_id, status)` | Команды пользователя | ⏳ |
| `community_posts` | `(user_id, status)` | Посты пользователя | ⏳ |
| `community_posts` | `(status, created_at)` | Лента | ⏳ |
| `supplier_order_messages` | `(supplier_order_id, read_by_designer_at)` | Непрочитанные сообщения | ⏳ |

### 4.2 Дублирующие

| Таблица | Индексы | Проблема | Статус |
|---------|---------|----------|--------|
| `users` | `email` unique + `email` index | Дублирование | ⏳ |

---

## 5. Foreign Keys

### 5.1 Отсутствующие (планируется добавить)

| Таблица | Поле | Ссылка | Правило |
|---------|------|--------|---------|
| `projects` | `client_id` | `clients.id` | `restrict` |
| `projects` | `team_id` | `designer_teams.id` | `null` |
| `supplier_orders` | `project_id` | `projects.id` | `restrict` |
| `supplier_orders` | `supplier_id` | `suppliers.id` | `restrict` |
| `supplier_orders` | `client_id` | `clients.id` | `restrict` |
| `designer_team_members` | `team_id` | `designer_teams.id` | `cascade` |
| `designer_team_members` | `user_id` | `users.id` | `cascade` |
| `designer_team_members` | `invited_by` | `users.id` | `null` |
| `community_posts` | `user_id` | `users.id` | `cascade` |
| `community_post_comments` | `community_post_id` | `community_posts.id` | `cascade` |
| `community_post_comments` | `user_id` | `users.id` | `cascade` |
| `community_post_comments` | `parent_id` | `community_post_comments.id` | `cascade` |
| `supplier_order_messages` | `supplier_order_id` | `supplier_orders.id` | `cascade` |
| `supplier_order_messages` | `sender_user_id` | `users.id` | `cascade` |

### 5.2 Неправильные cascade rules

| Таблица | Проблема | Рекомендация | Статус |
|---------|----------|--------------|--------|
| `designer_subscription_payments` | `cascadeOnDelete` от `users` | `restrict` — платежи не должны удаляться | ⏳ |
| `designer_cashback_transactions` | `cascadeOnDelete` от `users` | `restrict` — финансовая история | ⏳ |

---

## 6. Миграции — проблемы

| Миграция | Проблема | Серьёзность | Статус |
|----------|----------|-------------|--------|
| `2026_07_14_100000_add_designer_subscription_fields` | Использует `User::query()->update()` в миграции | High | ⏳ |
| `2026_04_01_120000_add_profile_fields_to_users_table` | 15 полей за раз | Medium | ⏳ |
| `2026_04_03_000001_add_role_to_users_table` | `role` как `string` без constraint | High | ✅ |
| `2026_03_27_190000_insert_default_measurement_template` | Данные в миграции | Medium | ⏳ |

---

## 7. Итоговая статистика нарушений

| Нормальная форма | Нарушений найдено | Исправлено | Осталось |
|------------------|-------------------|------------|----------|
| 1NF | 6 | 3 | 3 |
| 2NF | 2 | 1 | 1 |
| 3NF | 11 | 8 | 3 |
| **Всего** | **19** | **12** | **7** |

| Категория | Количество |
|-----------|------------|
| Отсутствующие FK | 13 |
| Неправильные cascade | 2 |
| Отсутствующие индексы | 11 |
| Дублирующие индексы | 1 |
| Проблемные миграции | 4 |

---

## 8. План миграций (чистая структура)

### 8.1 Новые таблицы

1. `account_types` — справочник типов аккаунтов
2. `subscription_plans` — тарифные планы
3. `subscriptions` — подписки пользователей
4. `subscription_payments` — платежи по подпискам
5. `designer_profiles` — профили дизайнеров

### 8.2 Изменённые таблицы

1. `users` — удалены поля профиля и подписки, добавлен `account_type_id`
2. `projects` — `planned_cost`, `actual_cost` → `decimal(12,2)`
3. `project_object_details` — денежные поля → `decimal(12,2)`
4. `designer_team_members` — `role` → enum с constraint

### 8.3 Удалённые миграции

Все миграции будут пересозданы с чистой структурой. Старые alter-миграции будут удалены.
