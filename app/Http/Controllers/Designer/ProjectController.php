<?php

namespace App\Http\Controllers\Designer;

use App\Enums\PipelineType;
use App\Enums\ProjectStatus;
use App\Enums\SupplyStatus;
use App\Http\Controllers\Controller;
use App\Models\PassportObject;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\ProjectObjectDetail;
use App\Models\ProjectStages;
use App\Models\ProjectStageStep;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\Template;
use App\Models\User;
use App\Services\Crm\ActivityFeedService;
use App\Services\Crm\ChecklistService;
use App\Services\Crm\PipelineService;
use App\Services\Crm\ProjectService;
use App\Support\AccountPermissions;
use App\Support\PublicFileStorage;
use App\Support\WorkspaceAccess;
use App\Services\Team\AssignmentNotifier;
use App\Services\Team\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const STAGE_TYPES = ['measurement', 'planning', 'drawings', 'equipment', 'estimate', 'visualization'];

    public function __construct(
        private PipelineService $pipelines,
        private ActivityFeedService $activity,
        private ChecklistService $checklists,
        private ProjectService $projects,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $this->pipelines->ensureDefaultsForUser((int) $userId);

        $projects = WorkspaceAccess::scopeProjects(Project::query(), $user)
            ->with([
                'user:id,name',
                'client:id,full_name',
                'object:id,address,city,client_id,area',
                'object.client:id,full_name',
                'objectDetails.client:id,full_name',
                'stages.steps',
                'stages.template:id,user_id,name,type,steps',
                'stages.responsible:id,name',
                'supplierOrders.supplier:id,name',
            ])
            ->orderByDesc('id')
            ->get();

        $clientOwnerIds = [(int) $userId];
        $team = app(TeamService::class)->activeTeamFor($user);
        if ($team && app(TeamService::class)->teamHasCorporateAccess($team)) {
            $clientOwnerIds[] = (int) $team->owner_id;
        }

        $clients = \App\Models\Client::query()
            ->whereIn('user_id', array_values(array_unique($clientOwnerIds)))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $cities = trans('cities.passport');
        $cities = is_array($cities) ? array_values($cities) : [];

        $templates = Template::query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();

        $projectPipeline = Pipeline::defaultForUser((int) $userId, PipelineType::Project);
        $supplyPipeline = Pipeline::defaultForUser((int) $userId, PipelineType::Supply);

        $suppliers = Supplier::query()
            ->where(function ($q) use ($userId) {
                $q->where('created_by_user_id', $userId)
                    ->orWhere(function ($legacy) use ($userId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $userId);
                    })
                    ->orWhere(function ($q2) {
                        $q2->where('profile_status', 'active')
                            ->where('moderation_status', 'approved');
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'moderation_status']);

        $categoryOptions = is_array(trans('categories')) ? trans('categories') : [];
        $roomOptions = is_array(trans('type_room')) ? trans('type_room') : [];

        $payload = [
            'projectsData' => $projects->map(fn (Project $project) => $this->projectPayload($project))->values(),
            'clientsData' => $clients->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values(),
            'suppliersData' => $suppliers->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'moderation_status' => $s->moderation_status,
            ])->values(),
            'categoryOptions' => $categoryOptions,
            'roomOptions' => $roomOptions,
            'cities' => $cities,
            'objectTypes' => [
                ['id' => 'apartment', 'label' => __('objects.apartment')],
                ['id' => 'house', 'label' => __('objects.house')],
                ['id' => 'commercial', 'label' => __('objects.commercial')],
                ['id' => 'office', 'label' => __('objects.office')],
                ['id' => 'other', 'label' => __('objects.other')],
            ],
            'users' => collect(app(TeamService::class)->assigneeOptions($user))
                ->map(fn (array $o) => [
                    'id' => $o['id'],
                    'name' => $o['name'],
                    'email' => $o['email'],
                    'role' => $o['role'] ?? null,
                    'role_label' => $o['role_label'] ?? null,
                ])
                ->values(),
            'templatesData' => $templates->map(fn (Template $template) => $this->templatePayload($template, $userId))->values(),
            'stageTypes' => self::STAGE_TYPES,
            'pipeline' => $this->pipelinePayload($projectPipeline),
            'supplyPipeline' => $this->pipelinePayload($supplyPipeline),
            'canManagePipeline' => AccountPermissions::canManageProjectPipeline($user),
            'isCorporate' => WorkspaceAccess::isCorporate($user),
        ];

        // Legacy view compatibility
        $payload['projects'] = $payload['projectsData'];
        $payload['clients'] = $payload['clientsData'];
        $payload['objects'] = collect();
        $payload['objectsData'] = collect();

        if ($request->boolean('legacy')) {
            return view('designer.projects.index', $payload);
        }

        return view('designer.projects.crm', $payload);
    }

    public function show(Request $request, int $projectId)
    {
        $project = WorkspaceAccess::scopeProjects(Project::query(), $request->user())
            ->with(['client', 'user:id,name', 'object.client', 'objectDetails.client', 'stages.steps', 'stages.template', 'stages.responsible:id,name', 'supplierOrders.supplier'])
            ->findOrFail($projectId);
        $payload = $this->projectPayload($project);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return redirect()->route('projects.index', ['open' => $project->id]);
    }

    public function store(\App\Http\Requests\Designer\ProjectSaveRequest $request)
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $project = new Project;
        $project->user_id = $userId;
        WorkspaceAccess::attachTeamOnCreate($user, $project);

        $this->projects->fillAndSave($request, $project);

        $project->moderation_status = 'approved';
        $project->moderation_reason = null;
        $project->save();

        $this->activity->record(
            $userId,
            'project',
            $project->id,
            'project.created',
            $request->user(),
            null,
            ['name' => $project->name]
        );

        return response()->json([
            'success' => true,
            'message' => __('projects.created'),
            'project' => $this->projectPayload($project->load(['client', 'object.client', 'objectDetails.client', 'stages.steps', 'stages.template', 'stages.responsible:id,name', 'supplierOrders.supplier'])),
        ]);
    }

    public function update(\App\Http\Requests\Designer\ProjectSaveRequest $request, int $projectId)
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $project = WorkspaceAccess::scopeProjects(Project::query(), $user)
            ->findOrFail($projectId);

        $this->projects->fillAndSave($request, $project);
        $this->activity->record(
            $userId,
            'project',
            $project->id,
            'project.updated',
            $request->user()
        );

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('projects.index', ['open' => $project->id])->with('status', __('projects.updated'));
        }

        return response()->json([
            'success' => true,
            'message' => __('projects.updated'),
            'project' => $this->projectPayload($project->load(['client', 'object.client', 'objectDetails.client', 'stages.steps', 'stages.template', 'stages.responsible:id,name', 'supplierOrders.supplier'])),
        ]);
    }

    public function destroy(Request $request, int $projectId)
    {
        $project = WorkspaceAccess::scopeProjects(Project::query(), $request->user())
            ->with('stages.steps')
            ->findOrFail($projectId);

        $this->projects->destroy($project);

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('projects.index')->with('status', __('projects.deleted'));
        }

        return response()->json([
            'success' => true,
            'message' => __('projects.deleted'),
        ]);
    }

    public function deleteFile(Request $request, int $projectId, int $fileIndex)
    {
        $project = WorkspaceAccess::scopeProjects(Project::query(), $request->user())
            ->with(['object.client', 'stages.steps', 'stages.template'])
            ->findOrFail($projectId);

        $files = is_array($project->files) ? array_values($project->files) : [];
        if ($files === [] || $fileIndex < 0 || $fileIndex >= count($files)) {
            return response()->json([
                'success' => false,
                'message' => __('projects.delete_error_generic'),
            ], 422);
        }

        $filePath = $files[$fileIndex];
        if (is_string($filePath) && $filePath !== '') {
            Storage::disk('public')->delete($filePath);
        }

        array_splice($files, $fileIndex, 1);
        $project->files = array_values($files);
        $project->save();

        return response()->json([
            'success' => true,
            'project' => $this->projectPayload($project->fresh(['object.client', 'stages.steps', 'stages.template'])),
        ]);
    }

    public function updateStatus(Request $request, int $projectId)
    {
        $userId = (int) $request->user()->id;
        $this->pipelines->ensureDefaultsForUser($userId);

        $allowedKeys = PipelineStage::query()
            ->whereHas('pipeline', fn ($q) => $q->where('user_id', $userId)->where('type', PipelineType::Project))
            ->pluck('system_key')
            ->all();

        if ($allowedKeys === []) {
            $allowedKeys = ProjectStatus::values();
        }

        $data = $request->validate([
            'status' => ['required', Rule::in($allowedKeys)],
        ]);

        $project = WorkspaceAccess::scopeProjects(Project::query(), $request->user())
            ->findOrFail($projectId);

        $from = (string) $project->status;
        $to = (string) $data['status'];

        if ($from !== $to) {
            $project->status = $to;
            $project->save();

            $labels = PipelineStage::query()
                ->whereHas('pipeline', fn ($q) => $q->where('user_id', $userId)->where('type', PipelineType::Project))
                ->whereIn('system_key', [$from, $to])
                ->pluck('name', 'system_key');

            $this->activity->record(
                $userId,
                'project',
                $project->id,
                'project.status_changed',
                $request->user(),
                null,
                [
                    'from' => $from,
                    'to' => $to,
                    'from_label' => $labels[$from] ?? $from,
                    'to_label' => $labels[$to] ?? $to,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('projects.status_updated'),
            'project' => $this->projectPayload($project->load(['client', 'object.client', 'objectDetails.client', 'stages.steps', 'stages.template', 'stages.responsible:id,name', 'supplierOrders.supplier'])),
        ]);
    }

    public function templates(Request $request)
    {
        $userId = (int) $request->user()->id;

        $templates = Template::query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates->map(fn (Template $template) => $this->templatePayload($template, $userId))->values(),
            'stage_types' => self::STAGE_TYPES,
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

        $template = $this->checklists->createTemplate((int) $request->user()->id, $data);

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
     * @deprecated Object selection removed from UI; kept for legacy API payloads.
     */
    private function passportObjectModerationError(Request $request, int $userId): ?string
    {
        return null;
    }

    private function fillAndSave(\App\Http\Requests\Designer\ProjectSaveRequest $request, Project $project): void
    {
        $userId = (int) $request->user()->id;
        $data = $request->validated();

        $links = $this->normalizeLinks($data['links'] ?? []);
        $existingFiles = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($data['existing_files'] ?? []))));

        $uploadedFiles = [];
        foreach ($request->file('files', []) as $file) {
            if ($file) {
                $uploadedFiles[] = PublicFileStorage::store($file, 'projects');
            }
        }

        $oldFiles = (array) ($project->files ?? []);
        $newFiles = array_values(array_unique(array_merge($existingFiles, $uploadedFiles)));
        foreach ($oldFiles as $oldFile) {
            if (is_string($oldFile) && $oldFile !== '' && ! in_array($oldFile, $newFiles, true)) {
                Storage::disk('public')->delete($oldFile);
            }
        }

        $budgetPlan = array_key_exists('repair_budget_planned', $data) && $data['repair_budget_planned'] !== null
            ? (float) $data['repair_budget_planned']
            : (float) ($project->planned_cost ?? 0);
        $budgetFact = array_key_exists('repair_budget_actual', $data) && $data['repair_budget_actual'] !== null
            ? (float) $data['repair_budget_actual']
            : (float) ($project->actual_cost ?? 0);

        $project->client_id = ! empty($data['client_id']) ? (int) $data['client_id'] : null;
        $project->name = trim($data['name']);
        $project->status = trim($data['status']);
        $project->start_date = $data['start_date'] ?? ($project->start_date ?: now()->toDateString());
        $project->planned_end_date = $data['planned_end_date'] ?? ($project->planned_end_date ?: now()->addMonth()->toDateString());
        $project->actual_end_date = $data['actual_end_date'] ?? null;
        $project->planned_cost = $budgetPlan;
        $project->actual_cost = $budgetFact;
        $project->links = $links;
        $project->files = $newFiles;
        $project->comment = $data['comment'] ?? null;
        $project->save();

        $this->saveObjectDetails($project, $data);
        $this->saveStages($project, $data, $userId);
    }

    private function normalizeLinks(array $raw): array
    {
        $out = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $url = trim($item);
                if ($url !== '') {
                    $out[] = ['title' => '', 'url' => $url];
                }
                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $out[] = [
                'title' => trim((string) ($item['title'] ?? '')),
                'url' => $url,
            ];
        }

        return array_values($out);
    }

    private function saveObjectDetails(Project $project, array $data): void
    {
        $existing = ProjectObjectDetail::query()->where('project_id', $project->id)->first();

        // Preserve legacy property fields when UI no longer sends them.
        $city = array_key_exists('city', $data) ? ($data['city'] ?? null) : $existing?->city;
        $address = array_key_exists('object_address', $data) ? ($data['object_address'] ?? null) : $existing?->address;
        $apartment = array_key_exists('apartment', $data) ? ($data['apartment'] ?? null) : $existing?->apartment;
        $floor = array_key_exists('apartment_floor', $data) ? ($data['apartment_floor'] ?? null) : $existing?->apartment_floor;
        $entrance = array_key_exists('apartment_entrance', $data) ? ($data['apartment_entrance'] ?? null) : $existing?->apartment_entrance;
        $type = array_key_exists('object_type', $data) ? ($data['object_type'] ?? null) : $existing?->type;
        $area = array_key_exists('area', $data)
            ? (isset($data['area']) && $data['area'] !== '' ? (float) $data['area'] : null)
            : ($existing?->area !== null ? (float) $existing->area : null);

        $budgetPlan = array_key_exists('repair_budget_planned', $data)
            ? (isset($data['repair_budget_planned']) && $data['repair_budget_planned'] !== ''
                ? (float) $data['repair_budget_planned']
                : null)
            : ($existing?->repair_budget_planned !== null ? (float) $existing->repair_budget_planned : null);
        $budgetFact = array_key_exists('repair_budget_actual', $data)
            ? (isset($data['repair_budget_actual']) && $data['repair_budget_actual'] !== ''
                ? (float) $data['repair_budget_actual']
                : null)
            : ($existing?->repair_budget_actual !== null ? (float) $existing->repair_budget_actual : null);

        ProjectObjectDetail::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'passport_object_id' => $project->object_id,
                'client_id' => $project->client_id,
                'city' => $city,
                'address' => $address,
                'apartment' => $apartment,
                'apartment_floor' => $floor,
                'apartment_entrance' => $entrance,
                'type' => $type,
                'area' => $area,
                'repair_budget_planned' => $budgetPlan,
                'repair_budget_actual' => $budgetFact,
                'repair_budget_per_m2_planned' => ($area && $area > 0 && $budgetPlan !== null)
                    ? round($budgetPlan / $area, 2) : null,
                'repair_budget_per_m2_actual' => ($area && $area > 0 && $budgetFact !== null)
                    ? round($budgetFact / $area, 2) : null,
            ]
        );
    }

    private function saveStages(Project $project, array $data, int $userId): void
    {
        // Preserve previous behaviour: replace stages when payload includes stages key.
        // Sync by id when provided so checklist step IDs (and supply included_step_ids) stay stable.
        if (! array_key_exists('stages', $data)) {
            return;
        }

        $keepStageIds = [];

        foreach ((array) ($data['stages'] ?? []) as $index => $stageRow) {
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

            $stageId = isset($stageRow['id']) && $stageRow['id'] !== '' ? (int) $stageRow['id'] : null;
            $stage = null;
            if ($stageId) {
                $stage = ProjectStages::query()
                    ->where('project_id', $project->id)
                    ->whereKey($stageId)
                    ->first();
            }

            $customName = isset($stageRow['name']) ? trim((string) $stageRow['name']) : '';
            $previousResponsibleId = $stage?->responsible_id ? (int) $stage->responsible_id : null;
            $resolvedResponsibleId = null;
            if (array_key_exists('responsible_id', $stageRow) && $stageRow['responsible_id'] !== null && $stageRow['responsible_id'] !== '') {
                $resolvedResponsibleId = app(TeamService::class)->assertAssigneeAllowed(
                    request()->user(),
                    (int) $stageRow['responsible_id']
                );
            }

            $attrs = [
                'stage_type' => $type,
                'name' => $customName !== '' ? $customName : null,
                'template_id' => $templateId,
                'deadline' => $stageRow['deadline'] ?? null,
                'responsible_id' => $resolvedResponsibleId,
                'assign_task' => ! empty($stageRow['assign_task']),
                'order' => $index,
            ];

            if ($stage) {
                $stage->fill($attrs)->save();
            } else {
                $stage = ProjectStages::create(array_merge($attrs, [
                    'project_id' => $project->id,
                    'created_by' => $userId,
                ]));
            }

            $keepStageIds[] = (int) $stage->id;
            $keepStepIds = [];

            app(AssignmentNotifier::class)->notifyChecklistAssigned(
                request()->user(),
                $previousResponsibleId,
                $resolvedResponsibleId,
                $project,
                $stage
            );

            foreach ((array) ($stageRow['steps'] ?? []) as $stepIdx => $stepRow) {
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
                $stepId = is_array($stepRow) && isset($stepRow['id']) && $stepRow['id'] !== ''
                    ? (int) $stepRow['id']
                    : null;

                $stepAttrs = [
                    'title' => $title,
                    'deadline' => $deadline ?: null,
                    'responsible_id' => $responsibleId ?: null,
                    'link' => $link !== '' ? $link : null,
                    'result_status' => $resultStatus,
                    'result_comment' => is_string($resultComment) && trim($resultComment) !== '' ? $resultComment : null,
                    'order' => $stepIdx,
                ];

                $step = null;
                if ($stepId) {
                    $step = ProjectStageStep::query()
                        ->where('project_stage_id', $stage->id)
                        ->whereKey($stepId)
                        ->first();
                }

                if ($step) {
                    $step->fill($stepAttrs)->save();
                } else {
                    $step = ProjectStageStep::create(array_merge($stepAttrs, [
                        'project_stage_id' => $stage->id,
                    ]));
                }

                $keepStepIds[] = (int) $step->id;
            }

            $stepsQuery = ProjectStageStep::query()->where('project_stage_id', $stage->id);
            if ($keepStepIds !== []) {
                $stepsQuery->whereNotIn('id', $keepStepIds);
            }
            $stepsQuery->delete();
        }

        $stagesQuery = $project->stages();
        if ($keepStageIds !== []) {
            $stagesQuery->whereNotIn('id', $keepStageIds);
        }
        $stagesQuery->delete();
    }

    private function projectPayload(Project $project): array
    {
        if (! $project->relationLoaded('stages')) {
            $project->load([
                'stages.steps',
                'stages.template:id,user_id,name,type,steps',
                'stages.responsible:id,name',
            ]);
        } elseif ($project->stages->isNotEmpty()) {
            $project->stages->loadMissing(['steps', 'template:id,user_id,name,type,steps', 'responsible:id,name']);
        }

        $workflowStatus = match ((string) ($project->moderation_status ?? '')) {
            'pending' => 'in_moderation',
            'rejected' => 'rejected',
            default => (string) $project->status,
        };

        $property = $project->propertySnapshot();
        $links = is_array($project->links) ? $project->links : [];
        $normalizedLinks = [];
        foreach ($links as $link) {
            if (is_string($link)) {
                $normalizedLinks[] = ['title' => '', 'url' => $link];
            } elseif (is_array($link) && ! empty($link['url'])) {
                $normalizedLinks[] = [
                    'title' => (string) ($link['title'] ?? ''),
                    'url' => (string) $link['url'],
                ];
            }
        }

        $today = now()->toDateString();
        $hasDelayedSupply = $project->relationLoaded('supplierOrders')
            && $project->supplierOrders->contains(function (Supplier_orders $o) use ($today) {
                $planned = $o->date_planned
                    ? (\Illuminate\Support\Carbon::parse($o->date_planned)->toDateString())
                    : null;

                return $planned
                    && $planned < $today
                    && (string) $o->status !== SupplyStatus::DeliveryCompleted->value;
            });

        return [
            'id' => $project->id,
            'client_id' => $property['client_id'],
            'client_name' => $property['client_name'],
            'owner_name' => $project->relationLoaded('user')
                ? ($project->user?->name)
                : null,
            'responsible_name' => $project->relationLoaded('user')
                ? ($project->user?->name)
                : null,
            'has_delayed_supply' => (bool) $hasDelayedSupply,
            'object_id' => $project->object_id,
            'object_address' => $property['address'],
            'object_city' => $property['city'],
            'name' => $project->name,
            'status' => $project->status,
            'workflow_status' => $workflowStatus,
            'moderation_status' => $project->moderation_status,
            'moderation_reason' => $project->moderation_reason,
            'moderation_comment' => $project->moderation_comment,
            'start_date' => $project->start_date,
            'planned_end_date' => $project->planned_end_date,
            'actual_end_date' => $project->actual_end_date,
            'planned_cost' => (float) ($property['repair_budget_planned'] ?? $project->planned_cost ?? 0),
            'actual_cost' => (float) ($property['repair_budget_actual'] ?? $project->actual_cost ?? 0),
            'city' => $property['city'],
            'object_type' => $property['type'],
            'object_address_field' => $property['address'],
            'apartment_floor' => $property['apartment_floor'],
            'apartment_entrance' => $property['apartment_entrance'],
            'apartment' => $property['apartment'],
            'area' => $property['area'],
            'repair_budget_planned' => $property['repair_budget_planned'],
            'repair_budget_actual' => $property['repair_budget_actual'],
            'repair_budget_per_m2_planned' => $property['repair_budget_per_m2_planned'],
            'repair_budget_per_m2_actual' => $property['repair_budget_per_m2_actual'],
            'links' => $normalizedLinks,
            'files' => is_array($project->files) ? $project->files : [],
            'file_urls' => collect(is_array($project->files) ? $project->files : [])
                ->map(fn ($f) => is_string($f) ? asset('storage/'.ltrim($f, '/')) : null)
                ->filter()
                ->values(),
            'file_items' => collect(is_array($project->files) ? $project->files : [])
                ->map(function ($f) {
                    if (! is_string($f) || trim($f) === '') {
                        return null;
                    }

                    return [
                        'path' => $f,
                        'name' => basename($f),
                        'url' => asset('storage/'.ltrim($f, '/')),
                    ];
                })
                ->filter()
                ->values(),
            'comment' => $project->comment,
            'object_details' => $property,
            'checklist_progress' => $project->relationLoaded('stages')
                ? (function () use ($project) {
                    $steps = $project->stages->flatMap->steps;
                    $total = $steps->count();
                    $done = $steps->where('result_status', 'done')->count();

                    return [
                        'total' => $total,
                        'done' => $done,
                        'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
                    ];
                })()
                : ['total' => 0, 'done' => 0, 'percent' => 0],
            'supplier_orders' => $project->relationLoaded('supplierOrders')
                ? (function () use ($project) {
                    $orderIds = $project->supplierOrders->pluck('id')->map(fn ($id) => (int) $id)->all();
                    $unreadMap = $this->chatUnreadMapForDesigner((int) auth()->id(), $orderIds);

                    return $project->supplierOrders->map(function (Supplier_orders $o) use ($unreadMap) {
                        $items = is_array($o->product_items) ? $o->product_items : [];
                        $offer = $o->offerPayload('designer');

                        return array_merge([
                            'id' => $o->id,
                            'status' => $o->status,
                            'workflow_status' => (bool) $o->is_sent_to_supplier ? (string) $o->status : 'draft',
                            'summa' => (int) ($o->summa ?? 0),
                            'amount' => (int) ($o->summa ?? 0),
                            'supplier_id' => $o->supplier_id,
                            'supplier_name' => $o->supplier?->name,
                            'bonus_percent' => $o->bonus_percent !== null ? (float) $o->bonus_percent : null,
                            'products_count' => count($items),
                            'date_planned' => $o->date_planned
                                ? \Illuminate\Support\Carbon::parse($o->date_planned)->toDateString()
                                : null,
                            'created_date' => optional($o->created_at)->format('Y-m-d'),
                            'is_sent_to_supplier' => (bool) $o->is_sent_to_supplier,
                            'product_items' => $items,
                            'unread_chat_count' => max(0, (int) ($unreadMap[(int) $o->id] ?? 0)),
                        ], $offer);
                    })->values();
                })()
                : [],
            'stages' => $project->stages->map(function (ProjectStages $stage) {
                $type = (string) $stage->stage_type;
                $labelKey = 'projects.stage_'.$type;
                $stageLabel = $type !== '' ? (string) __($labelKey) : '';
                if ($stageLabel === $labelKey) {
                    $stageLabel = $type;
                }

                $steps = $stage->steps->sortBy('order')->values();
                $total = $steps->count();
                $done = $steps->where('result_status', 'done')->count();
                $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;
                $deadline = $stage->deadline
                    ? \Illuminate\Support\Carbon::parse($stage->deadline)->toDateString()
                    : null;
                $isOverdue = $deadline
                    && $percent < 100
                    && \Illuminate\Support\Carbon::parse($deadline)->endOfDay()->isPast();

                $customName = is_string($stage->name) ? trim($stage->name) : '';
                $displayName = $customName !== '' ? $customName : $stageLabel;

                return [
                    'id' => $stage->id,
                    'name' => $displayName,
                    'custom_name' => $customName !== '' ? $customName : null,
                    'stage_type' => $stage->stage_type,
                    'stage_type_label' => $stageLabel,
                    'template_id' => $stage->template_id,
                    'deadline' => $deadline,
                    'responsible_id' => $stage->responsible_id,
                    'responsible_name' => $stage->responsible?->name,
                    'assign_task' => (bool) $stage->assign_task,
                    'steps_total' => $total,
                    'steps_done' => $done,
                    'progress_percent' => $percent,
                    'is_overdue' => $isOverdue,
                    'state' => $percent >= 100 ? 'done' : ($done > 0 ? 'in_progress' : 'not_started'),
                    'steps' => $steps->map(function (ProjectStageStep $step) {
                        $comment = $step->result_comment;

                        return [
                            'id' => $step->id,
                            'title' => $step->title,
                            'deadline' => $step->deadline,
                            'responsible_id' => $step->responsible_id,
                            'link' => $step->link,
                            'result_status' => $step->result_status ?? 'pending',
                            'result_comment' => $comment,
                            'has_result' => is_string($comment) && trim($comment) !== '',
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

    private function pipelinePayload(Pipeline $pipeline): array
    {
        return [
            'id' => $pipeline->id,
            'name' => $pipeline->name,
            'type' => $pipeline->type->value,
            'stages' => $pipeline->stages->map(fn (PipelineStage $s) => [
                'id' => $s->id,
                'system_key' => $s->system_key,
                'name' => $s->name,
                'color' => $s->color,
                'position' => $s->position,
                'is_system' => $s->is_system,
            ])->values(),
        ];
    }

    /**
     * @param  list<int>  $orderIds
     * @return array<int, int>
     */
    private function chatUnreadMapForDesigner(int $designerUserId, array $orderIds): array
    {
        if (
            $orderIds === []
            || $designerUserId < 1
            || ! Schema::hasTable('supplier_order_messages')
            || ! Schema::hasColumn('supplier_order_messages', 'read_by_designer_at')
        ) {
            return [];
        }

        $rows = DB::table('supplier_order_messages as m')
            ->whereIn('m.supplier_order_id', $orderIds)
            ->where('m.sender_user_id', '!=', $designerUserId)
            ->whereNull('m.read_by_designer_at')
            ->select('m.supplier_order_id', DB::raw('COUNT(*) as unread_count'))
            ->groupBy('m.supplier_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->supplier_order_id] = (int) $row->unread_count;
        }

        return $map;
    }
}
