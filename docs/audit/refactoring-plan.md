# План исправлений по приоритетам

**Дата:** 2026-08-03

---

## Этап 1: Critical Security (1-2 часа)

1. **Исправить IDOR в `SupplierOrderController`**
   - Заменить `Rule::exists('projects', 'id')->where('user_id', $userId)` на кастомное правило с `WorkspaceAccess`
   - Файл: `app/Http/Controllers/Designer/SupplierOrderController.php:288`

2. **Исправить path traversal в `deleteFile`**
   - `ProjectController::deleteFile` — проверять префикс пути
   - `SupplierOrderController::deleteFile` — проверять префикс пути

3. **Убрать mass assignment в `User`**
   - Убрать `role`, `subscription_plan` из `$fillable`

## Этап 2: Авторизация и tenant isolation (2-3 часа)

4. **Создать Policies**
   - `ProjectPolicy`, `SupplierOrderPolicy`, `ClientPolicy`
   - Заменить inline `where('user_id', ...)` на `authorize`

5. **Проверить subscription middleware**
   - Убедиться, что `subscription.active` покрывает все критические routes

## Этап 3: Баги бизнес-логики (2-3 часа)

6. **Исправить дублирование `ProjectController` и `ProjectService`**
   - Удалить приватные методы из `ProjectController`
   - Использовать `ProjectService` в `store`/`update`

7. **Добавить транзакции**
   - `ProjectController::store/update` — `DB::transaction`
   - `SupplierOrderController::store/update` — `DB::transaction`

8. **Исправить float для денег**
   - `ProjectController`, `ProjectService` — использовать `decimal` или `int`

## Этап 4: Нормализация базы (4-6 часов)

9. **Создать чистые миграции**
   - `users` — только auth
   - `designer_profiles` — профиль дизайнера
   - `subscriptions`, `subscription_plans`, `subscription_payments`
   - `teams`, `team_members` (переименовать из `designer_teams`)

10. **Добавить foreign keys и индексы**
    - Все FK из database-audit.md
    - Все индексы из performance-audit.md

11. **Исправить типы данных**
    - `float` → `decimal(12,2)` для денег
    - `string` → `enum` для статусов

## Этап 5: Чистая структура миграций (2-3 часа)

12. **Удалить старые миграции**
    - Объединить 66 миграций в ~20 чистых

13. **Создать seeders**
    - `AccountTypeSeeder`, `SubscriptionPlanSeeder`, `ProjectStageSeeder`

## Этап 6: Архитектурный рефакторинг (3-4 часа)

14. **Вынести бизнес-логику из контроллеров**
    - `SupplierOrderController::fillAndSave` → `SupplyService`
    - `CommunityController` → `CommunityPostService`

15. **Создать Form Requests**
    - `SupplierOrderSaveRequest`

16. **Создать API Resources**
    - `SupplierOrderResource`

## Этап 7: Удаление мёртвого кода (1 час)

17. **Удалить подтверждённый мёртвый код**
    - `passportObjectModerationError`
    - `ProjectController::fillAndSave`, `saveObjectDetails`, `saveStages`, `normalizeLinks`
    - `index_v2.blade.php`
    - `supplier-orders/index.blade.php`

## Этап 8: Производительность запросов (2-3 часа)

18. **Добавить пагинацию**
    - `ProjectController::index`
    - `SupplierOrderController::index`

19. **Исправить N+1**
    - `hasDelayedSupply` → SQL
    - `chatUnreadMapForDesigner` → один запрос

20. **Добавить индексы**
    - Все индексы из performance-audit.md

## Этап 9: Frontend (1-2 часа)

21. **Оптимизировать Blade**
    - Разбить большие файлы на компоненты

## Этап 10: Форматирование и статический анализ (1 час)

22. **Laravel Pint**
23. **PHPStan/Larastan**

## Этап 11: Тесты и повторный аудит (2-3 часа)

24. **Добавить тесты**
    - Security: IDOR, mass assignment, path traversal
    - Perf: N+1, пагинация
    - Бизнес-логика: транзакции, дублирование

25. **Повторный аудит**
    - Сравнить до/после

---

## Общее время: 20-30 часов

## Риски

| Риск | Митигация |
|------|-----------|
| Сломать существующий функционал | Запускать тесты после каждого этапа |
| Потерять данные при миграциях | Только локально, `migrate:fresh` |
| Несовместимость с API | Покрыть API тестами перед изменениями |
