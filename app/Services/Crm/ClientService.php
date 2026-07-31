<?php

namespace App\Services\Crm;

use App\Enums\PipelineType;
use App\Models\Client;
use App\Models\Pipeline;
use App\Support\PublicFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClientService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function allowedStatusKeys(int $userId): array
    {
        $this->pipelineService->ensureDefaultsForUser($userId);
        $pipeline = Pipeline::defaultForUser($userId, PipelineType::Client);
        $keys = $pipeline->stages()->orderBy('position')->pluck('system_key')->filter()->values()->all();

        return $keys !== [] ? $keys : ['new', 'in_work', 'not_working'];
    }

    /**
     * @param  array<int, string>  $existingFiles
     * @param  array<int, UploadedFile>  $uploadedFiles
     */
    public function syncFiles(Client $client, array $existingFiles = [], array $uploadedFiles = []): Client
    {
        $oldPaths = $this->filePaths($client);
        $uploadedPaths = [];

        foreach ($uploadedFiles as $file) {
            $uploadedPaths[] = PublicFileStorage::store($file, 'clients');
        }

        $newPaths = array_values(array_unique(array_merge(
            array_values(array_filter($existingFiles, fn ($path) => is_string($path) && $path !== '')),
            $uploadedPaths
        )));

        foreach ($oldPaths as $oldPath) {
            if (! in_array($oldPath, $newPaths, true)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $client->file_paths = $newPaths === [] ? null : json_encode($newPaths, JSON_UNESCAPED_SLASHES);
        $client->file_path = $newPaths[0] ?? null;
        $client->save();

        return $client;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $existingFiles
     * @param  array<int, UploadedFile>  $uploadedFiles
     */
    public function save(int $userId, array $data, array $existingFiles = [], array $uploadedFiles = []): Client
    {
        $clientId = $data['client_id'] ?? null;
        $client = $clientId
            ? Client::where('user_id', $userId)->findOrFail($clientId)
            : new Client(['user_id' => $userId]);

        $client->fill([
            'full_name' => $data['full_name'],
            'client_type' => $data['client_type'] ?? 'person',
            'phone' => $data['phone'],
            'email' => $data['email'],
            'status' => $data['status'],
            'comment' => $data['comment'] ?? null,
            'link' => $data['link'] ?? null,
        ]);
        $client->save();

        return $this->syncFiles($client, $existingFiles, $uploadedFiles);
    }

    public function updateStatus(Client $client, string $status): Client
    {
        $client->update(['status' => $status]);

        return $client;
    }

    /**
     * Returns the linked project count when confirmation is required.
     */
    public function destroy(Client $client, bool $confirmed = false): ?int
    {
        $projectsCount = (int) $client->crmProjects()->count() + (int) $client->projects()->count();

        if ($projectsCount > 0 && ! $confirmed) {
            return $projectsCount;
        }

        $client->delete();

        return null;
    }

    public function deleteFile(Client $client, int $fileIndex): bool
    {
        $filePaths = $this->filePaths($client);

        if ($fileIndex < 0 || $fileIndex >= count($filePaths)) {
            return false;
        }

        Storage::disk('public')->delete($filePaths[$fileIndex]);
        array_splice($filePaths, $fileIndex, 1);

        $client->file_paths = $filePaths === [] ? null : json_encode(array_values($filePaths), JSON_UNESCAPED_SLASHES);
        $client->file_path = $filePaths[0] ?? null;
        $client->save();

        return true;
    }

    public function loadAggregates(Client $client): Client
    {
        return $client->loadCount([
            'objects as count_objects',
            'crmProjects as projects_count',
        ])->loadSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->loadSum('crmProjects as projects_budget', 'planned_cost');
    }

    /**
     * @return array<int, string>
     */
    public function filePaths(Client $client): array
    {
        $paths = is_array($client->file_paths)
            ? $client->file_paths
            : json_decode((string) $client->file_paths, true);

        $paths = is_array($paths)
            ? array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''))
            : [];

        return $paths !== [] || empty($client->file_path) ? $paths : [(string) $client->file_path];
    }
}
