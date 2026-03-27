<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    private function clientForUserOrFail(Request $request, int $clientId): Client
    {
        return Client::where('user_id', $request->user()->id)->findOrFail($clientId);
    }

    public function index(Request $request)
    {
        $clients = Client::where('user_id', $request->user()->id)
            ->withCount(['objects as count_objects'])
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned')
            ->orderByDesc('id')
            ->get();

        // Для совместимости с фронтендом (в представлении ожидаются дополнительные поля)
        $clients->each(function (Client $client) {
            $client->count_objects = (int) ($client->count_objects ?? 0);
            $client->sum_repair_budget_planned = (float) ($client->sum_repair_budget_planned ?? 0);

            // Чтобы JS-модалка "Просмотр" показывала ВСЕ файлы,
            // декодируем file_paths из JSON в массив прямо на этапе index.
            $decoded = [];
            if (!empty($client->file_paths)) {
                $tmp = json_decode((string) $client->file_paths, true);
                if (is_array($tmp)) {
                    $decoded = array_values(array_filter($tmp, fn($p) => is_string($p) && $p !== ''));
                }
            }

            if (empty($decoded) && !empty($client->file_path)) {
                $decoded = [$client->file_path];
            }

            $client->file_paths = $decoded;
        });

        return view('clients.index', compact('clients'));
    }

    /**
     * Возвращает список клиентов для живого поиска (AJAX).
     */
    public function search(Request $request)
    {
        $query = Client::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['objects as count_objects'])
            ->withSum('objects as sum_repair_budget_planned', 'repair_budget_planned');

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('full_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('comment', 'like', $like)
                    ->orWhere('link', 'like', $like);
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $clients = $query
            ->orderByDesc('id')
            ->get()
            ->map(fn (Client $client) => $this->payload($client));

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    /**
     * Страница "подробнее" (CRUD на отдельной странице).
     */
    public function show(Request $request, int $clientId)
    {
        $client = $this->clientForUserOrFail($request, $clientId);

        return view('clients.show', [
            'client' => $client,
        ]);
    }

    /**
     * Создание / обновление клиента.
     */
    public function save(Request $request)
    {
        // В форме скрытое `client_id` может приходить как пустая строка.
        // Приводим к `null`, чтобы валидатор корректно применял `nullable`.
        if ($request->has('client_id') && $request->input('client_id') === '') {
            $request->merge(['client_id' => null]);
        }

        $data = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'full_name' => ['required', 'string', 'max:255'],
            'client_type' => ['required', Rule::in(['person', 'company'])],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'status' => ['required', Rule::in(['new', 'in_work', 'not_working'])],
            'comment' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:255'],
            // В таблице нет отдельной сущности для файлов, поэтому сохраняем опционально только file_path.
            'files' => ['nullable'],
        ]);

        $userId = $request->user()->id;

        $clientId = $data['client_id'] ?? null;
        $isUpdate = (bool) $clientId;
        $client = null;

        if ($clientId) {
            $client = Client::where('user_id', $userId)->findOrFail($clientId);
        } else {
            $client = new Client();
            $client->user_id = $userId;
        }

        // Загружаем несколько файлов и сохраняем их пути.
        // file_path оставляем для совместимости (первый файл), file_paths — для списка.
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploaded = is_array($files) ? $files : [$files];
            $paths = [];

            foreach ($uploaded as $file) {
                if (! $file) continue;
                $paths[] = $file->store('clients', 'public');
            }

            if (! empty($paths)) {
                // Добавляем к уже существующим (если они были).
                $existing = [];
                if (! empty($client->file_paths)) {
                    $decoded = json_decode((string) $client->file_paths, true);
                    if (is_array($decoded)) {
                        $existing = array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
                    }
                } elseif (! empty($client->file_path)) {
                    $existing = [$client->file_path];
                }

                $merged = array_values(array_unique(array_merge($existing, $paths)));
                $client->file_paths = json_encode($merged, JSON_UNESCAPED_SLASHES);
                $client->file_path = $merged[0] ?? null; // совместимость со старым полем
            }
        }

        $client->full_name = $data['full_name'];
        $client->client_type = $data['client_type'] ?? 'person';
        $client->phone = $data['phone'];
        $client->email = $data['email'];
        $client->status = $data['status'];
        $client->comment = $data['comment'] ?? null;
        $client->link = $data['link'] ?? null;
        $client->save();

        $message = $isUpdate ? __('clients.saved') : __('clients.added');

        // Для AJAX (index/live-search) возвращаем JSON, для обычной формы (страница details) — redirect.
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'client' => $this->payload($client),
            ]);
        }

        return redirect()
            ->route('clients.show', ['clientId' => $client->id])
            ->with('status', $message);
    }

    /**
     * Смена статуса клиента (для drag&drop и кнопок).
     */
    public function updateStatus(Request $request, int $clientId)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_work', 'not_working'])],
        ]);

        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);
        $client->status = $data['status'];
        $client->save();

        return response()->json([
            'success' => true,
            'client' => $this->payload($client),
        ]);
    }

    /**
     * Удаление клиента (AJAX).
     */
    public function destroy(Request $request, int $clientId)
    {
        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);
        $client->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Удаление одного файла клиента по индексу в массиве file_paths.
     */
    public function deleteFile(Request $request, int $clientId, int $fileIndex)
    {
        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);

        $filePaths = [];
        if (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $filePaths = array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
            }
        }
        if (empty($filePaths) && ! empty($client->file_path)) {
            $filePaths = [$client->file_path];
        }

        if ($fileIndex < 0 || $fileIndex >= count($filePaths)) {
            return response()->json([
                'success' => false,
                'message' => __('clients.error'),
            ], 422);
        }

        $pathToDelete = $filePaths[$fileIndex] ?? null;
        if ($pathToDelete) {
            Storage::disk('public')->delete($pathToDelete);
        }

        array_splice($filePaths, $fileIndex, 1);

        if (! empty($filePaths)) {
            $client->file_paths = json_encode(array_values($filePaths), JSON_UNESCAPED_SLASHES);
            $client->file_path = $filePaths[0] ?? null; // совместимость
        } else {
            $client->file_paths = null;
            $client->file_path = null;
        }

        $client->save();

        return response()->json([
            'success' => true,
            'client' => $this->payload($client),
        ]);
    }

    private function payload(Client $client): array
    {
        $filePaths = [];
        if (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $filePaths = array_values(array_filter($decoded, fn($p) => is_string($p) && $p !== ''));
            }
        }
        if (empty($filePaths) && ! empty($client->file_path)) {
            $filePaths = [$client->file_path];
        }

        // Если агрегаты не были подгружены заранее, считаем их через связь.
        $countObjects = isset($client->count_objects)
            ? (int) $client->count_objects
            : $client->objects()->count();
        $sumRepairBudgetPlanned = isset($client->sum_repair_budget_planned)
            ? (float) $client->sum_repair_budget_planned
            : (float) $client->objects()->sum('repair_budget_planned');

        return [
            'id' => $client->id,
            'full_name' => $client->full_name,
            'client_type' => $client->client_type,
            'phone' => $client->phone,
            'email' => $client->email,
            'status' => $client->status,
            'comment' => $client->comment,
            'link' => $client->link,
            'file_path' => $client->file_path,
            'file_paths' => $filePaths,
            'count_objects' => $client->count_objects ?? $countObjects,
            'sum_repair_budget_planned' => $client->sum_repair_budget_planned ?? $sumRepairBudgetPlanned,
        ];
    }
}
