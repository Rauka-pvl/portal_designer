<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CommunityController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Community feed API for designer and supplier (Sanctum).
 */
class CommunityApiController extends Controller
{
    /** GET /api/community */
    public function index(Request $request): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->apiIndex($request));
    }

    /** GET /api/community/posts/{id} */
    public function show(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->apiShow($request, $id));
    }

    /** GET /api/community/users/{id} */
    public function profile(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->apiProfile($request, $id));
    }

    /** POST /api/community/posts */
    public function store(Request $request): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->store($request));
    }

    /** PUT|PATCH /api/community/posts/{id} */
    public function update(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->update($request, $id));
    }

    /** DELETE /api/community/posts/{id} */
    public function destroy(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->destroy($request, $id));
    }

    /** POST /api/community/posts/{id}/like */
    public function toggleLike(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->toggleLike($request, $id));
    }

    /** POST /api/community/posts/{id}/save */
    public function toggleSave(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->toggleSave($request, $id));
    }

    /** POST /api/community/posts/{id}/comments */
    public function storeComment(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->storeComment($request, $id));
    }

    /** PUT|PATCH /api/community/comments/{id} */
    public function updateComment(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->updateComment($request, $id));
    }

    /** DELETE /api/community/comments/{id} */
    public function destroyComment(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->destroyComment($request, $id));
    }

    /** POST /api/community/posts/{id}/report */
    public function report(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->report($request, $id));
    }

    /** POST /api/community/posts/{id}/hide */
    public function hide(Request $request, int $id): Response
    {
        return $this->forward($request, fn () => app(CommunityController::class)->hide($request, $id));
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

        if ($response instanceof JsonResponse) {
            return $this->normalizeResponse($response);
        }

        if ($response instanceof Response) {
            return $response;
        }

        return response()->json(['success' => true, ...((array) $response)]);
    }

    private function normalizeResponse(JsonResponse $response): JsonResponse
    {
        $data = $response->getData(true);
        if (! is_array($data)) {
            return $response;
        }

        if (array_key_exists('ok', $data)) {
            $data['success'] = (bool) $data['ok'];
            unset($data['ok']);
        }

        unset($data['html']);

        return response()->json($data, $response->getStatusCode());
    }
}
