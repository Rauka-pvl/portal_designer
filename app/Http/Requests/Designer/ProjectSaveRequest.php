<?php

namespace App\Http\Requests\Designer;

use App\Enums\PipelineType;
use App\Enums\ProjectStageType;
use App\Models\Pipeline;
use App\Services\Team\TeamService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProjectSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('status')) {
            return;
        }

        $userId = (int) $this->user()?->id;
        if (! $userId) {
            return;
        }

        $first = Pipeline::defaultForUser($userId, PipelineType::Project)
            ?->stages()
            ->orderBy('position')
            ->value('system_key');

        if ($first) {
            $this->merge(['status' => $first]);
        }
    }

    public function rules(): array
    {
        $userId = (int) $this->user()->id;
        $teamService = app(TeamService::class);
        $allowedIds = collect($teamService->assigneeOptions($this->user()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($allowedIds === []) {
            $allowedIds = [$userId];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:64'],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(function ($q) use ($userId, $teamService) {
                    $ownerIds = [$userId];
                    $team = $teamService->activeTeamFor($this->user());
                    if ($team && $teamService->teamHasCorporateAccess($team)) {
                        $ownerIds[] = (int) $team->owner_id;
                    }
                    $q->whereIn('user_id', array_values(array_unique($ownerIds)));
                }),
            ],
            'start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date'],
            'comment' => ['nullable', 'string'],

            'city' => ['nullable', 'string', 'max:255'],
            'object_type' => ['nullable', Rule::in(['apartment', 'house', 'commercial', 'office', 'other'])],
            'object_address' => ['nullable', 'string', 'max:500'],
            'apartment_floor' => ['nullable', 'string', 'max:50'],
            'apartment_entrance' => ['nullable', 'string', 'max:50'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'repair_budget_planned' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_actual' => ['nullable', 'numeric', 'min:0'],

            'links' => ['nullable', 'array'],
            'links.*.title' => ['nullable', 'string', 'max:255'],
            'links.*.url' => ['required_with:links.*.title', 'nullable', 'url', 'max:1000'],

            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['nullable', 'string', 'max:1000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],

            'stages' => ['nullable', 'array'],
            'stages.*.id' => ['nullable', 'integer'],
            'stages.*.stage_type' => ['required_with:stages', Rule::in(ProjectStageType::values())],
            'stages.*.name' => ['nullable', 'string', 'max:255'],
            'stages.*.template_id' => ['nullable', 'integer'],
            'stages.*.deadline' => ['nullable', 'date'],
            'stages.*.assign_task' => ['nullable', 'boolean'],
            'stages.*.responsible_id' => [
                'nullable',
                'integer',
                Rule::in($allowedIds),
            ],
            'stages.*.steps' => ['nullable', 'array'],
            'stages.*.steps.*.id' => ['nullable', 'integer'],
            'stages.*.steps.*.title' => ['nullable', 'string', 'max:1000'],
            'stages.*.steps.*.deadline' => ['nullable', 'date'],
            'stages.*.steps.*.responsible_id' => [
                'nullable',
                'integer',
                Rule::in($allowedIds),
            ],
            'stages.*.steps.*.link' => ['nullable', 'url', 'max:1000'],
            'stages.*.steps.*.result_status' => ['nullable', 'string', Rule::in(['pending', 'done'])],
            'stages.*.steps.*.result_comment' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $address = trim((string) $this->input('object_address', ''));
            if ($address !== '') {
                $lat = $this->input('latitude');
                $lng = $this->input('longitude');
                if ($lat === null || $lat === '' || $lng === null || $lng === ''
                    || ! is_numeric($lat) || ! is_numeric($lng)) {
                    $validator->errors()->add('latitude', __('objects.map_point_required'));
                }
            }

            $teamService = app(TeamService::class);
            $user = $this->user();

            foreach ((array) $this->input('stages', []) as $i => $stage) {
                if (! is_array($stage)) {
                    continue;
                }
                try {
                    if (array_key_exists('responsible_id', $stage) && $stage['responsible_id'] !== null && $stage['responsible_id'] !== '') {
                        $teamService->assertAssigneeAllowed($user, (int) $stage['responsible_id']);
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    foreach ($e->errors() as $messages) {
                        foreach ($messages as $message) {
                            $validator->errors()->add("stages.$i.responsible_id", $message);
                        }
                    }
                }

                foreach ((array) ($stage['steps'] ?? []) as $j => $step) {
                    if (! is_array($step) || ! array_key_exists('responsible_id', $step)) {
                        continue;
                    }
                    if ($step['responsible_id'] === null || $step['responsible_id'] === '') {
                        continue;
                    }
                    try {
                        $teamService->assertAssigneeAllowed($user, (int) $step['responsible_id']);
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        foreach ($e->errors() as $messages) {
                            foreach ($messages as $message) {
                                $validator->errors()->add("stages.$i.steps.$j.responsible_id", $message);
                            }
                        }
                    }
                }
            }
        });
    }
}
