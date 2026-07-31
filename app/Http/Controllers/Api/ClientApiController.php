<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreClientRequest;
use App\Http\Requests\Api\UpdateClientRequest;
use App\Http\Resources\Api\ClientCollection;
use App\Http\Resources\Api\ClientResource;
use App\Models\Client;
use App\Models\Project;
use App\Services\Crm\ClientService;
use App\Support\Api\ApiQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private readonly ClientService $clients) {}

    public function index(Request $request): ClientCollection
    {
        $this->ensureDesigner($request);

        $query = Client::query()
            ->where('user_id', $request->user()->id)
            ->withCount('crmProjects as projects_count')
            ->withSum('crmProjects as projects_budget', 'planned_cost');

        ApiQuery::applySearch($query, $request->query('search'), [
            'full_name', 'phone', 'email', 'comment', 'link',
        ], fn ($query, string $like) => $query->orWhereHas(
            'crmProjects',
            fn ($projectQuery) => $projectQuery->where('name', 'like', $like)
        ));

        if (($status = $request->query('status')) !== null && $status !== '') {
            $query->where('status', $status);
        }

        if (in_array($request->query('type'), ['person', 'company'], true)) {
            $query->where('client_type', $request->query('type'));
        }

        if ($request->boolean('has_projects')) {
            $query->has('crmProjects');
        } elseif ($request->boolean('without_projects')) {
            $query->doesntHave('crmProjects');
        }

        ApiQuery::applySort($query, $request->query('sort'), [
            'id' => 'id',
            'name' => 'full_name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ]);

        return new ClientCollection(ApiQuery::applyPagination($query, $request));
    }

    public function store(StoreClientRequest $request): ClientResource
    {
        $this->ensureDesigner($request);
        $files = $request->file('files', []);
        $client = $this->clients->save(
            (int) $request->user()->id,
            $request->validated(),
            [],
            is_array($files) ? $files : [$files]
        );

        return new ClientResource($this->clients->loadAggregates($client));
    }

    public function show(Request $request, int $client): ClientResource
    {
        $this->ensureDesigner($request);
        $client = $this->clientForUserOrFail($request, $client);
        $projects = $this->relatedProjects($request, $client);
        $client->setRelation('projectsForApi', $projects);

        return new ClientResource($this->clients->loadAggregates($client));
    }

    public function update(UpdateClientRequest $request, int $client): ClientResource
    {
        $this->ensureDesigner($request);
        $data = $request->validated();
        $data['client_id'] = $client;
        $files = $request->file('files', []);
        $client = $this->clients->save(
            (int) $request->user()->id,
            $data,
            array_values($request->input('existing_files', [])),
            is_array($files) ? $files : [$files]
        );

        return new ClientResource($this->clients->loadAggregates($client));
    }

    public function destroy(Request $request, int $client): JsonResponse
    {
        $this->ensureDesigner($request);
        $client = $this->clientForUserOrFail($request, $client);
        $projectsCount = $this->clients->destroy($client, $request->boolean('confirm'));

        if ($projectsCount !== null) {
            throw ValidationException::withMessages([
                'confirm' => [__('clients.delete_with_projects', ['count' => $projectsCount])],
            ]);
        }

        return response()->json(['data' => null]);
    }

    public function projects(Request $request, int $client): JsonResponse
    {
        $this->ensureDesigner($request);
        $client = $this->clientForUserOrFail($request, $client);
        $projects = $this->relatedProjects($request, $client)->map(fn (Project $project) => [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'planned_end_date' => $project->planned_end_date
                ? \Illuminate\Support\Carbon::parse($project->planned_end_date)->toIso8601String()
                : null,
            'planned_cost' => \App\Support\Api\Money::formatMoney($project->planned_cost) ?? '0.00',
            'actual_cost' => \App\Support\Api\Money::formatMoney($project->actual_cost) ?? '0.00',
        ])->values();

        return response()->json(['data' => $projects]);
    }

    public function storeFiles(Request $request, int $client): ClientResource
    {
        $this->ensureDesigner($request);
        $request->validate(['files' => ['required', 'array'], 'files.*' => ['file']]);
        $client = $this->clientForUserOrFail($request, $client);
        $this->clients->syncFiles($client, $this->clients->filePaths($client), $request->file('files'));

        return new ClientResource($this->clients->loadAggregates($client));
    }

    public function destroyFile(Request $request, int $client, int $file): ClientResource
    {
        $this->ensureDesigner($request);
        $client = $this->clientForUserOrFail($request, $client);

        if (! $this->clients->deleteFile($client, $file)) {
            throw ValidationException::withMessages(['file' => [__('clients.error')]]);
        }

        return new ClientResource($this->clients->loadAggregates($client));
    }

    private function clientForUserOrFail(Request $request, int $clientId): Client
    {
        return Client::where('user_id', $request->user()->id)->findOrFail($clientId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Project>
     */
    private function relatedProjects(Request $request, Client $client)
    {
        return Project::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($client): void {
                $query->where('client_id', $client->id)
                    ->orWhereHas('object', fn ($objectQuery) => $objectQuery->where('client_id', $client->id));
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }
}
