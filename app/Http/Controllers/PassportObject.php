<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PassportObject as PassportObjectModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PassportObject extends Controller
{
    private function objectForUserOrFail(Request $request, int $objectId): PassportObjectModel
    {
        return PassportObjectModel::where('user_id', $request->user()->id)->findOrFail($objectId);
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $clients = Client::where('user_id', $userId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $objects = PassportObjectModel::query()
            ->where('user_id', $userId)
            ->with('client')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PassportObjectModel $o) => $this->payload($o))
            ->values();

        return view('objects.index_v2', [
            'objects' => $objects,
            'clients' => $clients,
        ]);
    }

    /**
     * Живой поиск объектов (AJAX).
     */
    public function search(Request $request)
    {
        $query = PassportObjectModel::query()
            ->where('user_id', $request->user()->id)
            ->with('client');

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $clientId = $request->query('client_id', '');
        $status = (string) $request->query('status', '');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('address', 'like', $like)
                    ->orWhere('type', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhere('comment', 'like', $like)
                    ->orWhereHas('client', function ($cq) use ($like) {
                        $cq->where('full_name', 'like', $like);
                    });
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($clientId !== '' && is_numeric($clientId)) {
            $query->where('client_id', (int) $clientId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $objects = $query
            ->orderByDesc('id')
            ->get()
            ->map(fn (PassportObjectModel $o) => $this->payload($o))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $objects,
        ]);
    }

    public function show(Request $request, int $objectId)
    {
        $object = $this->objectForUserOrFail($request, $objectId);
        $object->load('client');

        // payload удобно использовать для фронта, но в форме нам нужен сам модельный объект.
        return view('objects.show', [
            'object' => $object,
        ]);
    }

    public function save(Request $request)
    {
        if ($request->has('object_id') && $request->input('object_id') === '') {
            $request->merge(['object_id' => null]);
        }

        $data = $request->validate([
            'object_id' => ['nullable', 'integer'],
            'client_id' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::in(['new', 'in_work', 'not_working'])],
            'area' => ['required', 'numeric', 'min:0'],
            'repair_budget_planned' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_actual' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_per_m2_planned' => ['nullable', 'numeric', 'min:0'],
            'repair_budget_per_m2_actual' => ['nullable', 'numeric', 'min:0'],
            // links могут приходить как array (links[]) либо как строка (links_text)
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'string', 'max:2048'],
            'links_text' => ['nullable', 'string', 'max:10000'],
            'comment' => ['nullable', 'string'],
            'files' => ['nullable'], // хотим принять файлы через multipart
        ]);

        $userId = $request->user()->id;
        $objectId = $data['object_id'] ?? null;
        $isUpdate = (bool) $objectId;

        $object = null;
        if ($objectId) {
            $object = PassportObjectModel::where('user_id', $userId)->findOrFail((int) $objectId);
        } else {
            $object = new PassportObjectModel();
            $object->user_id = $userId;
        }

        // type whitelist (чтобы не сломать UI)
        $allowedTypes = ['apartment', 'house', 'commercial', 'other'];
        if (!in_array($data['type'], $allowedTypes, true)) {
            $data['type'] = 'other';
        }

        // ссылки
        $links = [];
        if ($request->filled('links') && is_array($request->input('links'))) {
            $links = array_values(array_filter(
                $request->input('links'),
                fn($v) => is_string($v) && trim($v) !== ''
            ));
        } elseif ($request->filled('links_text')) {
            $links = preg_split('/\r\n|\r|\n/', (string) $request->input('links_text')) ?: [];
            $links = array_values(array_filter(array_map('trim', $links), fn($v) => $v !== ''));
        }

        // файлы (несколько)
        $newPaths = [];
        $files = $request->file('files');
        if ($files) {
            $uploaded = is_array($files) ? $files : [$files];
            foreach ($uploaded as $file) {
                if (! $file) continue;
                $newPaths[] = $file->store('passport_objects', 'public');
            }
        }

        // существующие пути
        $existingPaths = [];
        if (!empty($object->file_paths) && is_array($object->file_paths)) {
            $existingPaths = array_values(array_filter($object->file_paths, fn($p) => is_string($p) && $p !== ''));
        }

        if (! empty($newPaths)) {
            $merged = array_values(array_unique(array_merge($existingPaths, $newPaths)));
            $object->file_paths = $merged;
        } else {
            // оставляем как было
            $object->file_paths = $existingPaths ?: $object->file_paths;
        }

        $object->client_id = (int) $data['client_id'];
        $object->address = $data['address'];
        $object->type = $data['type'];
        $object->status = $data['status'];
        $object->area = $data['area'];
        $object->repair_budget_planned = $data['repair_budget_planned'] ?? null;
        $object->repair_budget_actual = $data['repair_budget_actual'] ?? null;
        $object->repair_budget_per_m2_planned = $data['repair_budget_per_m2_planned'] ?? null;
        $object->repair_budget_per_m2_actual = $data['repair_budget_per_m2_actual'] ?? null;
        $object->links = $links ?: null;
        $object->comment = $data['comment'] ?? null;

        $object->save();

        $message = $isUpdate ? __('objects.object_updated') : __('objects.object_added');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'object' => $this->payload($object),
            ]);
        }

        return redirect()
            ->route('objects.show', ['objectId' => $object->id])
            ->with('status', $message);
    }

    public function updateStatus(Request $request, int $objectId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_work', 'not_working'])],
        ]);

        $object = PassportObjectModel::where('user_id', $request->user()->id)->findOrFail($objectId);
        $object->status = $data['status'];
        $object->save();

        return response()->json([
            'success' => true,
            'object' => $this->payload($object),
        ]);
    }

    public function destroy(Request $request, int $objectId)
    {
        $object = PassportObjectModel::where('user_id', $request->user()->id)->findOrFail($objectId);
        $object->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Удаление одного файла объекта по индексу из file_paths.
     */
    public function deleteFile(Request $request, int $objectId, int $fileIndex)
    {
        $object = PassportObjectModel::where('user_id', $request->user()->id)->findOrFail($objectId);

        $paths = is_array($object->file_paths) ? array_values($object->file_paths) : [];
        if (empty($paths)) {
            return response()->json([
                'success' => false,
                'message' => __('objects.error'),
            ], 422);
        }

        if ($fileIndex < 0 || $fileIndex >= count($paths)) {
            return response()->json([
                'success' => false,
                'message' => __('objects.error'),
            ], 422);
        }

        $toDelete = $paths[$fileIndex];
        if (is_string($toDelete) && $toDelete !== '') {
            Storage::disk('public')->delete($toDelete);
        }

        array_splice($paths, $fileIndex, 1);
        $object->file_paths = empty($paths) ? null : array_values($paths);
        $object->save();

        return response()->json([
            'success' => true,
            'object' => $this->payload($object),
        ]);
    }

    private function payload(PassportObjectModel $object): array
    {
        $clientName = $object->client?->full_name;
        if (is_array($object->links)) {
            $links = $object->links;
        } elseif (is_string($object->links)) {
            $decodedLinks = json_decode((string) $object->links, true);
            $links = is_array($decodedLinks) ? $decodedLinks : [];
        } else {
            $links = [];
        }

        if (is_array($object->file_paths)) {
            $filePaths = $object->file_paths;
        } elseif (is_string($object->file_paths)) {
            $decodedFiles = json_decode((string) $object->file_paths, true);
            $filePaths = is_array($decodedFiles) ? $decodedFiles : [];
        } else {
            $filePaths = [];
        }

        $projectsCount = 0;

        return [
            'id' => $object->id,
            'client_id' => $object->client_id,
            'client_name' => $clientName,
            'address' => $object->address,
            'type' => $object->type,
            'status' => $object->status,
            'area' => $object->area,
            'repair_budget_planned' => $object->repair_budget_planned,
            'repair_budget_actual' => $object->repair_budget_actual,
            'repair_budget_per_m2_planned' => $object->repair_budget_per_m2_planned,
            'repair_budget_per_m2_actual' => $object->repair_budget_per_m2_actual,
            'projects_count' => $projectsCount,
            'links' => $links,
            'file_paths' => $filePaths,
            'comment' => $object->comment,
        ];
    }
}
