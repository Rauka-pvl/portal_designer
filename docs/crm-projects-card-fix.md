# CRM Projects Card Fix — Report

## Изменённые файлы / Changed files

- `resources/views/designer/projects/crm.blade.php` — центрированная модалка, layout вкладок, форма без объекта
- `resources/css/app.css` — `.crm-modal-*`, уровни поверхностей dark theme, компактный kanban
- `app/Http/Controllers/Designer/ProjectController.php` — сохранение без `object_id`, client + property fields
- `app/Http/Requests/Designer/ProjectSaveRequest.php` — Form Request
- `app/Models/Project.php` — `client_id`, `propertySnapshot()`
- `database/migrations/2026_07_29_150000_decouple_projects_from_passport_objects.php`
- `tests/Feature/CrmRedesignTest.php`
- `lang/ru/projects.php`, `lang/ru/objects.php`

## Миграции

`2026_07_29_150000_decouple_projects_from_passport_objects`:

1. Добавляет `projects.client_id` (nullable FK)
2. Делает `projects.object_id` nullable
3. Backfill `client_id` из passport
4. Досинхронизация `project_object_details`

`passport_objects` **не удалена**.

## Удалённые UI-зависимости от объекта

- Поле «Выберите объект»
- Обязательный `object_id` при создании
- Модалка «Новый объект» из потока создания проекта
- Dual-column layout на вкладках Поставки / Чек-листы
- Sidebar/drawer-позиционирование карточки

## Перенос данных

Старые проекты с `object_id` сохраняют связь; данные копируются/читаются через `project_object_details` + `propertySnapshot()` (fallback на passport).

## Тесты

`CrmRedesignTest` — 14 passed, включая:

- создание проекта без passport object
- город/тип/адрес/этаж/подъезд/квартира/площадь
- бюджеты и расчёт м² / null при пустой площади
- legacy sync
- activity feed, pipeline, redirects

## Команды

```bash
php artisan migrate
php artisan crm:verify-migration
npm run build
php artisan test --filter=CrmRedesignTest
```

## Проверка перед production

1. Backup БД
2. `migrate` + `crm:verify-migration`
3. Smoke: создать проект без объекта → канбан → вкладки Общие/Поставки/Чек-листы
4. Проверить центрирование модалки поверх sidebar (desktop + mobile)
5. Light/dark theme
