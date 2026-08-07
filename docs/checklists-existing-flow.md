# Checklists — existing flow (Design Portal)

## Domain model

| Model | Table | Role |
|-------|-------|------|
| `App\Models\ProjectStages` | `project_stages` | Checklist block for one project stage type |
| `App\Models\ProjectStageStep` | `project_stages_steps` | Checklist item + result |
| `App\Models\Template` | `templates` | Reusable step list (`steps` JSON array) |
| `App\Models\Project` | `projects` | `stages()` hasMany checklists |

**Not the same as CRM pipeline:** `Pipeline` / `PipelineStage` = project status kanban. Checklist `stage_type` = `measurement|planning|drawings|equipment|estimate|visualization`.

### Key fields

**Stage (`project_stages`):** `project_id`, `stage_type`, `template_id`, `deadline`, `responsible_id`, `assign_task`, `order`

**Step (`project_stages_steps`):** `title`, `deadline`, `responsible_id`, `link`, `result_status` (`pending`|`done`), `result_comment`, `order`

**Template:** `user_id` NULL = system; otherwise owner. SoftDeletes. Steps stored as JSON strings.

## Endpoints (reused)

| Method | Route | Name | Purpose |
|--------|-------|------|---------|
| PUT/POST | `/projects/{id}` | `projects.update` / `store` | Persist stages via `saveStages()` when `stages` present |
| GET | `/projects/{id}` | `projects.show` | JSON project payload incl. `stages` |
| POST | `/projects/templates` | `projects.templates.store` | Create user template |
| DELETE | `/projects/templates/{id}` | `projects.templates.destroy` | Delete **own** template only |
| GET | `/projects/templates` | `projects.templates.index` | List system + own templates |
| PUT | `/checklist-steps/{id}` | `checklist-steps.update` | Toggle done + save `result_comment` |
| GET | `/checklist-steps/{id}` | `checklist-steps.show` | Legacy step page |

Templates are also embedded on CRM page as `templatesData` / `stageTypes`.

## How a checklist relates to project & stage

1. User picks a **stage type** (not pipeline column).
2. Optionally picks a **template** of that type → steps copied client-side into the form.
3. On project save, `ProjectController::saveStages()` writes `project_stages` + `project_stages_steps`.
4. Changing checklist steps must **not** mutate the template (copy-on-create).

## Results → supplies

- Each step has its own `result_comment`.
- Supplier order field `included_step_ids` (JSON) references step IDs.
- CRM supply form renders project steps as checkboxes (`included_step_ids[]`).
- Preserving step IDs on checklist edit is required so supply links stay valid.

## System vs user templates

| | System | User |
|--|--------|------|
| `user_id` | `null` | owner |
| Visible | all designers | owner + system |
| Delete | forbidden (403) | soft-delete own |
| Payload | `is_shared`, `!is_owned` | `is_owned` |

## One checklist per stage

- Legacy UI: unique `stage_type` selection.
- DB: **no** unique constraint; existing duplicates must be displayed.
- New CRM create: warn if stage type already has a checklist; do not silently create a second.

## Auth checks already in place

- Project scoped by `user_id`.
- Template delete: owner only.
- Template use: system or owned.
- `responsible_id`: must be current user (`exists:users,id` where id = auth).
- Step update: via `stage.project.user_id`.

## Why CRM tab showed only empty stub

`crm.blade.php` `renderChecklists()` was **read-only** (`disabled` checkboxes). Create/edit lived only in legacy `projects/index.blade.php` (`?legacy=1`). No create modal was wired into the CRM card.
