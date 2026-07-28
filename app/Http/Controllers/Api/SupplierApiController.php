<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Supplier\SupplierPortalController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Supplier-facing Sanctum API (orders + offer negotiation).
 * Delegates to SupplierPortalController (same business rules as web).
 */
class SupplierApiController extends Controller
{
    /** GET /api/supplier/orders */
    public function orders(Request $request): Response
    {
        return $this->forward($request, fn () => app(SupplierPortalController::class)->apiOrders($request));
    }

    /** GET /api/supplier/orders/{id} */
    public function order(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierPortalController::class)->apiOrder($request, $id));
    }

    /** POST /api/supplier/orders/{id}/offer/accept */
    public function acceptOffer(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierPortalController::class)->acceptOffer($request, $id));
    }

    /** POST /api/supplier/orders/{id}/offer/reject */
    public function rejectOffer(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierPortalController::class)->rejectOffer($request, $id));
    }

    /** POST /api/supplier/orders/{id}/offer/counter */
    public function counterOffer(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierPortalController::class)->counterOffer($request, $id));
    }

    /**
     * @param  callable(): mixed  $action
     */
    private function forward(Request $request, callable $action): Response
    {
        $this->ensureSupplier($request);
        $this->forceJson($request);

        $response = $action();

        if ($response instanceof Response) {
            return $response;
        }

        return response()->json($response);
    }

    private function ensureSupplier(Request $request): void
    {
        if ((string) ($request->user()->role ?? '') !== 'supplier') {
            abort(403, 'Only supplier portal');
        }
    }

    private function forceJson(Request $request): void
    {
        $request->headers->set('Accept', 'application/json');
        if (! $request->headers->has('X-Requested-With')) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }
    }
}
