# CRM Redesign — Production Report

## 1. Что изменено / What changed

Единая CRM-оболочка для «Кабинета дизайнера» по UX-паттернам Bitrix24 (воронки, overlay-карточки, лента), с сохранением бизнес-логики и цветовой гаммы проекта.

- Design tokens + компактные CRM-компоненты (`crm-*` в `app.css`)
- Навигация: Панель → **Проекты** → **Задачи** → Клиенты → Поставщики (без «Паспорт» и «Поставки» в меню)
- Панель управления: аналитика + Chart.js (без календаря)
- Задачи: календарь перенесён с dashboard
- Проекты: канбан-воронка, overlay-карточка, вкладки Общие / Поставки / Чек-листы, лента событий
- Воронки в БД (`pipelines` / `pipeline_stages`), настраиваемые владельцем
- Паспорт объекта → «Данные объекта» + таблица `project_object_details` (dual-read)
- Поставки доступны внутри проекта; `/supplier-orders` редиректит в проекты

## 2. Переработанные страницы

| Страница | Изменение |
|---|---|
| `/dashboard` | Аналитика, метрики, 4 графика |
| `/tasks` | Календарь (бывший dashboard calendar) |
| `/projects` | Новый CRM kanban (`crm.blade.php`) |
| `/projects/{id}` | Redirect в overlay CRM |
| `/objects` | Redirect в проекты (`?manage=1` — legacy UI) |
| `/supplier-orders` | Redirect в проекты |
| `/clients/{id}` | Блок связанных проектов |
| Layout sidebar | Новый порядок пунктов |

## 3. Модели и таблицы

**Новые:** `Pipeline`, `PipelineStage`, `ActivityEvent`, `ProjectObjectDetail`  
**Обновлены:** `Project`, `PassportObject`, `Client`

**Таблицы:** `pipelines`, `pipeline_stages`, `activity_events`, `project_object_details`  
Legacy `passport_objects` **не удалена**.

## 4. Миграции

`database/migrations/2026_07_29_140000_create_crm_pipelines_and_activity_tables.php`

- создаёт таблицы
- сидит дефолтные воронки для designer-пользователей
- backfill `project_object_details`
- seed `activity_events` (`project.created`)

## 5. Перенос данных

1. Для каждого `role=designer` → project + supply pipeline со статусами из Enums  
2. `projects.object_id` → `project_object_details` (chunkById)  
3. События создания проектов в ленту  

Проверка: `php artisan crm:verify-migration`

## 6. Тесты

`tests/Feature/CrmRedesignTest.php` — 14 тестов (создание, статус, лента, колонки, права, redirects, dashboard/tasks, object details).

## 7. Команды

```bash
php artisan migrate
php artisan crm:verify-migration
npm run build
php artisan test --filter=CrmRedesignTest
```

## 8. Environment variables

Новых env **не добавлено**.

## 9. Ручные действия перед production

1. **Backup БД** (обязательно)
2. `php artisan migrate`
3. `php artisan crm:verify-migration` — все checks OK
4. `npm run build` (CSS design system)
5. Smoke: dashboard, projects kanban DnD, create/save/cancel, supplies tab, tasks calendar, light/dark
6. Проверить API mobile/Sanctum (status keys не менялись)

## 10. Резервная копия

```bash
# MySQL example
mysqldump -u USER -p DATABASE > backup_crm_$(date +%Y%m%d_%H%M%S).sql

# SQLite
cp database/database.sqlite database/database.sqlite.bak
```

## 11. Проверка миграции

```bash
php artisan crm:verify-migration
```

Сверяет: pipelines на designer, stages count, object_details vs projects.object_id, activity events.

## 12. Откат

```bash
php artisan migrate:rollback --step=1
```

Откатит только CRM migration (pipelines, activity, project_object_details). Данные `projects` / `passport_objects` / orders **не трогаются**.

## 13. Оставшиеся риски

- Legacy `projects/index.blade.php` доступен через `?legacy=1`, но не основной UI
- Save проекта по-прежнему пересоздаёт stages/steps (как раньше) — риск для `included_step_ids`
- Кастомные колонки воронки хранят `system_key`; старые API ждут известные status strings
- Cleanup `passport_objects` — отдельная будущая миграция
- Полная унификация всех remaining pages (community, subscription) — частично через CSS tokens

## 14. Ключевые файлы

- `docs/crm-redesign-plan.md`
- `docs/crm-redesign-report.md`
- `app/Enums/*`, `app/Services/Crm/*`, `app/Support/AccountPermissions.php`
- `app/Models/Pipeline*.php`, `ActivityEvent.php`, `ProjectObjectDetail.php`
- Controllers: `DashboardController`, `TasksController`, `PipelineController`, `ProjectActivityController`, updated `ProjectController`
- Views: `designer/dashboard.blade.php`, `designer/tasks/index.blade.php`, `designer/projects/crm.blade.php`
- `resources/css/app.css` (CRM tokens)
- `routes/web.php`, `layouts/dashboard.blade.php`
- Migration + `crm:verify-migration` command
- `tests/Feature/CrmRedesignTest.php`

## 15. Сохранено без изменения логики

- Статусы проектов и поставок (system keys)
- Чек-листы / шаблоны / этапы `measurement…visualization`
- Offer negotiation, chat, cashback, reviews
- Calendar event derivation (`full_payment` → доплата done)
- Moderation flows, subscription, community, supplier portal
- API Sanctum endpoints

---

## Production checklist

- [ ] Backup database
- [ ] Deploy code
- [ ] `composer install --no-dev` (if needed)
- [ ] `php artisan migrate --force`
- [ ] `php artisan crm:verify-migration`
- [ ] `npm ci && npm run build`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Smoke test light + dark theme
- [ ] Smoke test mobile (320–430)
- [ ] Verify project create/move/comment
- [ ] Verify supply inside project still updates
- [ ] Verify calendar on `/tasks`
- [ ] Monitor logs 24h
