<?php

namespace App\Http\Controllers;

use App\Models\PassportObject;
use App\Models\Project;
use App\Models\ProjectStageStep;
use App\Models\ProjectStages;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\User;

class ProjectController extends Controller
{
    private const STAGE_TYPES = ['measurement', 'planning', 'drawings', 'equipment', 'estimate', 'visualization'];

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $projects = Project::query()
            ->where('user_id', $userId)
            ->with([
                'object:id,address,city,client_id',
                'object.client:id,full_name',
                'stages.steps',
                'stages.template:id,user_id,name,type,steps',
            ])
            ->orderByDesc('id')
            ->get();

        $objects = PassportObject::query()
            ->where('user_id', $userId)
            ->with('client:id,full_name')
            ->orderByDesc('id')
            ->get(['id', 'address', 'city', 'client_id']);

        $templates = Template::query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();

        return view('projects.index', [
            // Legacy variables kept for current Blade JS initialization
            'projects' => $projects->map(fn (Project $project) => $this->projectPayload($project))->values(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'projectsData' => $projects->map(fn (Project $project) => $this->projectPayload($project))->values(),
            'objectsData' => $objects->map(function (PassportObject $object) {
                return [
                    'id' => $object->id,
                    'address' => $object->address,
                    'city' => $object->city,
                    'client_name' => $object->client?->full_name,
                ];
            })->values(),
            // Backward compatibility for existing Blade loops in projects/index.blade.php
            'objects' => $objects->map(function (PassportObject $object) {
                return [
                    'id' => $object->id,
                    'address' => $object->address,
                    'city' => $object->city,
                ];
            })->values(),
            'clients' => $objects
                ->filter(fn (PassportObject $object) => $object->client !== null)
                ->map(fn (PassportObject $object) => ['id' => $object->client->id, 'name' => $object->client->full_name])
                ->unique('id')
                ->values(),
            'templatesData' => $templates->map(fn (Template $template) => $this->templatePayload($template, $userId))->values(),
            'stageTypes' => self::STAGE_TYPES,
        ]);
    }

    public function show(Request $request, int $projectId)
    {
        $project = Project::query()
            ->where('user_id', $request->user()->id)
            ->with(['object.client', 'stages.steps', 'stages.template'])
            ->findOrFail($projectId);

        return response()->json($this->projectPayload($project));
    }

    public function store(Request $request)
    {
        $project = new Project();
        $project->user_id = $request->user()->id;

        $this->fillAndSave($request, $project);

        return response()->json([
            'success' => true,
            'message' => __('projects.created'),
            'project' => $this->projectPayload($project->load(['object.client', 'stages.steps', 'stages.template'])),
        ]);
    }

    public function update(Request $request, int $projectId)
    {
        $project = Project::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($projectId);

        $this->fillAndSave($request, $project);

        return response()->json([
            'success' => true,
            'message' => __('projects.updated'),
            'project' => $this->projectPayload($project->load(['object.client', 'stages.steps', 'stages.template'])),
        ]);
    }

