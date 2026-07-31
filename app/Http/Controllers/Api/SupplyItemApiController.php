<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSupplyItemRequest;
use App\Http\Requests\Api\UpdateSupplyItemRequest;
use App\Http\Resources\Api\SupplyItemResource;
use App\Models\Supplier_orders;
use App\Models\SupplierProduct;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;

class SupplyItemApiController extends Controller
{
    use EnsuresDesigner;

    public function store(StoreSupplyItemRequest $request, int $supply)
    {
        $this->ensureDesigner($request);
        $order = $this->supply($request, $supply);
        $items = array_values((array) $order->product_items);
        $items[] = $this->product($order, $request->validated());
        $order->update(['product_items' => $items]);

        return (new SupplyItemResource(end($items) + ['id' => count($items) - 1]))->response()->setStatusCode(201);
    }

    public function updateTop(UpdateSupplyItemRequest $request, int $item)
    {
        $this->ensureDesigner($request);
        $supplyId = (int) $request->input('supply_id', $request->query('supply_id'));
        abort_unless($supplyId > 0, 422, 'supply_id is required.');
        $order = $this->supply($request, $supplyId);
        $items = array_values((array) $order->product_items);
        abort_unless(isset($items[$item]), 404);
        $items[$item] = $this->product($order, $request->validated());
        $order->update(['product_items' => $items]);

        return new SupplyItemResource($items[$item] + ['id' => $item]);
    }

    public function destroyTop(Request $request, int $item)
    {
        $this->ensureDesigner($request);
        $supplyId = (int) $request->input('supply_id', $request->query('supply_id'));
        abort_unless($supplyId > 0, 422, 'supply_id is required.');
        $order = $this->supply($request, $supplyId);
        $items = array_values((array) $order->product_items);
        abort_unless(isset($items[$item]), 404);
        array_splice($items, $item, 1);
        $order->update(['product_items' => $items]);

        return response()->noContent();
    }

    private function supply(Request $request, int $id): Supplier_orders
    {
        $order = Supplier_orders::query()->with('project')->findOrFail($id);
        abort_unless($order->project && WorkspaceAccess::canAccessProject($request->user(), $order->project), 404);

        return $order;
    }

    private function product(Supplier_orders $supply, array $data): array
    {
        $product = SupplierProduct::query()
            ->where('supplier_id', $supply->supplier_id)
            ->findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? $data['qty'] ?? 1;
        $price = $data['price'] ?? $product->price;

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'qty' => (string) $qty,
            'quantity' => (string) $qty,
            'price' => number_format((float) $price, 2, '.', ''),
            'unit' => $product->unit,
            'total' => number_format((float) $qty * (float) $price, 2, '.', ''),
        ];
    }
}
