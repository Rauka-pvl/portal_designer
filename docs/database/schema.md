# Database Schema

**Date:** 2026-08-03  
**Status:** Clean migration set (27 migrations), `migrate:fresh --seed` verified on `APP_ENV=local`

---

## Core account tables

### `users`
Account-only fields (auth + global type).

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| phone | string nullable | |
| password | string | |
| account_type | enum(designer, supplier, system_admin) | Global type (not team role) |
| account_status | enum(active, blocked, deleted) | |
| must_change_password | boolean | |
| password_changed_at | timestamp nullable | |
| email_verified_at | timestamp nullable | |
| remember_token | string | |
| timestamps / soft deletes | | |

### `designer_profiles`
1:1 designer profile (`user_id` unique FK cascade).

### `suppliers`
Supplier company profile / CRM card. `user_id` links supplier account when present; `created_by_user_id` for designer-created cards.

---

## Subscriptions (normalized)

### `subscription_plans`
| key | price | seats |
|-----|------:|------:|
| personal | 0 | 1 |
| standard | 5000 | 1 |
| pro | 9990 | 1 |
| corporate | 29990 | 5 |

Corporate is identified by **key = `corporate`**, never by price.

### `subscriptions`
`user_id` + `plan_id` + status/dates. Legacy `User::$subscription_*` accessors sync here.

### `subscription_payments` / `designer_subscription_payments`
Payment history. Cascade on users is **restrict** for financial rows where applicable.

---

## CRM

- `clients` — soft deletes, status, client_type
- `projects` — `decimal(12,2)` for planned/actual cost; team_id FK
- `project_object_details` — decimal budgets
- `project_stages` / `project_stages_steps`
- `templates`
- `passport_objects`
- `designer_tasks`
- `supplier_orders` — soft deletes, offer_* fields, money as integer (tiyin/kopecks)
- `crm_pipelines` / activity tables

---

## Teams

- `designer_teams` — owner_user_id restrict
- `designer_team_members` — role enum(owner, admin, designer), unique(team_id, user_id)
- `team_invitations`

Team role ≠ `users.account_type`.

---

## Community / messaging / notifications

- `community_posts` (+ media, comments, likes, hides, saves)
- `supplier_order_messages`
- `user_notifications`

---

## Indexes (selected)

- users: account_type, account_status, phone
- projects: (user_id, team_id), client_id, status
- supplier_orders: (user_id, status), project_id, supplier_id
- designer_team_members: (team_id, status), (user_id, status)
- subscriptions: (user_id, status), (status, expires_at)
- community_posts: (status, created_at)

---

## Money types

| Entity | Fields | Type |
|--------|--------|------|
| projects | planned_cost, actual_cost | decimal(12,2) |
| project_object_details | repair_budget_* | decimal(12,2) |
| subscription_plans | price | decimal(10,2) |
| supplier_orders | summa, *_amount | integer (minor units) |
| designer_profiles | price_per_m2 | decimal(10,2) |
