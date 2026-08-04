<?php

namespace App\Services\Push;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    private const CHUNK_SIZE = 100;

    /**
     * @param  list<string>|string  $to
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, tickets: list<array<string, mixed>>, errors: list<string>}
     */
    public function send(
        array|string $to,
        string $title,
        string $body,
        array $data = [],
        ?string $sound = 'default',
    ): array {
        $tokens = collect(is_array($to) ? $to : [$to])
            ->filter(fn ($token) => is_string($token) && $token !== '')
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return ['ok' => true, 'tickets' => [], 'errors' => []];
        }

        $messages = $tokens->map(function (string $token) use ($title, $body, $data, $sound) {
            $message = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data === [] ? (object) [] : $data,
            ];

            if ($sound !== null) {
                $message['sound'] = $sound;
            }

            return $message;
        });

        $tickets = [];
        $errors = [];

        foreach ($messages->chunk(self::CHUNK_SIZE) as $chunk) {
            $payload = $chunk->values()->all();

            try {
                $response = Http::acceptJson()
                    ->asJson()
                    ->timeout(15)
                    ->post(self::ENDPOINT, $payload);

                if (! $response->successful()) {
                    $errors[] = 'Expo HTTP '.$response->status();
                    Log::warning('expo.push.http_error', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);

                    continue;
                }

                $chunkTickets = $response->json('data') ?? [];
                if (! is_array($chunkTickets)) {
                    $chunkTickets = [];
                }

                foreach ($chunkTickets as $index => $ticket) {
                    if (! is_array($ticket)) {
                        continue;
                    }

                    $tickets[] = $ticket;
                    $token = $payload[$index]['to'] ?? null;

                    if (($ticket['status'] ?? null) === 'error') {
                        $errors[] = (string) ($ticket['message'] ?? 'Expo ticket error');
                        $this->pruneInvalidToken($token, $ticket);
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                Log::warning('expo.push.exception', ['message' => $e->getMessage()]);
            }
        }

        return [
            'ok' => $errors === [],
            'tickets' => $tickets,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, tickets: list<array<string, mixed>>, errors: list<string>}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = Device::query()
            ->where('user_id', $user->id)
            ->where('provider', 'expo')
            ->pluck('token')
            ->all();

        return $this->send($tokens, $title, $body, $data);
    }

    /**
     * @param  Collection<int, User>|list<User|int>  $users
     * @param  array<string, mixed>  $data
     * @return array{ok: bool, tickets: list<array<string, mixed>>, errors: list<string>}
     */
    public function sendToUsers(Collection|array $users, string $title, string $body, array $data = []): array
    {
        $ids = collect($users)->map(function ($user) {
            return $user instanceof User ? $user->id : (int) $user;
        })->filter()->unique()->values();

        $tokens = Device::query()
            ->whereIn('user_id', $ids)
            ->where('provider', 'expo')
            ->pluck('token')
            ->all();

        return $this->send($tokens, $title, $body, $data);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function pruneInvalidToken(mixed $token, array $ticket): void
    {
        if (! is_string($token) || $token === '') {
            return;
        }

        $details = $ticket['details']['error'] ?? $ticket['message'] ?? null;
        $details = is_string($details) ? $details : '';

        $invalid = str_contains($details, 'DeviceNotRegistered')
            || str_contains($details, 'InvalidCredentials')
            || str_contains(strtolower((string) ($ticket['message'] ?? '')), 'not a registered');

        if (! $invalid) {
            return;
        }

        Device::query()->where('token', $token)->delete();
    }
}
