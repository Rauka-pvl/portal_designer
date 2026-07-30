# Карта существующего процесса поставок (as-is)

Документ фиксирует рабочую backend-логику до подключения нового UI в карточке проекта.
Бизнес-поведение не меняется — новый frontend переиспользует эти endpoints и модели.

## Модель и таблица

- Модель: `App\Models\Supplier_orders` → таблица `supplier_orders`
- Связи: `project()`, `supplier()`, `designer()` (`user_id`)
- **Нет колонки `client_id`** — клиент берётся из проекта (`projects.client_id`)
- Товары: JSON `product_items` (не отдельная таблица позиций)
- Чек-листы: JSON `included_step_ids` (ID шагов `project_stages_steps`)
- Процент: `bonus_percent` + `offer_status` + `offer_history` + `offer_message`

## Статусы поставки (`SupplyStatus` / controller)

`draft` → `order_created` → `order_confirmed` → `advance_payment` → `full_payment` → `delivery_completed`

Воронка (канбан) без `draft`. Drag в воронку только если `isInFunnel()`:
`is_sent_to_supplier && effectiveOfferStatus === accepted`
(legacy: отправленные без `offer_status` считаются accepted).

## Статусы оффера

`pending_supplier` | `pending_designer` | `accepted` | `rejected`

## Endpoints (designer, web)

| Method | Route name | Назначение |
|--------|------------|------------|
| GET | `supplier-orders.index` | **Redirect** в `projects.index` (старый UI недоступен) |
| POST | `supplier-orders.store` | Создание (draft / send) |
| GET | `supplier-orders.show` | Карточка / JSON payload |
| PUT | `supplier-orders.update` | Обновление / detail_update |
| PATCH | `supplier-orders.update_status` | Смена статуса воронки |
| POST | `supplier-orders.offer.accept` | Принять % |
| POST | `supplier-orders.offer.reject` | Отклонить % |
| POST | `supplier-orders.offer.counter` | Встречный % |
| DELETE | `supplier-orders.destroy` | Удаление |
| GET | `suppliers.products.index` | HTML-каталог товаров |
| GET | `suppliers.products.json` | JSON-каталог (для модального UI) |

## Поля store/update (FormData)

Обязательные: `project_id`, `supplier_id`, `summa`, `date_planned`  
Ключевые: `send_to_supplier` (0/1) или `action=save|send`, `bonus_percent`, `category`, `mark`, `room`, даты/суммы аванса и доплаты, `product_items` (JSON-строка `[{id,qty}]`), `included_step_ids[]`, `links[]`, `files[]`, `comment`, `intent=update` для детального редактирования.

## Save vs Send

- **Save** (`send_to_supplier=0`): `status=draft`, без уведомления поставщику
- **Send** (`send_to_supplier=1`): `is_sent_to_supplier=true`, `offer_status=pending_supplier`, `status=order_created`, `OrderOfferNotifier`

## Уведомления / автоматизации

- `App\Support\OrderOfferNotifier` → `UserNotification` (`order_offer`, `supplier_order`, `supplier_order_updated`)
- При `delivery_completed` + accepted: `Review::requestReviewsForCompletedOrder`, `CashbackAccrual::forCompletedOrder`
- Policies / Events / Jobs для поставок **не используются**

## Действия дизайнера

Создать (draft/send), редактировать, удалить, ответить на оффер (accept/reject/counter), двигать статус после accept, чат.

## Действия поставщика (кабинет)

`supplier.orders.*` — принять / отклонить / встречный %, комментарий/чат, смена статуса после accept.

## Каталог и корзина (legacy)

- Товары: `supplier_products`
- Корзина: `localStorage` `catalog_cart_{supplierId}`
- Checkout раньше вёл на `/supplier-orders?open_order=1` (сейчас redirect)

## Чек-листы

`GET /projects/{id}` JSON → шаги с непустым `result_comment` → чекбоксы → `included_step_ids[]`  
Валидация: все ID принадлежат проекту.

## Новый UI (цель подключения)

Проект → вкладка «Поставки» → модалка создания/карточки → те же `store` / `update` / `offer.*` / `update_status`.
