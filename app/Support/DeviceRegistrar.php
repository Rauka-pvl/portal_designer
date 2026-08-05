<?php

namespace App\Support;

use App\Models\Device;
use App\Models\User;

class DeviceRegistrar
{
    /**
     * Upsert an Expo/FCM device push token for a user.
     *
     * @param  array{token?: string, push_token?: string, device_token?: string, platform?: string|null, provider?: string|null, app?: string|null, device?: array<string, mixed>|null}  $input
     */
    public static function upsertFromRequestData(User $user, array $input): ?Device
    {
        $nested = is_array($input['device'] ?? null) ? $input['device'] : [];

        $token = trim((string) (
            $input['push_token']
            ?? $input['device_token']
            ?? $nested['token']
            ?? $nested['push_token']
            ?? $input['token']
            ?? ''
        ));

        if (! self::isLikelyPushToken($token)) {
            return null;
        }

        $platform = $input['platform'] ?? $nested['platform'] ?? null;
        $provider = $input['provider'] ?? $nested['provider'] ?? 'expo';
        $app = $input['app'] ?? $nested['app'] ?? 'mobile';

        if (! in_array($platform, ['ios', 'android', 'web', null], true)) {
            $platform = null;
        }

        if (! in_array($provider, ['expo', 'fcm', 'apns'], true)) {
            $provider = 'expo';
        }

        $app = is_string($app) && $app !== '' ? mb_substr($app, 0, 64) : 'mobile';

        $device = Device::query()->where('token', $token)->first();

        if ($device) {
            $device->forceFill([
                'user_id' => $user->id,
                'platform' => $platform ?? $device->platform,
                'provider' => $provider,
                'app' => $app,
                'last_used_at' => now(),
            ])->save();

            return $device;
        }

        return Device::query()->create([
            'user_id' => $user->id,
            'token' => $token,
            'platform' => $platform,
            'provider' => $provider,
            'app' => $app,
            'last_used_at' => now(),
        ]);
    }

    private static function isLikelyPushToken(string $token): bool
    {
        if ($token === '' || strlen($token) < 8) {
            return false;
        }

        if (str_contains($token, 'ExponentPushToken')) {
            return true;
        }

        // Generic FCM / APNs style tokens
        return strlen($token) >= 32 && (bool) preg_match('/^[A-Za-z0-9_\\-:]+$/', $token);
    }
}
