<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\PassportObject;
use App\Models\Project;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Template;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        return view('designer.projects.index', [
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
        $payload = $this->projectPayload($project);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        $objects = PassportObject::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get(['id', 'address', 'city']);

        return view('designer.projects.show', [
            'project' => $project,
            'projectData' => $payload,
            'objects' => $objects,
            'stageTypes' => self::STAGE_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $userId = (int) $request->user()->id;

        if ($msg = $this->passportObjectModerationError($request, $userId)) {
            return response()->json(['success' => false, 'message' => $msg], 422);
        }

        $project = new Project;
        $project->user_id = $userId;

        $this->fillAndSave($request, $project);

        $project->moderation_status = 'approved';
        $project->moderation_reason = null;
        $project->save();

        return response()->json([
            'success' => true,
            'message' => __('projects.created'),
            'project' => $this->projectPayload($project->load(['object.client', 'stages.steps', 'stages.template'])),
        ]);
    }

    public function update(Request $request, int $projectId)
    {
        $userId = (int) $request->user()->id;

        if ($msg = $this->passportObjectModerationError($request, $userId)) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->withErrors(['object_id' => $msg])->withInput();
        }

        $project = Project::query()
            ->where('user_id', $userId)
            ->findOrFail($projectId);

        $this->fillAndSave($request, $project);

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('projects.show', $project->id)->with('status', __('projects.updated'));
        }

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

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('projects.index')->with('status', __('projects.deleted'));
        }

        return response()->json([
            'success' => true,
            'message' => __('projects.deleted'),
        ]);
    }

    public function updateStatus(Request $request, int $projectId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                'contract_negotiation',
                'contract_signed',
                'prepayment_received',
                'tz_signed',
                'documents_signed',
                'in_work',
            ])],
        ]);

        $project = Project::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($projectId);

        $project->status = $data['status'];
        $project->save();

        return response()->json([
            'success' => true,
            'project' => $this->projectPayload($project->load(['object.client', 'stages.steps', 'stages.template'])),
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

    /**
     * ÃÂÃÂµ Ã‘ÂÃÂ¾ÃÂ·ÃÂ´ÃÂ°ÃÂ²ÃÂ°Ã‘â€šÃ‘Å’/ÃÂ½ÃÂµ ÃÂ¿Ã‘â‚¬ÃÂ¸ÃÂ²Ã‘ÂÃÂ·Ã‘â€¹ÃÂ²ÃÂ°Ã‘â€šÃ‘Å’ ÃÂ¿Ã‘â‚¬ÃÂ¾ÃÂµÃÂºÃ‘â€š ÃÂº ÃÂ¾ÃÂ±Ã‘Å ÃÂµÃÂºÃ‘â€šÃ‘Æ’ ÃÂ½ÃÂ° ÃÂ¼ÃÂ¾ÃÂ´ÃÂµÃ‘â‚¬ÃÂ°Ã‘â€ ÃÂ¸ÃÂ¸ ÃÂ¸ÃÂ»ÃÂ¸ ÃÂ¾Ã‘â€šÃÂºÃÂ»ÃÂ¾ÃÂ½Ã‘â€˜ÃÂ½ÃÂ½ÃÂ¾ÃÂ¼Ã‘Æ’ ÃÂ¼ÃÂ¾ÃÂ´ÃÂµÃ‘â‚¬ÃÂ°Ã‘â€šÃÂ¾Ã‘â‚¬ÃÂ¾ÃÂ¼.
     */
    private function passportObjectModerationError(Request $request, int $userId): ?string
    {
        $objectId = $request->input('object_id');
        if ($objectId === null || $objectId === '') {
            return null;
        }

        $object = PassportObject::query()
            ->where('user_id', $userId)
            ->find((int) $objectId);

        if (! $object) {
            return null;
        }

        $status = (string) ($object->moderation_status ?? '');

        if ($status === 'pending') {
            return __('projects.object_moderation_pending');
        }

        if ($status === 'rejected') {
            return __('projects.object_moderation_rejected');
        }

        return null;
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
            'stages.*.steps.*.title' => ['nullable', 'string', 'max:1000'],
            'stages.*.steps.*.deadline' => ['nullable', 'date'],
            'stages.*.steps.*.responsible_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'stages.*.steps.*.link' => ['nullable', 'url', 'max:1000'],
            'stages.*.steps.*.result_status' => ['nullable', 'string', Rule::in(['pending', 'done'])],
            'stages.*.steps.*.result_comment' => ['nullable', 'string', 'max:5000'],
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
                'responsible_id' => $stageRow['responsible_id'] ?? null,
                'assign_task' => ! empty($stageRow['assign_task']),
                'order' => $index,
            ]);

            $steps = (array) ($stageRow['steps'] ?? []);
            foreach ($steps as $stepIdx => $stepRow) {
                $title = is_array($stepRow)
                    ? trim((string) ($stepRow['title'] ?? ''))
                    : trim((string) $stepRow);
                if ($title === '') {
                    continue;
                }

                $deadline = is_array($stepRow) ? ($stepRow['deadline'] ?? null) : null;
                $responsibleId = is_array($stepRow) ? ($stepRow['responsible_id'] ?? null) : null;
                $link = is_array($stepRow) ? trim((string) ($stepRow['link'] ?? '')) : '';
                $resultStatus = is_array($stepRow) ? (string) ($stepRow['result_status'] ?? 'pending') : 'pending';
                $resultComment = is_array($stepRow) ? ($stepRow['result_comment'] ?? null) : null;

                ProjectStageStep::create([
                    'project_stage_id' => $stage->id,
                    'title' => $title,
                    'deadline' => $deadline ?: null,
                    'responsible_id' => $responsibleId ?: null,
                    'link' => $link !== '' ? $link : null,
                    'result_status' => $resultStatus,
                    'result_comment' => is_string($resultComment) && trim($resultComment) !== '' ? $resultComment : null,
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

            // Moderation
            'moderation_status' => $project->moderation_status,
            'moderation_reason' => $project->moderation_reason,
            'moderation_comment' => $project->moderation_comment,
            'start_date' => $project->start_date,
            'planned_end_date' => $project->planned_end_date,
            'actual_end_date' => $project->actual_end_date,
            'planned_cost' => (float) $project->planned_cost,
            'actual_cost' => (float) $project->actual_cost,
            'links' => is_array($project->links) ? $project->links : [],
            'files' => is_array($project->files) ? $project->files : [],
            'file_urls' => collect(is_array($project->files) ? $project->files : [])
                ->map(fn ($f) => is_string($f) ? asset('storage/'.ltrim($f, '/')) : null)
                ->filter()
                ->values(),
            'comment' => $project->comment,
            'stages' => $project->stages->map(function (ProjectStages $stage) {
                $type = (string) $stage->stage_type;
                $labelKey = 'projects.stage_'.$type;
                $stageLabel = $type !== '' ? (string) __($labelKey) : '';
                if ($stageLabel === $labelKey) {
                    $stageLabel = $type;
                }

                return [
                    'id' => $stage->id,
                    'stage_type' => $stage->stage_type,
                    'stage_type_label' => $stageLabel,
                    'template_id' => $stage->template_id,
                    'deadline' => $stage->deadline,
                    'responsible_id' => $stage->responsible_id,
                    'assign_task' => (bool) $stage->assign_task,
                    'steps' => $stage->steps->sortBy('order')->map(function (ProjectStageStep $step) {
                        return [
                            'id' => $step->id,
                            'title' => $step->title,
                            'deadline' => $step->deadline,
                            'responsible_id' => $step->responsible_id,
                            'link' => $step->link,
                            'result_status' => $step->result_status ?? 'pending',
                            'result_comment' => $step->result_comment,
                        ];
                    })->values(),
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


