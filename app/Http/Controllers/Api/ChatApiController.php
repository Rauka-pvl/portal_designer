<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SupplierOrderChatController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Order chat API for designer and supplier (Sanctum).
 */
class ChatApiController extends Controller
{
    /** GET /api/supplier-orders/{id}/chat/messages */
    public function messages(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderChatController::class)->messages($request, $id));
    }

    /** POST /api/supplier-orders/{id}/chat/messages */
    public function store(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderChatController::class)->store($request, $id));
    }

    /** POST /api/supplier-orders/{id}/chat/read */
    public function markRead(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderChatController::class)->markRead($request, $id));
    }

    /** GET /api/supplier-orders/chat/unread-map */
    public function unreadMap(Request $request): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderChatController::class)->unreadMap($request));
    }

    /** GET /api/chat/unread-count */
    public function unreadCount(Request $request): Response
    {
        return $this->forward($request, fn () => app(SupplierOrderChatController::class)->unreadCount($request));
    }

    /**
     * @param  callable(): mixed  $action
     */
    private function forward(Request $request, callable $action): Response
    {
        $role = (string) ($request->user()->role ?? '');
        if (! in_array($role, ['designer', 'supplier'], true)) {
            abort(403, 'Only designer or supplier');
        }

        $request->headers->set('Accept', 'application/json');
        if (! $request->headers->has('X-Requested-With')) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        $response = $action();

        if ($response instanceof Response) {
            return $response;
        }

        return response()->json($response);
    }
}
