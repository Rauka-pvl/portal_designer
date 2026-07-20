<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Designer\ClientController;
use App\Http\Controllers\Designer\PassportObject;
use App\Http\Controllers\Designer\ProjectController;
use App\Http\Controllers\Designer\SupplierController;
use App\Http\Controllers\Designer\SupplierOrderController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Write API for the designer mobile/React client.
 * Delegates to existing web Designer controllers (same validation & business rules).
 */
class DesignerCrudController extends Controller
{
    // ─── Clients ───────────────────────────────────────────

    /** POST /api/clients */
    public function storeClient(Request $request): Response
    {
        return $this->forward($request, fn () => app(ClientController::class)->save($request));
    }

    /** PUT|PATCH /api/clients/{id} */
    public function updateClient(Request $request, int $id): Response
    {
        $request->merge(['client_id' => $id]);

        return $this->forward($request, fn () => app(ClientController::class)->save($request));
    }

    /** DELETE /api/clients/{id} */
    public function destroyClient(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(ClientController::class)->destroy($request, $id));
    }

    // ─── Objects ───────────────────────────────────────────

    /** POST /api/objects */
    public function storeObject(Request $request): Response
    {
        return $this->forward($request, fn () => app(PassportObject::class)->save($request));
    }

    /** PUT|PATCH /api/objects/{id} */
    public function updateObject(Request $request, int $id): Response
    {
        $request->merge(['object_id' => $id]);

        return $this->forward($request, fn () => app(PassportObject::class)->save($request));
    }

    /** DELETE /api/objects/{id} */
    public function destroyObject(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(PassportObject::class)->destroy($request, $id));
    }

    // ─── Projects ──────────────────────────────────────────

    /** POST /api/projects */
    public function storeProject(Request $request): Response
    {
        return $this->forward($request, fn () => app(ProjectController::class)->store($request));
    }

    /** PUT|PATCH /api/projects/{id} */
    public function updateProject(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(ProjectController::class)->update($request, $id));
    }

    /** DELETE /api/projects/{id} */
    public function destroyProject(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(ProjectController::class)->destroy($request, $id));
    }

    // ─── Supplier orders ───────────────────────────────────

    /** POST /api/supplier-orders */
    public function storeSupplierOrder(Request $request): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderController::class)->store($request));
    }

    /** PUT|PATCH /api/supplier-orders/{id} */
    public function updateSupplierOrder(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderController::class)->update($request, $id));
    }

    /** DELETE /api/supplier-orders/{id} */
    public function destroySupplierOrder(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderController::class)->destroy($request, $id));
    }

    // ─── Suppliers ─────────────────────────────────────────

    /** POST /api/suppliers */
    public function storeSupplier(Request $request): Response
    {
        return $this->forward($request, fn () => app(SupplierController::class)->store($request));
    }

    /** PUT|PATCH /api/suppliers/{id} */
    public function updateSupplier(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierController::class)->update($request, $id));
    }

    /** DELETE /api/suppliers/{id} */
    public function destroySupplier(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierController::class)->destroy($request, $id));
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * @param  callable(): mixed  $action
     */
    private function forward(Request $request, callable $action): Response
    {
        $this->ensureDesigner($request);
        $this->forceJson($request);

        $response = $action();

        if ($response instanceof Response) {
            return $response;
        }

        return response()->json($response);
    }

    private function ensureDesigner(Request $request): void
    {
        $role = (string) ($request->user()->role ?? '');

        if (! in_array($role, ['designer', 'moderator'], true)) {
            abort(403, 'Only designer portal');
        }
    }

    private function forceJson(Request $request): void
    {
        $request->headers->set('Accept', 'application/json');
        // Laravel treats X-Requested-With as AJAX; helps expectsJson()/wantsJson().
        if (! $request->headers->has('X-Requested-With')) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }
    }
}
