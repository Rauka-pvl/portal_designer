<?php

namespace App\Http\Requests\Designer;

use App\Enums\DesignerTaskStatus;
use App\Models\Project;
use App\Services\Team\TeamService;
use App\Support\WorkspaceAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DesignerTaskSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'status' => ['nullable', 'string', Rule::in(DesignerTaskStatus::values())],
            'due_at' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $teams = app(TeamService::class);

            try {
                $assigneeId = $teams->assertAssigneeAllowed(
                    $user,
                    $this->filled('assignee_id') ? (int) $this->input('assignee_id') : (int) $user->id
                );
                $this->merge(['assignee_id' => $assigneeId]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->errors() as $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add('assignee_id', $message);
                    }
                }
            }

            if ($this->filled('project_id')) {
                $project = Project::query()->find((int) $this->input('project_id'));
                if (! $project || ! WorkspaceAccess::canAccessProject($user, $project)) {
                    $validator->errors()->add('project_id', __('tasks.invalid_project'));
                }
            }
        });
    }
}
