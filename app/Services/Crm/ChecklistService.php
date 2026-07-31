<?php

namespace App\Services\Crm;

use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Template;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChecklistService
{
    public function list(Project $project): Collection
    {
        return $project->stages()->with('steps')->orderBy('order')->get();
    }

    public function create(Project $project, int $userId, array $data): ProjectStages
    {
        return DB::transaction(function () use ($project, $userId, $data) {
            $stage = ProjectStages::create([
                'project_id' => $project->id,
                'stage_type' => $data['stage_type'],
                'name' => $data['name'] ?? null,
                'template_id' => $this->allowedTemplateId($data['template_id'] ?? null, $userId),
                'deadline' => $data['deadline'] ?? null,
                'responsible_id' => $data['responsible_id'] ?? null,
                'assign_task' => (bool) ($data['assign_task'] ?? false),
                'created_by' => $userId,
                'order' => (int) ($data['order'] ?? ((int) $project->stages()->max('order') + 1)),
            ]);

            foreach ((array) ($data['items'] ?? $data['steps'] ?? []) as $index => $step) {
                $payload = is_array($step) ? $step : ['title' => $step];
                $payload['order'] = $payload['order'] ?? $payload['position'] ?? $index;
                $this->createItem($stage, $payload);
            }

            return $stage->load('steps');
        });
    }

    public function update(ProjectStages $stage, int $userId, array $data): ProjectStages
    {
        $attrs = array_intersect_key($data, array_flip([
            'stage_type', 'name', 'deadline', 'responsible_id', 'assign_task', 'order',
        ]));
        if (array_key_exists('template_id', $data)) {
            $attrs['template_id'] = $this->allowedTemplateId($data['template_id'], $userId);
        }
        $stage->fill($attrs)->save();

        return $stage->fresh('steps');
    }

    public function delete(ProjectStages $stage): void
    {
        $stage->steps()->delete();
        $stage->delete();
    }

    public function createItem(ProjectStages $stage, array $data): ProjectStageStep
    {
        return $stage->steps()->create([
            'title' => trim((string) $data['title']),
            'deadline' => $data['deadline'] ?? null,
            'responsible_id' => $data['responsible_id'] ?? null,
            'link' => isset($data['link']) && trim((string) $data['link']) !== '' ? trim((string) $data['link']) : null,
            'result_status' => $data['result_status'] ?? 'pending',
            'result_comment' => $data['result_comment'] ?? null,
            'order' => (int) ($data['order'] ?? ((int) $stage->steps()->max('order') + 1)),
        ]);
    }

    public function updateItem(ProjectStageStep $item, array $data): ProjectStageStep
    {
        $item->fill(array_intersect_key($data, array_flip([
            'title', 'deadline', 'responsible_id', 'link', 'result_status', 'result_comment', 'order',
        ])))->save();

        return $item->fresh();
    }

    public function completeItem(ProjectStageStep $item, bool $done): ProjectStageStep
    {
        $item->result_status = $done ? 'done' : 'pending';
        $item->save();

        return $item->fresh();
    }

    public function setItemResult(ProjectStageStep $item, ?string $comment): ProjectStageStep
    {
        $item->result_comment = $comment !== null && trim($comment) !== '' ? trim($comment) : null;
        $item->save();

        return $item->fresh();
    }

    public function reorder(ProjectStages $stage, array $ids): Collection
    {
        $items = $stage->steps()->whereIn('id', $ids)->get()->keyBy('id');
        if ($items->count() !== count(array_unique($ids))) {
            abort(422, 'Checklist items do not belong to this checklist.');
        }
        DB::transaction(function () use ($ids, $items) {
            foreach (array_values($ids) as $order => $id) {
                $items[$id]->update(['order' => $order]);
            }
        });

        return $stage->steps()->orderBy('order')->get();
    }

    public function createTemplate(int $userId, array $data): Template
    {
        return Template::create([
            'user_id' => $userId,
            'name' => trim((string) $data['name']),
            'type' => $data['type'],
            'steps' => array_values(array_filter(array_map(
                fn ($step) => trim((string) (is_array($step) ? ($step['title'] ?? '') : $step)),
                $data['steps']
            ))),
        ]);
    }

    public function updateTemplate(Template $template, array $data): Template
    {
        $template->fill(array_intersect_key($data, array_flip(['name', 'type'])))->save();
        if (array_key_exists('steps', $data)) {
            $template->steps = array_values(array_filter(array_map(fn ($step) => trim((string) $step), $data['steps'])));
            $template->save();
        }

        return $template->fresh();
    }

    public function results(Project $project): Collection
    {
        return ProjectStageStep::query()
            ->whereHas('stage', fn ($q) => $q->where('project_id', $project->id))
            ->with('stage:id,project_id,name,stage_type,deadline,responsible_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resultsGrouped(Project $project): array
    {
        $stages = ProjectStages::query()
            ->where('project_id', $project->id)
            ->with(['responsible:id,name', 'steps' => fn ($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        return $stages->map(function (ProjectStages $stage) {
            return [
                'checklist' => [
                    'id' => (int) $stage->id,
                    'name' => trim((string) $stage->name) !== '' ? $stage->name : (string) $stage->stage_type,
                    'stage_id' => null,
                    'stage_type' => $stage->stage_type,
                    'responsible' => $stage->responsible_id ? [
                        'id' => (int) $stage->responsible_id,
                        'name' => $stage->responsible?->name,
                    ] : null,
                    'deadline' => $stage->deadline
                        ? \Carbon\Carbon::parse($stage->deadline)->toIso8601String()
                        : null,
                ],
                'items' => $stage->steps->map(fn (ProjectStageStep $step) => [
                    'id' => (int) $step->id,
                    'title' => (string) $step->title,
                    'result' => $step->result_comment,
                    'has_result' => filled($step->result_comment),
                    'completed' => (string) ($step->result_status ?? 'pending') === 'done',
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function allowedTemplateId(mixed $templateId, int $userId): ?int
    {
        if (! $templateId) {
            return null;
        }

        return Template::query()
            ->whereKey((int) $templateId)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
            ->value('id');
    }
}
