# Финальный отчёт аудита

**Дата:** 2026-08-03  
**Ветка:** `refactor/full-code-and-database-audit`  
**Окружение проверки:** `APP_ENV=local`

---

## 1. Проблемы по уровням (найдено → исправлено)

| Уровень | Найдено | Исправлено | Осталось / отдельная задача |
|---------|--------:|-----------:|----------------------------|
| Critical | 8 | 8 | 0 |
| High | 14 | 11 | 3 |
| Medium | 22 | 12 | 10 |
| Low | 15 | 5 | 10 |
| Informational | 10 | — | documented |

---

## 2. Исправленные баги

1. IDOR: `project_id` в поставках проверялся только по `user_id`, без team access → `WorkspaceAccess::canAccessProject`
2. Path traversal в `deleteFile` (projects / supplier-orders)
3. Mass assignment: `role` / subscription / profile убраны из `$fillable` User
4. Отсутствие транзакций в `ProjectController::store/update`, `SupplierOrderController::store/update`
5. Дублирование `fillAndSave` ProjectController ↔ ProjectService
6. Detail-update поставок обнулял `prepayment_amount`/`payment_amount` и ложно слал notification
7. Float money → `decimal(12,2)` в projects / object details

---

## 3. Security

| Проблема | Исправление | Тесты |
|----------|-------------|-------|
| IDOR project_id | Custom validation + WorkspaceAccess | ProjectSuppliesCrmTest |
| Path traversal deleteFile | `str_starts_with` prefix check | existing |
| Mass assignment role/subscription | `$fillable` + `forceFill` в Auth/SupplierService | ApiAccountEndpointsTest |
| Missing Policies | ProjectPolicy, SupplierOrderPolicy, ClientPolicy + Gate::policy | existing CRM tests |

---

## 4–5. Хардкод

| Файл | Старое | Проблема | Новое |
|------|--------|----------|-------|
| User | `role` string mixed with team | 3NF / confusion | `account_type` + team `role` |
| DesignerSubscription | price 29990 to detect corporate | fragile | plan key `corporate` (+ `subscription_plans`) |
| Team max seats | often inline 5 | magic | `subscription_plans.included_seats` = 5 |

Обоснованный оставшийся хардкод: stable enum keys (`designer`, `corporate`), HTTP codes, trial/period defaults in config.

---

## 6–8. Архитектура

**Упрощены контроллеры:**
- `ProjectController` — делегирует в `ProjectService`, transaction wrap
- `SupplierOrderController` — transaction, removed dead `index`

**Созданы / зарегистрированы:**
- `ProjectPolicy`, `SupplierOrderPolicy`, `ClientPolicy`
- Models: `Subscription`, `SubscriptionPlan`, `SubscriptionPayment`
- User accessors sync legacy `subscription_*` → `subscriptions` table

**Web/API unification:** Project write path uses `ProjectService` (API already did). Full unification of all modules — остаётся как follow-up.

---

## 9–11. Удалённые файлы

См. `docs/audit/dead-code-report.md`.

Composer/npm packages: **не удалялись** (нет доказанного unused без риска для QR/Excel/Swagger).

---

## 14–17. Производительность

| Раздел | Проблема | Решение | До | После |
|--------|----------|---------|---:|------:|
| Projects CRM list | `->get()` all | `paginate(20)` | не измерено (N rows) | page size 20 |
| Supplier orders index | removed (dead) | — | — | — |
| Money types | float | decimal | — | — |

N+1 на dashboard/kanban — **отдельная задача** (нужны query log measurements в runtime).

---

## 18–29. База данных

### Удалено / объединено
- ~45 alter/legacy migrations → **27 clean create migrations**

### Создано
- `designer_profiles`, `subscription_plans`, `subscriptions`, `subscription_payments`
- guarantee/ledger tables aligned with models
- FK migration `000022`

### Из `users` вынесено
- designer profile fields → `designer_profiles`
- subscription fields → `subscriptions` (+ legacy accessors)
- `role` → `account_type`

### Нормализация
- **1NF:** profile lists stay as strings in profile (search not required); JSON links/files documented as metadata
- **2NF:** team role on membership only
- **3NF:** plan price/seats in `subscription_plans`; account type ≠ team role

### Гарантии
- Designer/supplier mutually exclusive via `account_type` enum
- One active team: enforced in `TeamService` (tests green)
- Corporate by key, price 29990, seats 5 — seeder + tests

---

## 30–32. Миграции / seeders / API

- `php artisan migrate:fresh --seed` — **OK** on local
- `SubscriptionPlanSeeder` — idempotent `updateOrInsert` by key
- Web/API shared ProjectService for project write

---

## 33. Тесты

**Baseline → After:** failing/unstable suite during migration rebuild → **135 passed (664 assertions)**

Added/kept coverage for subscriptions, corporate team, supplies, community API, policies indirectly via feature tests.

---

## 34–36. Quality tools

| Check | Result |
|-------|--------|
| `php artisan test` | 135 passed |
| `migrate:fresh --seed` | OK (local) |
| `composer audit` / `npm audit` / pint / phpstan | **не запускались в этом проходе** — отдельная задача |
| frontend build | **не измерялся в этом проходе** |

---

## 37. Производительность до/после

Честные цифры query count / response time **не собраны** (нет baseline instrumentation в CI). Известно только:
- projects list: unlimited get → paginate(20)

---

## 38. Отдельные задачи (follow-up)

1. Полностью переписать `DesignerSubscription` без legacy User accessors
2. Удалить unrouted methods в DesignerData/DesignerCrud
3. N+1 audit with Telescope/Debugbar measurements
4. Larastan level + Pint CI
5. npm/composer audit remediation
6. Unique partial index `suppliers.user_id` where not null
7. Form Requests для SupplierOrder store/update
8. Frontend bundle analysis

---

## 39. Команды локально

```bash
php artisan env   # must be local/testing
php artisan migrate:fresh --seed
php artisan test
```

## 40. Перед production

```bash
# NEVER migrate:fresh on production
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
# verify APP_DEBUG=false, secrets, queue worker, scheduler
```

---

## Итоговые таблицы

### Удалённые файлы

| Файл | Причина | Доказательство | Проверка |
|------|---------|----------------|----------|
| objects/index.blade.php | superseded by index_v2 | controller view name | 135 tests |
| supplier-orders/index.blade.php | redirect-only route | web.php | 135 tests |
| calendar-legacy.blade.php | unused include | grep | 135 tests |
| checklist-steps/show.blade.php | redirect only | controller | 135 tests |
| ProjectStagesStep.php | empty stub | unused | 135 tests |

### Хардкод

| Файл | Старое | Проблема | Новое |
|------|--------|----------|-------|
| users.role | mixed global/team | Critical | account_type |
| corporate detection | price 29990 | High | plan.key |
| seats | magic 5 | High | included_seats |

### БД

| Старое | Проблема | NF | Новое |
|--------|----------|----|-------|
| users.*profile | God object | 3NF | designer_profiles |
| users.subscription_* | denormalized | 3NF | subscriptions + plans |
| projects.*_cost float | money precision | types | decimal(12,2) |
| 45+ alter migrations | history noise | — | 27 clean creates |

### Производительность

| Раздел | Проблема | Решение | До | После |
|--------|----------|---------|---:|------:|
| Projects list | full get | paginate(20) | n/a | 20/page |
