<?php

namespace App\Services\Crm;

use App\Http\Requests\Designer\ProjectSaveRequest;
use App\Models\Project;
use App\Models\ProjectObjectDetail;
use App\Models\ProjectStageStep;
use App\Models\ProjectStages;
use App\Models\Template;
use App\Services\Team\AssignmentNotifier;
use App\Services\Team\TeamService;
use App\Support\PublicFileStorage;
use App\Support\WorkspaceAccess;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function findAccessible(User $user, int $id): Project
    {
        return WorkspaceAccess::scopeProjects(Project::query(), $user)->findOrFail($id);
    }

    public function fillAndSave(ProjectSaveRequest $request, Project $project): Project
    {
        $data = $request->validated();
        $files = array_values(array_filter(array_map('strval', (array) ($data['existing_files'] ?? []))));
        foreach ($request->file('files', []) as $file) {
            if ($file) {
                $files[] = PublicFileStorage::store($file, 'projects');
            }
        }
        $files = array_values(array_unique($files));
        foreach ((array) $project->files as $file) {
            if (is_string($file) && ! in_array($file, $files, true)) {
                Storage::disk('public')->delete($file);
            }
        }

        $property = $project->propertySnapshot();
        $project->fill([
            'client_id' => ! empty($data['client_id']) ? (int) $data['client_id'] : null,
            'name' => trim((string) $data['name']),
            'status' => (string) $data['status'],
            'start_date' => $data['start_date'] ?? $project->start_date ?? now()->toDateString(),
            'planned_end_date' => $data['planned_end_date'] ?? $project->planned_end_date ?? now()->addMonth()->toDateString(),
            'actual_end_date' => $data['actual_end_date'] ?? null,
            'planned_cost' => $data['repair_budget_planned'] ?? $property['repair_budget_planned'] ?? $project->planned_cost ?? 0,
            'actual_cost' => $data['repair_budget_actual'] ?? $property['repair_budget_actual'] ?? $project->actual_cost ?? 0,
            'links' => $this->normalizeLinks($data['links'] ?? []),
            'files' => $files,
            'comment' => $data['comment'] ?? null,
        ])->save();

        $this->saveObjectDetails($project, $data);
        $this->saveStages($request->user(), $project, $data);

        return $project;
    }

    public function destroy(Project $project): void
    {
        foreach ((array) $project->files as $file) {
            if (is_string($file) && $file !== '') {
                Storage::disk('public')->delete($file);
            }
        }
        $project->delete();
    }

    private function normalizeLinks(array $links): array
    {
        return collect($links)->map(function ($link) {
            if (is_string($link)) {
                return ['title' => '', 'url' => trim($link)];
            }
            return ['title' => trim((string) ($link['title'] ?? '')), 'url' => trim((string) ($link['url'] ?? ''))];
        })->filter(fn ($link) => $link['url'] !== '')->values()->all();
    }

    private function saveObjectDetails(Project $project, array $data): void
    {
        $old = $project->objectDetails;
        $value = fn (string $key, ?string $oldKey = null) => array_key_exists($key, $data)
            ? $data[$key] : $old?->{ $oldKey ?? $key };
        $area = $value('area');
        $plan = $value('repair_budget_planned');
        $actual = $value('repair_budget_actual');

        ProjectObjectDetail::query()->updateOrCreate(['project_id' => $project->id], [
            'passport_object_id' => $project->object_id,
            'client_id' => $project->client_id,
            'city' => $value('city'),
            'address' => $value('object_address', 'address'),
            'apartment' => $value('apartment'),
            'apartment_floor' => $value('apartment_floor'),
            'apartment_entrance' => $value('apartment_entrance'),
            'type' => $value('object_type', 'type'),
            'area' => $area === '' ? null : $area,
            'repair_budget_planned' => $plan === '' ? null : $plan,
            'repair_budget_actual' => $actual === '' ? null : $actual,
            'repair_budget_per_m2_planned' => is_numeric($area) && (float) $area > 0 && is_numeric($plan) ? round($plan / $area, 2) : null,
            'repair_budget_per_m2_actual' => is_numeric($area) && (float) $area > 0 && is_numeric($actual) ? round($actual / $area, 2) : null,
        ]);
    }

    private function saveStages(User $actor, Project $project, array $data): void
    {
        if (! array_key_exists('stages', $data)) return;
        $keep = [];
        foreach ((array) $data['stages'] as $order => $row) {
            if (empty($row['stage_type'])) continue;
            $stage = ! empty($row['id']) ? $project->stages()->find($row['id']) : null;
            $templateId = ! empty($row['template_id']) && Template::query()->whereKey($row['template_id'])
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $actor->id))->exists() ? (int) $row['template_id'] : null;
            $responsible = array_key_exists('responsible_id', $row) && $row['responsible_id'] !== null && $row['responsible_id'] !== ''
                ? app(TeamService::class)->assertAssigneeAllowed($actor, (int) $row['responsible_id']) : null;
            $attrs = ['stage_type' => $row['stage_type'], 'name' => $row['name'] ?? null, 'template_id' => $templateId,
                'deadline' => $row['deadline'] ?? null, 'responsible_id' => $responsible, 'assign_task' => ! empty($row['assign_task']), 'order' => $order];
            $previous = $stage?->responsible_id;
            $stage ??= new ProjectStages(['project_id' => $project->id, 'created_by' => $actor->id]);
            $stage->fill($attrs)->save();
            $keep[] = $stage->id;
            app(AssignmentNotifier::class)->notifyChecklistAssigned($actor, $previous, $responsible, $project, $stage);
            $stepKeep = [];
            foreach ((array) ($row['steps'] ?? []) as $stepOrder => $stepRow) {
                $stepRow = is_array($stepRow) ? $stepRow : ['title' => $stepRow];
                if (trim((string) ($stepRow['title'] ?? '')) === '') continue;
                $step = ! empty($stepRow['id']) ? $stage->steps()->find($stepRow['id']) : null;
                $stepResponsible = ! empty($stepRow['responsible_id'])
                    ? app(TeamService::class)->assertAssigneeAllowed($actor, (int) $stepRow['responsible_id'])
                    : null;
                $step ??= new ProjectStageStep(['project_stage_id' => $stage->id]);
                $step->fill(['title' => trim($stepRow['title']), 'deadline' => $stepRow['deadline'] ?? null,
                    'responsible_id' => $stepResponsible, 'link' => $stepRow['link'] ?? null,
                    'result_status' => $stepRow['result_status'] ?? 'pending', 'result_comment' => $stepRow['result_comment'] ?? null, 'order' => $stepOrder])->save();
                $stepKeep[] = $step->id;
            }
            $stage->steps()->when($stepKeep, fn ($q) => $q->whereNotIn('id', $stepKeep))->delete();
        }
        $project->stages()->when($keep, fn ($q) => $q->whereNotIn('id', $keep))->delete();
    }
}
