<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnsuresDesigner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DashboardQueryRequest;
use App\Http\Resources\DashboardResource;
use App\Services\Crm\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    use EnsuresDesigner;

    public function __construct(private readonly DashboardAnalyticsService $analytics) {}

    public function index(DashboardQueryRequest $request): JsonResponse
    {
        return $this->show($request);
    }

    public function show(DashboardQueryRequest $request): JsonResponse
    {
        $this->ensureDesigner($request);

        return response()->json(['data' => (new DashboardResource($this->analytics->forApi(
            $request->user(),
            $request->input('period', 'month'),
            $request->input('from'),
            $request->input('to'),
            $request->input('timezone'),
        )))->resolve()]);
    }
}
