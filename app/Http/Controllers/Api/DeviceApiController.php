<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
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

        $user = $request->user();
        $provider = $data['provider'] ?? 'expo';
        $app = $data['app'] ?? 'mobile';

        $device = Device::query()->where('token', $data['token'])->first();

        if ($device) {
            $device->forceFill([
                'user_id' => $user->id,
                'platform' => $data['platform'] ?? $device->platform,
                'provider' => $provider,
                'app' => $app,
                'last_used_at' => now(),
            ])->save();
        } else {
            $device = Device::query()->create([
                'user_id' => $user->id,
                'token' => $data['token'],
                'platform' => $data['platform'] ?? null,
                'provider' => $provider,
                'app' => $app,
                'last_used_at' => now(),
            ]);
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
