# ER Diagram

**Date:** 2026-08-03

```mermaid
erDiagram
    users ||--o| designer_profiles : has
    users ||--o| suppliers : "supplier account"
    users ||--o{ subscriptions : has
    users ||--o{ clients : owns
    users ||--o{ projects : owns
    users ||--o{ designer_tasks : creates
    users ||--o{ community_posts : authors

    subscription_plans ||--o{ subscriptions : defines
    subscriptions ||--o{ subscription_payments : has

    designer_teams ||--o{ designer_team_members : has
    users ||--o{ designer_team_members : joins
    designer_teams ||--o{ team_invitations : invites
    designer_teams ||--o{ projects : "team context"

    clients ||--o{ projects : "client_id"
    projects ||--o| project_object_details : details
    projects ||--o{ project_stages : stages
    project_stages ||--o{ project_stages_steps : steps
    projects ||--o{ supplier_orders : supplies
    projects ||--o{ designer_tasks : tasks

    suppliers ||--o{ supplier_orders : fulfills
    suppliers ||--o{ supplier_products : catalogs
    supplier_orders ||--o{ supplier_order_messages : chat

    community_posts ||--o{ community_post_comments : has
    community_posts ||--o{ community_post_likes : has
    community_posts ||--o{ community_post_saves : has

    users {
        bigint id PK
        string account_type
        string account_status
        string email
    }

    designer_profiles {
        bigint id PK
        bigint user_id UK
    }

    subscription_plans {
        bigint id PK
        string key UK
        decimal price
        int included_seats
    }

    subscriptions {
        bigint id PK
        bigint user_id FK
        bigint plan_id FK
        string status
    }

    designer_teams {
        bigint id PK
        bigint owner_user_id FK
    }

    designer_team_members {
        bigint id PK
        bigint team_id FK
        bigint user_id FK
        string role
    }

    projects {
        bigint id PK
        bigint user_id FK
        bigint client_id FK
        bigint team_id FK
        decimal planned_cost
        decimal actual_cost
    }

    supplier_orders {
        bigint id PK
        bigint project_id FK
        bigint supplier_id FK
        int summa
        string offer_status
    }
```

## Account type vs team role

```
users.account_type     → designer | supplier | system_admin
designer_team_members.role → owner | admin | designer
```

Corporate administrator = team role `admin` with global type `designer`.
