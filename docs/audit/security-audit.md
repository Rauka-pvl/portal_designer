# Аудит безопасности

**Дата:** 2026-08-03

---

## Critical

| Файл | Проблема | Последствие | Исправление | Тест |
|------|----------|-------------|-------------|------|
| `app/Http/Controllers/Designer/SupplierOrderController.php:288` | `Rule::exists('projects', 'id')->where('user_id', $userId)` — IDOR | Доступ к чужим проектам через подбор ID | `WorkspaceAccess::canAccessProject` | Team member может создать поставку для team project |
| `app/Http/Controllers/Designer/ProjectController.php:250-277` | `deleteFile` — `$project->files[$fileIndex]` без проверки пути | Path traversal / удаление чужих файлов | Проверять `str_starts_with($filePath, 'projects/')` | Нельзя удалить файл из другой директории |
| `app/Http/Controllers/Designer/SupplierOrderController.php:168-196` | `deleteFile` — аналогично | Path traversal | Проверять префикс `supplier-orders/` | Нельзя удалить чужой файл |
| `app/Models/User.php:25-55` | `$fillable` содержит `role`, `subscription_plan` | Mass assignment — повышение прав | Убрать из `$fillable`, использовать `forceFill` | Нельзя изменить role через update |
| `routes/api.php` designer business group | Нет `role:designer` — supplier мог ходить в CRM API | Privilege confusion | Добавлен `role:designer` + JSON 403 в RoleMiddleware | `SecurityAuditFixesTest` |
| `TeamService::changeRole` | Не проверял `member.team_id === team.id` | Cross-team IDOR смены роли | Проверка как в `removeMember` | `SecurityAuditFixesTest` |
| `SupplierDeposit::isDemo` | Fallback default `true` при отсутствии config | Демо-оплата депозита в prod | Default `false` | `SecurityAuditFixesTest` |

## High

| Файл | Проблема | Последствие | Исправление | Тест |
|------|----------|-------------|-------------|------|
| `app/Http/Controllers/Designer/ProjectController.php:172-201` | `store` — `WorkspaceAccess::attachTeamOnCreate` без проверки подписки | Создание проекта в команде без подписки | Проверять `teamHasCorporateAccess` | Пользователь без подписки не может создать team project |
| Все API контроллеры | Нет rate limiting на критических endpoint | Brute force / DoS | `throttle:60,1` на POST/PUT/DELETE | — |
| `app/Http/Controllers/CommunityController.php` | Нет проверки прав на редактирование/удаление постов | IDOR | Проверять `user_id` или `Policy` | Нельзя удалить чужой пост |
| `app/Http/Controllers/Designer/ClientController.php` | Вероятно, inline `where('user_id', ...)` | IDOR | `ClientPolicy` | Нельзя получить чужого клиента |

## Medium

| Файл | Проблема | Последствие | Исправление |
|------|----------|-------------|-------------|
| `app/Http/Controllers/Api/AuthController.php` | Нет проверки `APP_DEBUG` в production | Утечка stack trace | Проверить `.env` |
| `app/Http/Controllers/Supplier/DepositController.php` | Webhook без проверки подписи | Подделка платежей | Проверить `deposit.paid` middleware |
| `app/Http/Controllers/CommunityController.php` | XSS в комментариях (вероятно) | Injection | Экранировать вывод |

## Low

| Файл | Проблема | Исправление |
|------|----------|-------------|
| `config/cors.php` | Проверить настройки CORS | Ограничить origins |
| `config/scramble.php` | Swagger доступен в production | Отключить в production |

## Проверенные безопасные практики

| Практика | Статус |
|----------|--------|
| Пароли хешируются (`hashed` cast) | ✓ |
| CSRF токены в Blade | ✓ (предположительно) |
| Sanctum для API | ✓ |
| Rate limiting на login/register | ✓ (`throttle:login`, `throttle:register`) |
| Проверка `WorkspaceAccess` в `ProjectController` | ✓ (но не везде) |
