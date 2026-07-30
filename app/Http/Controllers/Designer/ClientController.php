<?php

namespace App\Http\Controllers\Designer;

use App\Enums\PipelineType;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Pipeline;
use App\Models\Project;
use App\Support\AccountPermissions;
use App\Support\PublicFileStorage;
use App\Services\Crm\PipelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    private function clientForUserOrFail(Request $request, int $clientId): Client
    {
        return Client::where('user_id', $request->user()->id)->findOrFail($clientId);
    }

    public function index(Request $request)
    {
        app(PipelineService::class)->ensureDefaultsForUser((int) $request->user()->id);

        $clients = Client::where('user_id', $request->user()->id)
            ->withCount([
                'objects as count_objects',
                'crmProjects as projects_count',
            ])
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->withSum('crmProjects as projects_budget', 'planned_cost')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Client $client) => $this->payload($client))
            ->values();

        $pipeline = Pipeline::defaultForUser((int) $request->user()->id, PipelineType::Client);

        return view('designer.clients.index', [
            'clientsData' => $clients,
            'pipeline' => $this->pipelinePayload($pipeline),
            'canManagePipeline' => AccountPermissions::canManageClientPipeline($request->user()),
        ]);
    }

    /**
     * Возвращает список клиентов для живого поиска (AJAX).
     */
    public function search(Request $request)
    {
        $query = Client::query()
            ->where('user_id', $request->user()->id)
            ->withCount([
                'objects as count_objects',
                'crmProjects as projects_count',
            ])
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->withSum('crmProjects as projects_budget', 'planned_cost');

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $projectsFilter = (string) $request->query('projects', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('full_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('comment', 'like', $like)
                    ->orWhere('link', 'like', $like)
                    ->orWhereHas('crmProjects', fn ($pq) => $pq->where('name', 'like', $like));
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if (in_array($type, ['person', 'company'], true)) {
            $query->where('client_type', $type);
        }

        if ($projectsFilter === 'with') {
            $query->has('crmProjects');
        } elseif ($projectsFilter === 'without') {
            $query->doesntHave('crmProjects');
        }

        $clients = $query
            ->orderByDesc('id')
            ->get()
            ->map(fn (Client $client) => $this->payload($client));

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Страница "подробнее" / JSON для CRM-карточки.
     */
    public function show(Request $request, int $clientId)
    {
        $client = $this->clientForUserOrFail($request, $clientId);
        $client->loadCount([
            'objects as count_objects',
            'crmProjects as projects_count',
        ]);
        $client->loadSum('objects as sum_repair_budget_planned', 'repair_budget_planned');
        $client->loadSum('crmProjects as projects_budget', 'planned_cost');

        $relatedProjects = Project::query()
            ->where('user_id', (int) $request->user()->id)
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('object', fn ($oq) => $oq->where('client_id', $client->id));
            })
            ->with(['stages.steps'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Project $p) => $this->projectBrief($p))
            ->values();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'client' => $this->payload($client),
                'projects' => $relatedProjects,
            ]);
        }

        return redirect()->route('clients.index', ['open' => $client->id]);
    }

    /**
     * Создание / обновление клиента.
     */
    public function save(Request $request)
    {
        if ($request->has('client_id') && $request->input('client_id') === '') {
            $request->merge(['client_id' => null]);
        }

        $allowedStatuses = $this->allowedStatusKeys((int) $request->user()->id);

        $data = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'full_name' => ['required', 'string', 'max:255'],
            'client_type' => ['required', Rule::in(['person', 'company'])],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'status' => ['required', 'string', 'max:64', Rule::in($allowedStatuses)],
            'comment' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:255'],
            'files' => ['nullable'],
        ]);

        $userId = $request->user()->id;

        $clientId = $data['client_id'] ?? null;
        $isUpdate = (bool) $clientId;
        $client = null;

        if ($clientId) {
            $client = Client::where('user_id', $userId)->findOrFail($clientId);
        } else {
            $client = new Client;
            $client->user_id = $userId;
        }

        $existingFiles = array_values(array_filter(array_map(
            fn ($v) => trim((string) $v),
            (array) $request->input('existing_files', [])
        )));

        $uploadedPaths = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploaded = is_array($files) ? $files : [$files];
            foreach ($uploaded as $file) {
                if ($file) {
                    $uploadedPaths[] = PublicFileStorage::store($file, 'clients');
                }
            }
        }

        $oldPaths = [];
        if (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $oldPaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
            }
        } elseif (! empty($client->file_path)) {
            $oldPaths = [(string) $client->file_path];
        }

        $newPaths = array_values(array_unique(array_merge($existingFiles, $uploadedPaths)));
        foreach ($oldPaths as $oldPath) {
            if ($oldPath !== '' && ! in_array($oldPath, $newPaths, true)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $client->file_paths = $newPaths !== [] ? json_encode($newPaths, JSON_UNESCAPED_SLASHES) : null;
        $client->file_path = $newPaths[0] ?? null;

        $client->full_name = $data['full_name'];
        $client->client_type = $data['client_type'] ?? 'person';
        $client->phone = $data['phone'];
        $client->email = $data['email'];
        $client->status = $data['status'];
        $client->comment = $data['comment'] ?? null;
        $client->link = $data['link'] ?? null;
        $client->save();

        $client->loadCount([
            'objects as count_objects',
            'crmProjects as projects_count',
        ]);
        $client->loadSum('objects as sum_repair_budget_planned', 'repair_budget_planned');
        $client->loadSum('crmProjects as projects_budget', 'planned_cost');

        $message = $isUpdate ? __('clients.saved') : __('clients.added');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'client' => $this->payload($client),
            ]);
        }

        return redirect()
            ->route('clients.index', ['open' => $client->id])
            ->with('status', $message);
    }

    public function updateStatus(Request $request, int $clientId)
    {
        $allowedStatuses = $this->allowedStatusKeys((int) $request->user()->id);

        $data = $request->validate([
            'status' => ['required', 'string', 'max:64', Rule::in($allowedStatuses)],
        ]);

        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);
        $client->status = $data['status'];
        $client->save();

        $client->loadCount([
            'objects as count_objects',
            'crmProjects as projects_count',
        ]);
        $client->loadSum('objects as sum_repair_budget_planned', 'repair_budget_planned');
        $client->loadSum('crmProjects as projects_budget', 'planned_cost');

        return response()->json([
            'success' => true,
            'client' => $this->payload($client),
        ]);
    }

    public function destroy(Request $request, int $clientId)
    {
        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);

        $projectsCount = (int) $client->crmProjects()->count()
            + (int) $client->projects()->count();

        if ($projectsCount > 0 && ! $request->boolean('confirm')) {
            return response()->json([
                'success' => false,
                'needs_confirm' => true,
                'projects_count' => $projectsCount,
                'message' => __('clients.delete_with_projects', ['count' => $projectsCount]),
            ], 422);
        }

        $client->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function deleteFile(Request $request, int $clientId, int $fileIndex)
    {
        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);

        $filePaths = [];
        if (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $filePaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
            }
        }
        if (empty($filePaths) && ! empty($client->file_path)) {
            $filePaths = [$client->file_path];
        }

        if ($fileIndex < 0 || $fileIndex >= count($filePaths)) {
            return response()->json([
                'success' => false,
                'message' => __('clients.error'),
            ], 422);
        }

        $pathToDelete = $filePaths[$fileIndex] ?? null;
        if ($pathToDelete) {
            Storage::disk('public')->delete($pathToDelete);
        }

        array_splice($filePaths, $fileIndex, 1);

        if (! empty($filePaths)) {
            $client->file_paths = json_encode(array_values($filePaths), JSON_UNESCAPED_SLASHES);
            $client->file_path = $filePaths[0] ?? null;
        } else {
            $client->file_paths = null;
            $client->file_path = null;
        }

        $client->save();

        $client->loadCount([
            'objects as count_objects',
            'crmProjects as projects_count',
        ]);
        $client->loadSum('objects as sum_repair_budget_planned', 'repair_budget_planned');
        $client->loadSum('crmProjects as projects_budget', 'planned_cost');

        return response()->json([
            'success' => true,
            'client' => $this->payload($client),
        ]);
    }

    private function allowedStatusKeys(int $userId): array
    {
        app(PipelineService::class)->ensureDefaultsForUser($userId);
        $pipeline = Pipeline::defaultForUser($userId, PipelineType::Client);

        $keys = $pipeline->stages()->orderBy('position')->pluck('system_key')->filter()->values()->all();

        return $keys !== [] ? $keys : ['new', 'in_work', 'not_working'];
    }

    private function pipelinePayload(Pipeline $pipeline): array
    {
        return [
            'id' => $pipeline->id,
            'name' => $pipeline->name,
            'type' => $pipeline->type->value,
            'stages' => $pipeline->stages->map(fn ($s) => [
                'id' => $s->id,
                'system_key' => $s->system_key,
                'name' => $s->name,
                'color' => $s->color,
                'position' => $s->position,
                'is_system' => $s->is_system,
            ])->values(),
        ];
    }

    private function payload(Client $client): array
    {
        $filePaths = [];
        if (is_array($client->file_paths)) {
            $filePaths = array_values(array_filter($client->file_paths, fn ($p) => is_string($p) && $p !== ''));
        } elseif (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $filePaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
            }
        }
        if (empty($filePaths) && ! empty($client->file_path)) {
            $filePaths = [$client->file_path];
        }

        $countObjects = isset($client->count_objects)
            ? (int) $client->count_objects
            : $client->objects()->count();
        $projectsCount = isset($client->projects_count)
            ? (int) $client->projects_count
            : $client->crmProjects()->count();
        $sumRepairBudgetPlanned = isset($client->sum_repair_budget_planned)
            ? (float) $client->sum_repair_budget_planned
            : (float) $client->objects()->sum('repair_budget_planned');
        $projectsBudget = isset($client->projects_budget)
            ? (float) $client->projects_budget
            : (float) $client->crmProjects()->sum('planned_cost');

        return [
            'id' => $client->id,
            'full_name' => $client->full_name,
            'client_type' => $client->client_type ?: 'person',
            'phone' => $client->phone,
            'email' => $client->email,
            'status' => $client->status,
            'comment' => $client->comment,
            'link' => $client->link,
            'file_path' => $client->file_path,
            'file_paths' => $filePaths,
            'count_objects' => $countObjects,
            'sum_repair_budget_planned' => $sumRepairBudgetPlanned,
            'projects_count' => $projectsCount,
            'projects_budget' => $projectsBudget,
            'updated_at' => optional($client->updated_at)?->toIso8601String(),
            'created_at' => optional($client->created_at)?->toIso8601String(),
        ];
    }

    private function projectBrief(Project $project): array
    {
        $stages = $project->stages ?? collect();
        $stepsTotal = 0;
        $stepsDone = 0;
        foreach ($stages as $stage) {
            foreach ($stage->steps ?? [] as $step) {
                $stepsTotal++;
                if (($step->result_status ?? '') === 'done') {
                    $stepsDone++;
                }
            }
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'planned_end_date' => $project->planned_end_date
                ? (string) $project->planned_end_date
                : null,
            'planned_cost' => (float) ($project->planned_cost ?? 0),
            'actual_cost' => (float) ($project->actual_cost ?? 0),
            'checklist_progress' => [
                'done' => $stepsDone,
                'total' => $stepsTotal,
                'percent' => $stepsTotal ? (int) round(($stepsDone / $stepsTotal) * 100) : 0,
            ],
        ];
    }
}
