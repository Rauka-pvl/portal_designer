<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSupplierRequest;
use App\Http\Requests\Api\UpdateSupplierRequest;
use App\Http\Resources\Api\SupplierCollection;
use App\Http\Resources\Api\SupplierProductResource;
use App\Http\Resources\Api\SupplierResource;
use App\Services\Crm\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DesignerSupplierApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private SupplierService $suppliers) {}

    public function index(Request $request)
    {
        $this->ensureDesigner($request);
        $list = $this->decorate(
            $this->suppliers->list((int) $request->user()->id, $request->only(['search', 'city', 'sphere', 'favorite', 'status', 'brand'])),
            (int) $request->user()->id
        );

        return new SupplierCollection($list);
    }

    public function show(Request $request, int $supplier)
    {
        $this->ensureDesigner($request);
        $model = $this->decorate(
            collect([$this->suppliers->findVisible((int) $request->user()->id, $supplier)]),
            (int) $request->user()->id
        )->first();

        return new SupplierResource($model);
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->ensureDesigner($request);

        return (new SupplierResource($this->suppliers->create(
            (int) $request->user()->id,
            $request->validated(),
            $request->file('logo')
        )))->response()->setStatusCode(201);
    }

    public function update(UpdateSupplierRequest $request, int $supplier)
    {
        $this->ensureDesigner($request);

        return new SupplierResource($this->suppliers->update(
            (int) $request->user()->id,
            $supplier,
            $request->validated(),
            $request->file('logo')
        ));
    }

    public function destroy(Request $request, int $supplier)
    {
        $this->ensureDesigner($request);
        $this->suppliers->delete((int) $request->user()->id, $supplier);

        return response()->noContent();
    }

    public function toggleFavorite(Request $request, int $supplier)
    {
        $this->ensureDesigner($request);

        return response()->json([
            'data' => [
                'is_favorite' => $this->suppliers->toggleFavorite((int) $request->user()->id, $supplier),
            ],
        ]);
    }

    public function products(Request $request, int $supplier)
    {
        $this->ensureDesigner($request);

        return SupplierProductResource::collection(
            $this->suppliers->products((int) $request->user()->id, $supplier, $request->only(['search', 'category', 'category_id']))
        );
    }

    private function decorate(Collection $suppliers, int $userId): Collection
    {
        $favorites = array_flip($this->suppliers->favoriteIds($userId));

        return $suppliers->each(function ($supplier) use ($userId, $favorites) {
            $supplier->is_favorite = isset($favorites[$supplier->id]);
            $supplier->is_owned_by_designer = (int) $supplier->created_by_user_id === $userId
                || (! $supplier->created_by_user_id && (int) $supplier->user_id === $userId);
        });
    }
}
