# Corporate Subscription — Architecture Plan

## Current architecture (audit)

### Stack
- Laravel 12 / PHP 8.2
- Custom designer billing (no Cashier): `App\Support\DesignerSubscription`
- Plans in code: `standard` (5 000 ₸), `pro` (9 990 ₸)
- Access: date fields on `users` (`subscription_ends_at`, `subscription_trial_ends_at`)
- Ledger: `designer_subscription_payments`
- Gate: middleware `subscription.active` → `EnsureDesignerSubscription`

### Tasks / checklists
- No standalone Task model. “Tasks” calendar = `project_stages_steps` + supplier order dates
- Assignees: `project_stages.responsible_id`, `project_stages_steps.responsible_id` → `users`
- Validation today forces responsible = auth user only
- Project ownership: `projects.user_id`

### Notifications
- Custom `user_notifications` via `UserNotification::create([...])`
- No assignment notifications yet

### Settings
- Tabs: Profile, Security active; Notifications / Roles / Team disabled stubs
- Subscriptions link points to separate `/subscription` page

### Team / roles
- `AccountPermissions` stub only (`role === designer` = owner)
- No teams / seats tables

---

## Proposed architecture

### Corporate plan
- Key: `corporate`
- Price: **29 990 ₸ / month**
- Seats: **5** (owner + up to 4 members)
- Inherits Pro feature keys + team features

### Tables
| Table | Purpose |
|-------|---------|
| `designer_teams` | One workspace per Corporate owner |
| `designer_team_members` | user ↔ team + role + status |
| `designer_team_invitations` | pending invites (reserve seats) |
| `projects.team_id` | nullable FK; personal projects stay null until Corporate activation |

### Roles (enum keys)
- `owner` — billing + full control
- `admin` — manage members (except owner/billing), all projects/tasks
- `designer` — all projects; tasks created by self OR assigned to self

### Access rules
1. Personal Standard/Pro: unchanged (`users.subscription_*`)
2. Corporate owner: paid Corporate on owner user + `designer_teams`
3. Members: access if team status active AND owner Corporate subscription active
4. Expired Corporate: middleware allows only `subscription.*`, logout, language; data preserved

### Personal → Corporate
- On Corporate checkout success: create team, add owner member, backfill `projects.team_id`
- Keep payment history; set `subscription_plan = corporate`
- Do not delete personal payment rows

### Leave team
- Member loses Corporate access
- Must buy personal plan again (no auto-restore of expired personal)

### Assignees
- Personal: responsible locked to self
- Corporate: TeamMemberSelect of active members; backend validates same `team_id`

### Notifications (reuse `UserNotification`)
- Task/checklist assigned or reassigned (skip self-assign)
- Team invite / role change / Corporate expired (later waves)

### Settings UI
- Remove Notifications + Subscriptions tabs from Settings chrome
- Enable Team + Roles and access (locked upsell on Standard/Pro)

### Risks & compatibility
- Calendar currently filters by `project.user_id` only — extend for Corporate visibility
- `ProjectSaveRequest` must relax responsible validation for Corporate
- Avoid migrate:fresh; all new FKs nullable first
- One active team per user (unique on active membership)

### Status (implementation)
- Corporate plan `corporate` / 29 990 ₸ / 5 seats — done
- Teams tables + `projects.team_id` + `project_stages.created_by` — done
- Settings Team / Roles tabs; Notifications & Subscriptions tabs removed — done
- Assignees via TeamService + ProjectSaveRequest; checklist notify — done
- Expiry lock via middleware `reason=corporate_expired` — done
- Verify: `php artisan corporate:verify-migration`
- Tests: `tests/Feature/CorporateSubscriptionTest.php`
