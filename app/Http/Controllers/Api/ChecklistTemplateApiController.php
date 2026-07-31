<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreChecklistTemplateRequest;
use App\Http\Requests\Api\UpdateChecklistTemplateRequest;
use App\Http\Resources\Api\ChecklistTemplateResource;
use App\Models\Template;
use App\Services\Crm\ChecklistService;
use Illuminate\Http\Request;

class ChecklistTemplateApiController extends Controller
{
    public function __construct(private ChecklistService $checklists) {}
    public function index(Request $request)
    {
        $q = Template::query()->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $request->user()->id));
        if ($request->filled('stage_id') || $request->filled('stage_type')) {
            $q->where('type', $request->query('stage_type', $request->query('stage_id')));
        }
        if ($search = trim((string) $request->query('search', ''))) {
            $q->where('name', 'like', '%'.$search.'%');
        }

        return ChecklistTemplateResource::collection($q->orderBy('name')->get());
    }

    public function store(StoreChecklistTemplateRequest $request)
    {
        return (new ChecklistTemplateResource($this->checklists->createTemplate((int) $request->user()->id, $request->validated())))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        $template = Template::query()
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $request->user()->id))
            ->findOrFail($id);

        return new ChecklistTemplateResource($template);
    }

    public function update(UpdateChecklistTemplateRequest $request, int $id)
    {
        return new ChecklistTemplateResource($this->checklists->updateTemplate($this->owned($request, $id), $request->validated()));
    }

    public function destroy(Request $request, int $id)
    {
        $this->owned($request, $id)->delete();

        return response()->noContent();
    }

    private function owned(Request $request, int $id): Template
    {
        return Template::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }
}