    public function destroy(Request $request, int $projectId)
    {
        $project = Project::query()
            ->where('user_id', $request->user()->id)
            ->with('stages.steps')
            ->findOrFail($projectId);

        foreach (($project->files ?? []) as $filePath) {
            if (is_string($filePath) && $filePath !== '') {
                Storage::disk('public')->delete($filePath);
            }
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => __('projects.deleted'),
        ]);
    }

    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::STAGE_TYPES)],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*' => ['required', 'string', 'max:1000'],
        ]);

        $template = Template::create([
            'user_id' => $request->user()->id,
            'name' => trim($data['name']),
            'type' => $data['type'],
            'steps' => array_values(array_filter(array_map(fn ($v) => trim((string) $v), $data['steps']))),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('projects.template_saved'),
            'template' => $this->templatePayload($template, $request->user()->id),
        ]);
    }

    public function deleteTemplate(Request $request, int $templateId)
    {
        $template = Template::findOrFail($templateId);
        if ((int) $template->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => __('projects.template_deleted'),
        ]);
    }

    private function fillAndSave(Request $request, Project $project): void
    {
        $userId = $request->user()->id;
        $data = $request->validate([
            'object_id' => ['required', Rule::exists('passport_objects', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date'],
            'planned_cost' => ['nullable', 'numeric'],
            'actual_cost' => ['nullable', 'numeric'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url', 'max:1000'],
            'existing_files' => ['nullable', 'array'],
            'existing_files.*' => ['nullable', 'string', 'max:1000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:10240'],
            'comment' => ['nullable', 'string'],
            'stages' => ['nullable', 'array'],
            'stages.*.stage_type' => ['required_with:stages', Rule::in(self::STAGE_TYPES)],
            'stages.*.template_id' => ['nullable', 'integer'],
            'stages.*.deadline' => ['nullable', 'date'],
            'stages.*.assign_task' => ['nullable', 'boolean'],
            'stages.*.responsible_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'stages.*.steps' => ['nullable', 'array'],
            'stages.*.steps.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $links = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['links'] ?? []))));
        $existingFiles = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['existing_files'] ?? []))));

        $uploadedFiles = [];
        foreach ($request->file('files', []) as $file) {
            if ($file) {
                $uploadedFiles[] = $file->store('projects', 'public');
            }
        }

        $oldFiles = (array) ($project->files ?? []);
        $newFiles = array_values(array_unique(array_merge($existingFiles, $uploadedFiles)));
        foreach ($oldFiles as $oldFile) {
            if (is_string($oldFile) && $oldFile !== '' && ! in_array($oldFile, $newFiles, true)) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $project->object_id = (int) $data['object_id'];
        $project->name = trim($data['name']);
        $project->status = trim($data['status']);
        $project->start_date = $data['start_date'] ?? null;
        $project->planned_end_date = $data['planned_end_date'] ?? null;
        $project->actual_end_date = $data['actual_end_date'] ?? null;
        $project->planned_cost = (float) ($data['planned_cost'] ?? 0);
        $project->actual_cost = (float) ($data['actual_cost'] ?? 0);
        $project->links = $links;
        $project->files = $newFiles;
        $project->comment = $data['comment'] ?? null;
        $project->save();

        $project->stages()->delete();

        $stageRows = (array) ($data['stages'] ?? []);
        foreach ($stageRows as $index => $stageRow) {
            $type = $stageRow['stage_type'] ?? null;
            if (! is_string($type) || $type === '') {
                continue;
            }

            $templateId = isset($stageRow['template_id']) && $stageRow['template_id'] !== ''
                ? (int) $stageRow['template_id']
                : null;
            if ($templateId !== null) {
                $template = Template::query()
                    ->where('id', $templateId)
                    ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))
                    ->first();
                $templateId = $template?->id;
            }

            $stage = ProjectStages::create([
                'project_id' => $project->id,
                'stage_type' => $type,
                'template_id' => $templateId,
                'deadline' => $stageRow['deadline'] ?? null,
                'responsible_id' => ! empty($stageRow['assign_task']) ? ($stageRow['responsible_id'] ?? null) : null,
                'assign_task' => ! empty($stageRow['assign_task']),
                'order' => $index,
            ]);

            $steps = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($stageRow['steps'] ?? []))));
            foreach ($steps as $stepIdx => $stepTitle) {
                ProjectStageStep::create([
                    'project_stage_id' => $stage->id,
                    'title' => $stepTitle,
                    'order' => $stepIdx,
                ]);
            }
        }
    }

    private function projectPayload(Project $project): array
    {
        return [
            'id' => $project->id,
            'object_id' => $project->object_id,
            'object_address' => $project->object?->address,
            'object_city' => $project->object?->city,
            'client_name' => $project->object?->client?->full_name,
            'name' => $project->name,
            'status' => $project->status,
            'start_date' => $project->start_date,
            'planned_end_date' => $project->planned_end_date,
            'actual_end_date' => $project->actual_end_date,
            'planned_cost' => (float) $project->planned_cost,
            'actual_cost' => (float) $project->actual_cost,
            'links' => is_array($project->links) ? $project->links : [],
            'files' => is_array($project->files) ? $project->files : [],
            'file_urls' => collect(is_array($project->files) ? $project->files : [])
                ->map(fn ($f) => is_string($f) ? asset('storage/' . ltrim($f, '/')) : null)
                ->filter()
                ->values(),
            'comment' => $project->comment,
            'stages' => $project->stages->map(function (ProjectStages $stage) {
                return [
                    'id' => $stage->id,
                    'stage_type' => $stage->stage_type,
                    'template_id' => $stage->template_id,
                    'deadline' => $stage->deadline,
                    'responsible_id' => $stage->responsible_id,
                    'assign_task' => (bool) $stage->assign_task,
                    'steps' => $stage->steps->sortBy('order')->pluck('title')->values(),
                ];
            })->values(),
        ];
    }

    private function templatePayload(Template $template, int $userId): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'type' => $template->type,
            'steps' => is_array($template->steps) ? $template->steps : [],
            'is_shared' => $template->user_id === null,
            'is_owned' => (int) $template->user_id === $userId,
        ];
    }
}

