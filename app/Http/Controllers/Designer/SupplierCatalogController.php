<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Review;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierCatalogController extends Controller
{
    public function index(Request $request, int $supplierId): View
    {
        $userId = (int) $request->user()->id;
        $supplier = $this->visibleSupplierOrFail($userId, $supplierId);

        $products = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('id')
            ->get();

        return view('designer.suppliers.products.index', [
            'supplier' => $supplier,
            'products' => $products,
            'ratingSummary' => Review::supplierRatingSummary((int) $supplier->id),
            'projects' => $this->projectsForDesigner($userId),
            'categoryOptions' => $this->categoryOptions(),
            'roomOptions' => $this->roomOptions(),
        ]);
    }

    public function show(Request $request, int $supplierId, int $productId): View
    {
        $userId = (int) $request->user()->id;
        $supplier = $this->visibleSupplierOrFail($userId, $supplierId);

        $product = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->where('id', $productId)
            ->firstOrFail();

        return view('designer.suppliers.products.show', [
            'supplier' => $supplier,
            'product' => $product,
        ]);
    }

    /**
     * JSON catalog for in-project supply modal (reuses supplier_products, no new tables).
     */
    public function productsJson(Request $request, int $supplierId)
    {
        $userId = (int) $request->user()->id;
        $supplier = $this->visibleSupplierOrFail($userId, $supplierId);

        $q = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('description', 'like', '%'.$q.'%');
            });
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $paginator = $query->paginate(min(48, max(12, (int) $request->query('per_page', 24))));

        $categories = SupplierProduct::query()
            ->where('supplier_id', $supplier->id)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'success' => true,
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
            ],
            'categories' => $categories,
            'data' => collect($paginator->items())->map(fn (SupplierProduct $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category,
                'description' => $p->description,
                'price' => $p->price !== null ? (float) $p->price : 0,
                'unit' => $p->unit,
                'image_url' => $p->image_url,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function visibleSupplierOrFail(int $designerUserId, int $supplierId): Supplier
    {
        $supplier = Supplier::query()->findOrFail($supplierId);

        $isOwned = (int) ($supplier->created_by_user_id ?? 0) === $designerUserId
            || ((int) ($supplier->created_by_user_id ?? 0) < 1 && (int) ($supplier->user_id ?? 0) === $designerUserId);
        $isPublicApproved = (string) $supplier->profile_status === 'active'
            && (string) $supplier->moderation_status === 'approved';

        if (! $isOwned && ! $isPublicApproved) {
            abort(404);
        }

        return $supplier;
    }

    private function projectsForDesigner(int $userId)
    {
        return Project::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function categoryOptions(): array
    {
        $all = trans('categories');

        return is_array($all) ? $all : [];
    }

    private function roomOptions(): array
    {
        $all = trans('type_room');

        return is_array($all) ? $all : [];
    }
}
