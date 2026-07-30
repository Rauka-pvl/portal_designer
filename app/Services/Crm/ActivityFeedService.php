<?php

namespace App\Services\Crm;

use App\Models\ActivityEvent;
use App\Models\User;

class ActivityFeedService
{
    public function record(
        int $ownerUserId,
        string $subjectType,
        int $subjectId,
        string $eventType,
        ?User $actor = null,
        ?string $body = null,
        ?array $meta = null
    ): ActivityEvent {
        return ActivityEvent::query()->create([
            'user_id' => $ownerUserId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event_type' => $eventType,
            'actor_id' => $actor?->id,
            'body' => $body,
            'meta' => $meta,
        ]);
    }

    public function forSubject(string $subjectType, int $subjectId, ?string $filter = null, int $perPage = 30)
    {
        $query = ActivityEvent::query()
            ->with('actor:id,name')
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderByDesc('id');

        if ($filter === 'comments') {
            $query->where('event_type', 'comment');
        } elseif ($filter === 'changes') {
            $query->where('event_type', '!=', 'comment');
        }

        return $query->paginate($perPage);
    }
}
