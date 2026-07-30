<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\ActivityEvent;
use App\Models\Project;
use App\Services\Crm\ActivityFeedService;
use App\Support\AccountPermissions;
use Illuminate\Http\Request;

class ProjectActivityController extends Controller
{
    public function __construct(private ActivityFeedService $feed) {}

    public function index(Request $request, int $projectId)
    {
        $project = Project::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($projectId);

        $paginator = $this->feed->forSubject(
            'project',
            $project->id,
            $request->query('filter')
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn (ActivityEvent $e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'body' => $e->body ?: $this->defaultBody($e),
                'meta' => $e->meta,
                'created_at' => optional($e->created_at)?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
                'actor' => $e->actor ? ['id' => $e->actor->id, 'name' => $e->actor->name] : null,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeComment(Request $request, int $projectId)
    {
        $user = $request->user();
        $project = Project::query()
            ->where('user_id', $user->id)
            ->findOrFail($projectId);

        abort_unless(AccountPermissions::ownsResource($user, (int) $project->user_id), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $event = $this->feed->record(
            (int) $project->user_id,
            'project',
            $project->id,
            'comment',
            $user,
            trim($data['body'])
        );

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'event_type' => 'comment',
                'body' => $event->body,
                'created_at' => $event->created_at?->format('d.m.Y H:i'),
                'actor' => ['id' => $user->id, 'name' => $user->name],
            ],
        ]);
    }

    private function defaultBody(ActivityEvent $e): string
    {
        return match ($e->event_type) {
            'project.created' => __('projects.activity_created'),
            'project.status_changed' => __('projects.activity_status_changed', [
                'from' => $e->meta['from_label'] ?? ($e->meta['from'] ?? ''),
                'to' => $e->meta['to_label'] ?? ($e->meta['to'] ?? ''),
            ]),
            'project.updated' => __('projects.activity_updated'),
            'supply.created' => __('projects.activity_supply_created'),
            'checklist.updated' => __('projects.activity_checklist_updated'),
            default => $e->event_type,
        };
    }
}
