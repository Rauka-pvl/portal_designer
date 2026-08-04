# Отчёт по хардкоду

**Дата:** 2026-08-03

---

## Critical

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Services/Team/TeamService.php` | 97 | `'max_members' => 5` | Лимит команды захардкожен | `subscription_plans.included_seats` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 23-30 | `STATUSES` array | Статусы поставок | `SupplyStatus` enum |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 200-207 | Статусы в `Rule::in` | Дублирование | `SupplyStatus::values()` |
| `app/Http/Controllers/Designer/ProjectController.php` | 37 | `STAGE_TYPES` | Типы этапов | `ProjectStageType::values()` |

## High

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Services/Team/TeamService.php` | 234, 353 | `now()->addDays(14)` | Срок жизни приглашения | `config/team.invite_ttl_days` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 308 | `'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,zip'` | Типы файлов | `config/files.allowed_mimes` |
| `app/Http/Controllers/Designer/SupplierOrderController.php` | 308 | `'max:10240'` | Макс. размер файла | `config/files.max_size_kb` |
| `app/Http/Controllers/CommunityController.php` | 41, 168 | `config('community.posts_per_page', 10)` | Пагинация | `config/community.posts_per_page` |

## Medium

| Файл | Строки | Значение | Проблема | Решение |
|------|--------|----------|----------|---------|
| `app/Http/Controllers/Designer/ProjectController.php` | 122-126 | `objectTypes` | Типы объектов | `config/objects.php` |
| `app/Models/Supplier_orders.php` | 45-51 | `OFFER_*` константы | Статусы офферов | `OfferStatus` enum |
| `app/Http/Controllers/Designer/ProjectController.php` | 85 | `orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')` | Сортировка | Пересмотреть логику |

## Обоснованный хардкод (оставлен)

| Файл | Значение | Обоснование |
|------|----------|-------------|
| `app/Enums/TeamRole.php` | `owner`, `admin`, `designer` | Стабильные технические ключи enum |
| `app/Enums/SupplyStatus.php` | `order_created`, `delivery_completed` | Стабильные технические ключи enum |
| `app/Enums/ProjectStatus.php` | `new`, `in_progress`, `completed` | Стабильные технические ключи enum |
| HTTP status codes | `200`, `201`, `204`, `403`, `404`, `422` | Стандартные HTTP-коды |
