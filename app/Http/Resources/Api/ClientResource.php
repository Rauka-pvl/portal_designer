<?php

namespace App\Http\Resources\Api;

use App\Models\Client;
use App\Models\Project;
use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

/** @mixin Client */
class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $files = app(\App\Services\Crm\ClientService::class)->filePaths($this->resource);
        $projects = $this->resource->relationLoaded('projectsForApi')
            ? $this->resource->getRelation('projectsForApi')
            : null;

        return [
            'id' => $this->id,
            'type' => $this->client_type ?: 'person',
            'name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'comment' => $this->comment,
            'links' => $this->links(),
            'files' => array_map(fn (string $path) => Storage::disk('public')->url($path), $files),
            'projects_count' => (int) ($this->projects_count ?? $this->crmProjects()->count()),
            'total_projects_budget' => Money::formatMoney(
                $this->projects_budget ?? $this->crmProjects()->sum('planned_cost')
            ) ?? '0.00',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'projects' => $this->when($projects !== null, fn () => $projects->map(
                fn (Project $project) => $this->projectBrief($project)
            )->values()),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function links(): array
    {
        $links = is_array($this->links ?? null) ? $this->links : [];
        if ($this->link) {
            array_unshift($links, $this->link);
        }

        return array_values(array_unique(array_filter($links, 'is_string')));
    }

    /**
     * @return array<string, mixed>
     */
    private function projectBrief(Project $project): array
    {
        $property = $project->propertySnapshot();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'status' => $project->status,
            'city' => $property['city'],
            'object_address' => $property['address'],
            'latitude' => $property['latitude'] === null ? null : (string) $property['latitude'],
            'longitude' => $property['longitude'] === null ? null : (string) $property['longitude'],
            'planned_end_date' => $project->planned_end_date
                ? Carbon::parse($project->planned_end_date)->toIso8601String()
                : null,
            'planned_cost' => Money::formatMoney($project->planned_cost) ?? '0.00',
            'actual_cost' => Money::formatMoney($project->actual_cost) ?? '0.00',
        ];
    }
}
