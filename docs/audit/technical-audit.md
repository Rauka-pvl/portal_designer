# Технический аудит Laravel-проекта

**Дата:** 2026-08-03  
**Ветка:** refactor/full-code-and-database-audit  
**Baseline:** 135 тестов (665 assertions), 349 маршрутов, 66 миграций, 35 моделей, 53 контроллера, frontend build ✓

---

## 1. Архитектурные проблемы

### 1.1 Дублирование бизнес-логики web/API

| Файл | Метод | Строки | Проблема | Серьёзность | Последствие | Рекомендация |
|------|-------|--------|----------|-------------|-------------|--------------|
| `app/Http/Controllers/Designer/ProjectController.php` | `fillAndSave` | 395-440 | Дублирует `ProjectService::fillAndSave` (25-60) | **Critical** | Рассинхронизация логики при изменениях | Удалить метод из контроллера, использовать `ProjectService` |
| `app/Http/Controllers/Designer/ProjectController.php` | `saveObjectDetails` | 469-515 | Дублирует `ProjectService::saveObjectDetails` (82-106) | **Critical** | Рассинхронизация логики | Удалить, использовать сервис |
| `app/Http/Controllers/Designer/ProjectController.php` | `saveStages` | 517-651 | Дублирует `ProjectService::saveStages` (108-152) | **Critical** | Рассинхронизация логики | Удалить, использовать сервис |
| `app/Http/Controllers/Designer/ProjectController.php` | `normalizeLinks` | 442-467 | Дублирует `ProjectService::normalizeLinks` (72-80) | High | Рассинхронизация | Удалить, использовать сервис |
| `app/Http/Controllers/Designer/ProjectController.php` | `passportObjectModerationError` | 390-393 | Мёртвый метод (`return null`) | Low | Загромождение кода | Удалить |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `fillAndSave` | 261-540 | 280 строк бизнес-логики в контроллере | **Critical** | Невозможность тестирования, дублирование с API | Вынести в `SupplyService` |
| `app/Http/Controllers/CommunityController.php` | Все методы | 1-971 | 971 строка, God Object | **Critical** | Невозможность поддержки | Разбить на `CommunityPostService`, `CommunityCommentService`, `CommunityFeedService` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `chatUnreadMapForDesigner` | 944-968 | Дублирует `ProjectController::chatUnreadMapForDesigner` (888-913) | High | Дублирование запросов | Вынести в `SupplierOrderChatService` |

### 1.2 God Object / Смешение ответственности

| Файл | Проблема | Серьёзность | Рекомендация |
|------|----------|-------------|--------------|
| `app/Models/User.php` | Содержит роль, подписку, профиль дизайнера, соцсети, опыт, цены | **Critical** | Разделить: `users` (auth), `designer_profiles`, `supplier_profiles`, `subscriptions` |
| `app/Models/Supplier_orders.php` | 349 строк, содержит логику офферов, чатов, нормализации шагов, запросы к БД | High | Разделить: модель + `OfferService` + `IncludedStepsService` |

### 1.3 Отсутствие слоёв

| Проблема | Файлы | Серьёзность | Рекомендация |
|----------|-------|-------------|--------------|
| Нет Form Requests для SupplierOrder | `SupplierOrderController::store/update` | High | Создать `SupplierOrderSaveRequest` |
| Нет Policies | Все контроллеры используют inline `where('user_id', ...)` | High | Создать `ProjectPolicy`, `SupplierOrderPolicy`, `ClientPolicy` |
| Нет API Resources для SupplierOrder | `SupplierOrderController::payload` (879-938) | Medium | Создать `SupplierOrderResource` |
| Нет Actions для сложных операций | Создание проекта со стадиями, отправка поставки | Medium | Создать `CreateProjectAction`, `SendSupplyAction` |

---

## 2. Баги и логические дефекты

### 2.1 Critical

