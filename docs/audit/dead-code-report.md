# Отчёт по мёртвому коду

**Дата:** 2026-08-03  
**Версия:** 2.0 (после удалений)

---

## Удалённые файлы

| Файл | Причина удаления | Доказательство | Проверка после удаления |
|---|---|---|---|
| `resources/views/designer/objects/index.blade.php` | Заменён на `index_v2` | `PassportObject` рендерит только `index_v2` | `php artisan test` — 135 passed |
| `resources/views/designer/supplier-orders/index.blade.php` | Route — redirect на projects; view не вызывался | `routes/web.php` closure redirect | tests passed |
| `resources/views/designer/tasks/partials/calendar-legacy.blade.php` | Нигде не include | Grep `calendar-legacy` только в docs | tests passed |
| `resources/views/designer/checklist-steps/show.blade.php` | Controller redirect, view не возвращается | `ChecklistStepController::show` → redirect | tests passed |
| `app/Models/ProjectStagesStep.php` | Пустой stub; используется `ProjectStageStep` | Нет references | tests passed |

## Удалённый код (методы)

| Файл | Метод | Причина | Проверка |
|------|-------|---------|----------|
| `ProjectController` | `passportObjectModerationError`, `fillAndSave`, `normalizeLinks`, `saveObjectDetails`, `saveStages` | Dead / duplicate of `ProjectService` | tests passed |
| `SupplierOrderController` | `index()` | Unrouted; view deleted | tests passed |

## Кандидаты на ручную проверку (не удалены)

| Файл | Причина сомнения |
|------|------------------|
| `app/Http/Controllers/Api/DesignerDataController.php` | Часть методов live (`objects`, `supplierOrders`), часть unrouted |
| `app/Http/Controllers/Api/DesignerCrudController.php` | Аналогично — object CRUD ещё wired |
| `app/Http/Controllers/Designer/PassportObject.php` | Legacy objects UI still used via index_v2 |
| Unrouted methods in DesignerData/DesignerCrud | Нужна точечная вырезка методов, не файла |

## Debug-код

- `dd` / `dump` / `var_dump` в `app/` и `resources/` — **не найдено**
