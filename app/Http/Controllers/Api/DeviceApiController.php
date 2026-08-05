<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Support\DeviceRegistrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceApiController extends Controller
{
    /**
     * POST /api/devices
     * POST /api/push-tokens (alias)
     *
     * Body: token, platform?, provider?, app?
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'min:8', 'max:512'],
            'platform' => ['nullable', 'string', Rule::in(['ios', 'android', 'web'])],
            'provider' => ['nullable', 'string', Rule::in(['expo', 'fcm', 'apns'])],
            'app' => ['nullable', 'string', 'max:64'],
        ]);

        $device = DeviceRegistrar::upsertFromRequestData($request->user(), $data);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid push token',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'token' => $device->token,
                'platform' => $device->platform,
                'provider' => $device->provider,
                'app' => $device->app,
            ],
        ], $device->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * DELETE /api/devices
     * DELETE /api/push-tokens (alias)
     *
     * Body: { "token": "ExponentPushToken[...]" }
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $deleted = Device::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted > 0,
        ]);
    }
}