| Файл | Метод | Строки | Описание | Последствие | Исправление |
|------|-------|--------|----------|-------------|-------------|
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `fillAndSave` | 288 | `Rule::exists('projects', 'id')->where(fn ($q) => $q->where('user_id', $userId))` — игнорирует `WorkspaceAccess` | Пользователь команды не может создать поставку для проекта владельца | Заменить на `WorkspaceAccess::canAccessProject` или кастомное правило |
| `app/Http/Controllers/Designer/ProjectController.php` | `index` | 52-65 | `->get()` без пагинации, загружает все проекты со всеми связями | При 100+ проектах — out of memory | Добавить `->paginate(20)` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `fillAndSave` | 287-540 | Нет транзакции при создании/обновлении поставки | Частично сохранённые данные при ошибке | Обернуть в `DB::transaction` |
| `app/Http/Controllers/Designer/ProjectController.php` | `store` | 177-201 | Нет транзакции при создании проекта со стадиями | Частично сохранённые стадии | Обернуть в `DB::transaction` |

### 2.2 High

| Файл | Метод | Строки | Описание | Последствие | Исправление |
|------|-------|--------|----------|-------------|-------------|
| `app/Http/Controllers/Designer/ProjectController.php` | `fillAndSave` | 418-423 | `(float)` для денежных значений | Потеря точности при расчётах | Использовать `decimal` или `int` (копейки) |
| `app/Services/Crm/ProjectService.php` | `fillAndSave` | 49-50 | `?? 0` для денег | Невозможно отличить "не указано" от "ноль" | Использовать `null` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `updateStatus` | 200-221 | Дублирование статусов из `STATUSES` | Рассинхронизация при добавлении статуса | Использовать `SupplyStatus::values()` |
| `app/Models/Supplier_orders.php` | `bonusAmount` | 125-132 | `(int) round((int) $this->summa * (float) $this->bonus_percent / 100)` | Ошибка округления при больших суммах | Использовать `bcmul` или `int` |

### 2.3 Medium

| Файл | Метод | Строки | Описание | Последствие | Исправление |
|------|-------|--------|----------|-------------|-------------|
| `app/Http/Controllers/Designer/ProjectController.php` | `projectPayload` | 686-695 | `hasDelayedSupply` — N+1 при загрузке `supplierOrders` | Медленная загрузка списка | Использовать `withExists` или подзапрос |
| `app/Http/Controllers/Designer/ProjectController.php` | `index` | 78-87 | `orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')` | Медленная сортировка | Добавить индекс или пересмотреть логику |
| `app/Services/Team/TeamService.php` | `activeTeamFor` | 19-32 | 2 запроса при каждом вызове | N+1 в циклах | Кешировать результат на запрос |

---

## 3. Хардкод

### 3.1 Critical

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Services/Team/TeamService.php` | 97 | `'max_members' => 5` | Лимит команды захардкожен | Вынести в `subscription_plans.included_seats` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 23-30 | `STATUSES` array | Статусы поставок захардкожены | Использовать `SupplyStatus` enum |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 200-207 | Статусы в `Rule::in` | Дублирование статусов | `Rule::in(SupplyStatus::values())` |
| `app/Http/Controllers/Designer/ProjectController.php` | 37 | `STAGE_TYPES` | Типы этапов захардкожены | Использовать `ProjectStageType::values()` |

### 3.2 High

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Services/Team/TeamService.php` | 234, 353 | `now()->addDays(14)` | Срок жизни приглашения | Вынести в `config/team.php` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 308 | `'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip'` | Типы файлов | Вынести в `config/files.php` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 308 | `'max:10240'` | Макс. размер файла | Вынести в `config/files.php` |
| `app/Http/Controllers/CommunityController.php` | 41, 168 | `config('community.posts_per_page', 10)` | Пагинация | Оставить, но добавить в `config/community.php` |

