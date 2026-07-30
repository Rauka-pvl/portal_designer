# Отчёт: поставки внутри карточки проекта

## 1. Переиспользованные routes / handlers

| Endpoint | Использование |
|----------|----------------|
| `POST /supplier-orders` (`store`) | Создание draft / send |
| `PUT /supplier-orders/{id}` (`update`) | Редактирование |
| `GET /supplier-orders/{id}` JSON | Карточка поставки |
| `PATCH /supplier-orders/{id}/status` | Drag в воронке |
| `POST .../offer/accept\|reject\|counter` | Согласование % |
| `GET /suppliers/{id}/products.json` | **новый thin JSON** поверх `supplier_products` |
| `GET /projects/{id}` JSON | Шаги чек-листов с `result_comment` |

Backend `fillAndSave` / офферы / уведомления / cashback **не переписывались**.

## 2. Новые frontend-компоненты

- `resources/views/designer/projects/partials/supply-modals.blade.php` — create / catalog / detail / unsaved
- `resources/views/designer/projects/partials/supply-scripts.blade.php` — `window.CrmSupplies`
- Вкладка «Поставки» в `crm.blade.php` — toolbar, канбан, список
- CSS `.crm-supply-*` в supply-scripts

## 3. Старые страницы больше не как основной UX

- `supplier-orders.index` → redirect в Projects CRM
- `supplier-orders.show` (HTML) → redirect `?open={project}&tab=supplies&supply={id}`
- Legacy Blade `designer/supplier-orders/index|show` остаются в репозитории, но не являются точкой входа UI

## 4. Автоопределение проекта и клиента

- `project_id` — hidden из открытого проекта
- Клиент — readonly из `project.client_name` (колонки `client_id` на заказе нет)
- Backend по-прежнему валидирует `project_id` owned by designer

## 5. Вложенный каталог

Модалка поверх формы → `GET /suppliers/{id}/products.json?q&category&page` → корзина → «Добавить выбранные» → `product_items` + пересчёт `summa`. Форма не закрывается.

## 6. Состояние формы

Dirty-флаг + unsaved confirm. Каталог не считается закрытием формы.

## 7. Чек-листы

Шаги с непустым `result_comment` → чекбоксы → `included_step_ids[]` (та же валидация на backend).

## 8. Согласование процента

Деталь поставки показывает `offerPayload` и вызывает существующие `offer.accept|reject|counter`.

## 9. Кабинет поставщика

Не менялся. Получает те же записи после `send_to_supplier=1`.

## 10. Тесты

`tests/Feature/ProjectSuppliesCrmTest.php` — 7 кейсов (модалка в CRM, draft, foreign project, send, products.json, redirect show, summa payload).

## 11. Команды

```bash
php artisan test --filter="ProjectSuppliesCrmTest|CrmRedesignTest"
# при необходимости CSS:
npm run build
```

Hard refresh `/projects` → открыть проект → вкладка «Поставки».

## 12. Риски

- Каталог HTML (`/suppliers/{id}/products`) ещё существует для старых закладок — UX создания теперь только из проекта
- `category`/`mark`/`room` в БД NOT NULL — сохраняем `''` вместо `null` (совместимо со схемой)
- Чат поставок в detail-модалке пока не встроен (endpoints чата живы)
- Полный browser E2E каталога/корзины не покрыт — есть API/feature тесты
