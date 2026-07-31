<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PercentageProposalRequest;
use App\Http\Requests\Api\StoreSupplyCommentRequest;
use App\Http\Requests\Api\StoreSupplyRequest;
use App\Http\Requests\Api\UpdateSupplyRequest;
use App\Http\Requests\Api\UpdateSupplyStatusRequest;
use App\Http\Resources\Api\SupplyCollection;
use App\Http\Resources\Api\SupplyResource;
use App\Models\Project;
use App\Models\Supplier_orders;
use App\Services\Crm\SupplyService;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class SupplyApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private SupplyService $supplies) {}

    public function index(Request $request, int $project)
    {
        $this->ensureDesigner($request);

        return new SupplyCollection($this->supplies->list($this->project($request, $project)));
    }

    public function store(StoreSupplyRequest $request, int $project)
    {
        $this->ensureDesigner($request);
        $created = $this->supplies->create(
            $this->project($request, $project),
            (int) $request->user()->id,
            $request->validated(),
            $this->files($request)
        );

        return (new SupplyResource($created->load(['project', 'supplier'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $supply)
    {
        $this->ensureDesigner($request);

        return new SupplyResource($this->ownedSupply($request, $supply)->load(['project', 'supplier']));
    }

    public function update(UpdateSupplyRequest $request, int $supply)
    {
        $this->ensureDesigner($request);
        $order = $this->ownedSupply($request, $supply);
        $project = $this->project($request, (int) $order->project_id);

        return new SupplyResource($this->supplies->update($project, $order, $request->validated(), $this->files($request))->load(['project', 'supplier']));
    }

    public function destroy(Request $request, int $supply)
    {
        $this->ensureDesigner($request);
        $this->supplies->delete($this->ownedSupply($request, $supply));

        return response()->noContent();
    }

    public function updateStatus(UpdateSupplyStatusRequest $request, int $supply)
    {
        $this->ensureDesigner($request);

        return new SupplyResource($this->supplies->updateStatus($this->ownedSupply($request, $supply), $request->validated('status')));
    }

    public function send(Request $request, int $supply)
    {
        $this->ensureDesigner($request);
        $order = $this->ownedSupply($request, $supply);

        return new SupplyResource($this->supplies->sendProposal($order, $order->bonus_percent !== null ? (string) $order->bonus_percent : null, $order->offer_message));
    }

    public function comments(Request $request, int $supply)
    {
        $this->ensureDesigner($request);
        $order = $this->ownedSupply($request, $supply);

        return response()->json([
            'data' => array_values(array_filter([
                $order->comment ? [
                    'id' => 1,
                    'body' => (string) $order->comment,
                    'author_type' => 'designer',
                    'created_at' => $order->updated_at?->toIso8601String(),
                ] : null,
            ])),
        ]);
    }

    public function storeComment(StoreSupplyCommentRequest $request, int $supply)
    {
        $this->ensureDesigner($request);
        $order = $this->ownedSupply($request, $supply);
        $order->update(['comment' => $request->validated('comment')]);

        return new SupplyResource($order->fresh(['project', 'supplier']));
    }

    public function sendProposal(PercentageProposalRequest $request, int $supply)
    {
        $this->ensureDesigner($request);
        $data = $request->validated();

        return new SupplyResource($this->supplies->sendProposal(
            $this->ownedSupply($request, $supply),
            $data['bonus_percent'] ?? $data['percentage'] ?? null,
            $data['message'] ?? null
        ));
    }

    public function acceptProposal(Request $request, int $supply)
    {
        $this->ensureDesigner($request);

        return new SupplyResource($this->supplies->acceptProposal($this->ownedSupply($request, $supply)));
    }

    public function rejectProposal(Request $request, int $supply)
    {
        $this->ensureDesigner($request);

        return new SupplyResource($this->supplies->rejectProposal($this->ownedSupply($request, $supply)));
    }

    public function counterProposal(PercentageProposalRequest $request, int $supply)
    {
        $this->ensureDesigner($request);
        $data = $request->validated();
        $percent = $data['bonus_percent'] ?? $data['percentage'] ?? null;
        abort_unless($percent !== null, 422, 'bonus_percent is required.');

        return new SupplyResource($this->supplies->counterProposal(
            $this->ownedSupply($request, $supply),
            (string) $percent,
            $data['message'] ?? null
        ));
    }

    /** @return list<\Illuminate\Http\UploadedFile> */
    private function files(Request $request): array
    {
        $files = $request->file('files', []);

        return is_array($files) ? array_values(array_filter($files)) : ($files ? [$files] : []);
    }

    private function project(Request $request, int $id): Project
    {
        return WorkspaceAccess::scopeProjects(Project::query(), $request->user())->findOrFail($id);
    }

    private function ownedSupply(Request $request, int $id): Supplier_orders
    {
        $order = Supplier_orders::query()->findOrFail($id);
        $this->project($request, (int) $order->project_id);

        return $order;
    }
}