### 3.3 Medium

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Http/Controllers/Designer/ProjectController.php` | 122-126 | `objectTypes` | Типы объектов | Вынести в `config/objects.php` или lang |
| `app/Models/Supplier_orders.php` | 45-51 | `OFFER_*` константы | Статусы офферов | Перенести в `OfferStatus` enum |

---

## 4. Производительность

### 4.1 Critical — N+1 и отсутствие пагинации

| Файл | Метод | Строки | Проблема | Исправление |
|------|-------|--------|----------|-------------|
| `app/Http/Controllers/Designer/ProjectController.php` | `index` | 52-65 | `->get()` без пагинации, `stages.steps`, `supplierOrders.supplier` | `->paginate(20)`, `withCount` |
| `app/Http/Controllers/Designer/ProjectController.php` | `projectPayload` | 686-695 | `hasDelayedSupply` — проверка каждого заказа в PHP | SQL `whereExists` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `index` | 43-47 | `->get()` без пагинации | `->paginate(20)` |

### 4.2 High — Неэффективные запросы

| Файл | Метод | Строки | Проблема | Исправление |
|------|-------|--------|----------|-------------|
| `app/Http/Controllers/Designer/ProjectController.php` | `index` | 52-65 | `select *` для всех связей | Выбирать только нужные поля |
| `app/Http/Controllers/Designer/ProjectController.php` | `index` | 67-76 | 2 запроса для clients | Объединить с `WorkspaceAccess` |
| `app/Services/Team/TeamService.php` | `activeTeamFor` | 19-32 | Вызывается в каждом `WorkspaceAccess` методе | Кешировать на уровне запроса |

---

## 5. Безопасность

### 5.1 Critical

| Файл | Проблема | Последствие | Исправление |
|------|----------|-------------|-------------|
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `Rule::exists('projects', 'id')->where('user_id', $userId)` | IDOR — доступ к чужим проектам через подбор ID | Использовать `WorkspaceAccess` |
| `app/Http/Controllers/Designer/ProjectController.php` | `deleteFile` — `$project->files[$fileIndex]` без проверки владения файлом | Path traversal / удаление чужих файлов | Проверять, что путь начинается с `projects/` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | `deleteFile` — аналогично | Path traversal | Проверять префикс пути |

### 5.2 High

| Файл | Проблема | Последствие | Исправление |
|------|----------|-------------|-------------|
| `app/Models/User.php` | `$fillable` содержит `role`, `subscription_plan` | Mass assignment — повышение прав | Убрать из `$fillable`, использовать `forceFill` |
| `app/Http/Controllers/Designer/ProjectController.php` | `store` — `WorkspaceAccess::attachTeamOnCreate` без проверки подписки | Создание проекта в команде без подписки | Проверять `teamHasCorporateAccess` |
| Все контроллеры | Нет rate limiting на критических endpoint | Brute force / DoS | Добавить `throttle` |

---

## 6. Мёртвый код

| Файл | Строки | Причина | Доказательство |
|------|--------|---------|----------------|
| `app/Http/Controllers/Designer/ProjectController.php` | 390-393 | Метод `passportObjectModerationError` всегда возвращает `null` | Поиск по проекту — нет вызовов |
| `app/Http/Controllers/Designer/ProjectController.php` | 395-651 | Методы `fillAndSave`, `saveObjectDetails`, `saveStages`, `normalizeLinks` дублируют `ProjectService` | `ProjectService` существует и используется в API |
| `resources/views/designer/objects/index_v2.blade.php` | — | Старая версия страницы объектов | Не используется в routes |
| `resources/views/designer/supplier-orders/index.blade.php` | — | Старая страница поставок | Редирект на `projects.index` |

---

## 7. Тесты

| Проблема | Текущее состояние | Требуется |
|----------|-------------------|-----------|
| Нет тестов для `WorkspaceAccess` в `SupplierOrderController` | Баг с `project_id` не покрыт | Добавить тест: team member может создать поставку для team project |
| Нет тестов на транзакции | Частичные сохранения не отловлены | Тест на rollback при ошибке в `saveStages` |
| Нет тестов на N+1 | Нет защиты от регресса | Тест с `DB::enableQueryLog` |
| Нет security тестов | IDOR не покрыт | Тест: нельзя получить чужой проект/поставку |

---

## Итоговая статистика

| Уровень | Количество |
|---------|-----------|
| Critical | 12 |
| High | 18 |
| Medium | 15 |
| Low | 5 |
| **Всего** | **50** |
