<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        // Ãâ€ÃÂ»Ã‘Â Ã‘ÂÃÂ¾ÃÂ²ÃÂ¼ÃÂµÃ‘ÂÃ‘â€šÃÂ¸ÃÂ¼ÃÂ¾Ã‘ÂÃ‘â€šÃÂ¸ Ã‘Â Ã‘â€žÃ‘â‚¬ÃÂ¾ÃÂ½Ã‘â€šÃÂµÃÂ½ÃÂ´ÃÂ¾ÃÂ¼ (ÃÂ² ÃÂ¿Ã‘â‚¬ÃÂµÃÂ´Ã‘ÂÃ‘â€šÃÂ°ÃÂ²ÃÂ»ÃÂµÃÂ½ÃÂ¸ÃÂ¸ ÃÂ¾ÃÂ¶ÃÂ¸ÃÂ´ÃÂ°Ã‘Å½Ã‘â€šÃ‘ÂÃ‘Â ÃÂ´ÃÂ¾ÃÂ¿ÃÂ¾ÃÂ»ÃÂ½ÃÂ¸Ã‘â€šÃÂµÃÂ»Ã‘Å’ÃÂ½Ã‘â€¹ÃÂµ ÃÂ¿ÃÂ¾ÃÂ»Ã‘Â)
        $clients->each(function (Client $client) {
            $client->count_objects = (int) ($client->count_objects ?? 0);
            $client->sum_repair_budget_planned = (float) ($client->sum_repair_budget_planned ?? 0);

            // ÃÂ§Ã‘â€šÃÂ¾ÃÂ±Ã‘â€¹ JS-ÃÂ¼ÃÂ¾ÃÂ´ÃÂ°ÃÂ»ÃÂºÃÂ° "ÃÅ¸Ã‘â‚¬ÃÂ¾Ã‘ÂÃÂ¼ÃÂ¾Ã‘â€šÃ‘â‚¬" ÃÂ¿ÃÂ¾ÃÂºÃÂ°ÃÂ·Ã‘â€¹ÃÂ²ÃÂ°ÃÂ»ÃÂ° Ãâ€™ÃÂ¡Ãâ€¢ Ã‘â€žÃÂ°ÃÂ¹ÃÂ»Ã‘â€¹,
            // ÃÂ´ÃÂµÃÂºÃÂ¾ÃÂ´ÃÂ¸Ã‘â‚¬Ã‘Æ’ÃÂµÃÂ¼ file_paths ÃÂ¸ÃÂ· JSON ÃÂ² ÃÂ¼ÃÂ°Ã‘ÂÃ‘ÂÃÂ¸ÃÂ² ÃÂ¿Ã‘â‚¬Ã‘ÂÃÂ¼ÃÂ¾ ÃÂ½ÃÂ° Ã‘ÂÃ‘â€šÃÂ°ÃÂ¿ÃÂµ index.
            $decoded = [];
            if (! empty($client->file_paths)) {
                $tmp = json_decode((string) $client->file_paths, true);
                if (is_array($tmp)) {
                    $decoded = array_values(array_filter($tmp, fn ($p) => is_string($p) && $p !== ''));
                }
            }

            if (empty($decoded) && ! empty($client->file_path)) {
                $decoded = [$client->file_path];
            }

            $client->file_paths = $decoded;
        });

        return view('designer.clients.index', compact('clients'));
    }

    /**
     * Ãâ€™ÃÂ¾ÃÂ·ÃÂ²Ã‘â‚¬ÃÂ°Ã‘â€°ÃÂ°ÃÂµÃ‘â€š Ã‘ÂÃÂ¿ÃÂ¸Ã‘ÂÃÂ¾ÃÂº ÃÂºÃÂ»ÃÂ¸ÃÂµÃÂ½Ã‘â€šÃÂ¾ÃÂ² ÃÂ´ÃÂ»Ã‘Â ÃÂ¶ÃÂ¸ÃÂ²ÃÂ¾ÃÂ³ÃÂ¾ ÃÂ¿ÃÂ¾ÃÂ¸Ã‘ÂÃÂºÃÂ° (AJAX).
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
                $like = '%'.$search.'%';
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
     * ÃÂ¡Ã‘â€šÃ‘â‚¬ÃÂ°ÃÂ½ÃÂ¸Ã‘â€ ÃÂ° "ÃÂ¿ÃÂ¾ÃÂ´Ã‘â‚¬ÃÂ¾ÃÂ±ÃÂ½ÃÂµÃÂµ" (CRUD ÃÂ½ÃÂ° ÃÂ¾Ã‘â€šÃÂ´ÃÂµÃÂ»Ã‘Å’ÃÂ½ÃÂ¾ÃÂ¹ Ã‘ÂÃ‘â€šÃ‘â‚¬ÃÂ°ÃÂ½ÃÂ¸Ã‘â€ ÃÂµ).
     */
    public function show(Request $request, int $clientId)
    {
        $client = $this->clientForUserOrFail($request, $clientId);

        return view('designer.clients.show', [
            'client' => $client,
        ]);
    }

    /**
     * ÃÂ¡ÃÂ¾ÃÂ·ÃÂ´ÃÂ°ÃÂ½ÃÂ¸ÃÂµ / ÃÂ¾ÃÂ±ÃÂ½ÃÂ¾ÃÂ²ÃÂ»ÃÂµÃÂ½ÃÂ¸ÃÂµ ÃÂºÃÂ»ÃÂ¸ÃÂµÃÂ½Ã‘â€šÃÂ°.
     */
    public function save(Request $request)
    {
        // Ãâ€™ Ã‘â€žÃÂ¾Ã‘â‚¬ÃÂ¼ÃÂµ Ã‘ÂÃÂºÃ‘â‚¬Ã‘â€¹Ã‘â€šÃÂ¾ÃÂµ `client_id` ÃÂ¼ÃÂ¾ÃÂ¶ÃÂµÃ‘â€š ÃÂ¿Ã‘â‚¬ÃÂ¸Ã‘â€¦ÃÂ¾ÃÂ´ÃÂ¸Ã‘â€šÃ‘Å’ ÃÂºÃÂ°ÃÂº ÃÂ¿Ã‘Æ’Ã‘ÂÃ‘â€šÃÂ°Ã‘Â Ã‘ÂÃ‘â€šÃ‘â‚¬ÃÂ¾ÃÂºÃÂ°.
        // ÃÅ¸Ã‘â‚¬ÃÂ¸ÃÂ²ÃÂ¾ÃÂ´ÃÂ¸ÃÂ¼ ÃÂº `null`, Ã‘â€¡Ã‘â€šÃÂ¾ÃÂ±Ã‘â€¹ ÃÂ²ÃÂ°ÃÂ»ÃÂ¸ÃÂ´ÃÂ°Ã‘â€šÃÂ¾Ã‘â‚¬ ÃÂºÃÂ¾Ã‘â‚¬Ã‘â‚¬ÃÂµÃÂºÃ‘â€šÃÂ½ÃÂ¾ ÃÂ¿Ã‘â‚¬ÃÂ¸ÃÂ¼ÃÂµÃÂ½Ã‘ÂÃÂ» `nullable`.
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
            // Ãâ€™ Ã‘â€šÃÂ°ÃÂ±ÃÂ»ÃÂ¸Ã‘â€ ÃÂµ ÃÂ½ÃÂµÃ‘â€š ÃÂ¾Ã‘â€šÃÂ´ÃÂµÃÂ»Ã‘Å’ÃÂ½ÃÂ¾ÃÂ¹ Ã‘ÂÃ‘Æ’Ã‘â€°ÃÂ½ÃÂ¾Ã‘ÂÃ‘â€šÃÂ¸ ÃÂ´ÃÂ»Ã‘Â Ã‘â€žÃÂ°ÃÂ¹ÃÂ»ÃÂ¾ÃÂ², ÃÂ¿ÃÂ¾Ã‘ÂÃ‘â€šÃÂ¾ÃÂ¼Ã‘Æ’ Ã‘ÂÃÂ¾Ã‘â€¦Ã‘â‚¬ÃÂ°ÃÂ½Ã‘ÂÃÂµÃÂ¼ ÃÂ¾ÃÂ¿Ã‘â€ ÃÂ¸ÃÂ¾ÃÂ½ÃÂ°ÃÂ»Ã‘Å’ÃÂ½ÃÂ¾ Ã‘â€šÃÂ¾ÃÂ»Ã‘Å’ÃÂºÃÂ¾ file_path.
            'files' => ['nullable'],
        ]);

        $userId = $request->user()->id;

        $clientId = $data['client_id'] ?? null;
        $isUpdate = (bool) $clientId;
        $client = null;

        if ($clientId) {
            $client = Client::where('user_id', $userId)->findOrFail($clientId);
        } else {
            $client = new Client;
            $client->user_id = $userId;
        }

        // Ãâ€”ÃÂ°ÃÂ³Ã‘â‚¬Ã‘Æ’ÃÂ¶ÃÂ°ÃÂµÃÂ¼ ÃÂ½ÃÂµÃ‘ÂÃÂºÃÂ¾ÃÂ»Ã‘Å’ÃÂºÃÂ¾ Ã‘â€žÃÂ°ÃÂ¹ÃÂ»ÃÂ¾ÃÂ² ÃÂ¸ Ã‘ÂÃÂ¾Ã‘â€¦Ã‘â‚¬ÃÂ°ÃÂ½Ã‘ÂÃÂµÃÂ¼ ÃÂ¸Ã‘â€¦ ÃÂ¿Ã‘Æ’Ã‘â€šÃÂ¸.
        // file_path ÃÂ¾Ã‘ÂÃ‘â€šÃÂ°ÃÂ²ÃÂ»Ã‘ÂÃÂµÃÂ¼ ÃÂ´ÃÂ»Ã‘Â Ã‘ÂÃÂ¾ÃÂ²ÃÂ¼ÃÂµÃ‘ÂÃ‘â€šÃÂ¸ÃÂ¼ÃÂ¾Ã‘ÂÃ‘â€šÃÂ¸ (ÃÂ¿ÃÂµÃ‘â‚¬ÃÂ²Ã‘â€¹ÃÂ¹ Ã‘â€žÃÂ°ÃÂ¹ÃÂ»), file_paths Ã¢â‚¬â€ ÃÂ´ÃÂ»Ã‘Â Ã‘ÂÃÂ¿ÃÂ¸Ã‘ÂÃÂºÃÂ°.
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploaded = is_array($files) ? $files : [$files];
            $paths = [];

            foreach ($uploaded as $file) {
                if (! $file) {
                    continue;
                }
                $paths[] = $file->store('clients', 'public');
            }

            if (! empty($paths)) {
                // Ãâ€ÃÂ¾ÃÂ±ÃÂ°ÃÂ²ÃÂ»Ã‘ÂÃÂµÃÂ¼ ÃÂº Ã‘Æ’ÃÂ¶ÃÂµ Ã‘ÂÃ‘Æ’Ã‘â€°ÃÂµÃ‘ÂÃ‘â€šÃÂ²Ã‘Æ’Ã‘Å½Ã‘â€°ÃÂ¸ÃÂ¼ (ÃÂµÃ‘ÂÃÂ»ÃÂ¸ ÃÂ¾ÃÂ½ÃÂ¸ ÃÂ±Ã‘â€¹ÃÂ»ÃÂ¸).
                $existing = [];
                if (! empty($client->file_paths)) {
                    $decoded = json_decode((string) $client->file_paths, true);
                    if (is_array($decoded)) {
                        $existing = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
                    }
                } elseif (! empty($client->file_path)) {
                    $existing = [$client->file_path];
                }

                $merged = array_values(array_unique(array_merge($existing, $paths)));
                $client->file_paths = json_encode($merged, JSON_UNESCAPED_SLASHES);
                $client->file_path = $merged[0] ?? null; // Ã‘ÂÃÂ¾ÃÂ²ÃÂ¼ÃÂµÃ‘ÂÃ‘â€šÃÂ¸ÃÂ¼ÃÂ¾Ã‘ÂÃ‘â€šÃ‘Å’ Ã‘ÂÃÂ¾ Ã‘ÂÃ‘â€šÃÂ°Ã‘â‚¬Ã‘â€¹ÃÂ¼ ÃÂ¿ÃÂ¾ÃÂ»ÃÂµÃÂ¼
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

        // Ãâ€ÃÂ»Ã‘Â AJAX (index/live-search) ÃÂ²ÃÂ¾ÃÂ·ÃÂ²Ã‘â‚¬ÃÂ°Ã‘â€°ÃÂ°ÃÂµÃÂ¼ JSON, ÃÂ´ÃÂ»Ã‘Â ÃÂ¾ÃÂ±Ã‘â€¹Ã‘â€¡ÃÂ½ÃÂ¾ÃÂ¹ Ã‘â€žÃÂ¾Ã‘â‚¬ÃÂ¼Ã‘â€¹ (Ã‘ÂÃ‘â€šÃ‘â‚¬ÃÂ°ÃÂ½ÃÂ¸Ã‘â€ ÃÂ° details) Ã¢â‚¬â€ redirect.
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
     * ÃÂ¡ÃÂ¼ÃÂµÃÂ½ÃÂ° Ã‘ÂÃ‘â€šÃÂ°Ã‘â€šÃ‘Æ’Ã‘ÂÃÂ° ÃÂºÃÂ»ÃÂ¸ÃÂµÃÂ½Ã‘â€šÃÂ° (ÃÂ´ÃÂ»Ã‘Â drag&drop ÃÂ¸ ÃÂºÃÂ½ÃÂ¾ÃÂ¿ÃÂ¾ÃÂº).
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
     * ÃÂ£ÃÂ´ÃÂ°ÃÂ»ÃÂµÃÂ½ÃÂ¸ÃÂµ ÃÂºÃÂ»ÃÂ¸ÃÂµÃÂ½Ã‘â€šÃÂ° (AJAX).
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
     * ÃÂ£ÃÂ´ÃÂ°ÃÂ»ÃÂµÃÂ½ÃÂ¸ÃÂµ ÃÂ¾ÃÂ´ÃÂ½ÃÂ¾ÃÂ³ÃÂ¾ Ã‘â€žÃÂ°ÃÂ¹ÃÂ»ÃÂ° ÃÂºÃÂ»ÃÂ¸ÃÂµÃÂ½Ã‘â€šÃÂ° ÃÂ¿ÃÂ¾ ÃÂ¸ÃÂ½ÃÂ´ÃÂµÃÂºÃ‘ÂÃ‘Æ’ ÃÂ² ÃÂ¼ÃÂ°Ã‘ÂÃ‘ÂÃÂ¸ÃÂ²ÃÂµ file_paths.
     */
    public function deleteFile(Request $request, int $clientId, int $fileIndex)
    {
        $client = Client::where('user_id', $request->user()->id)->findOrFail($clientId);

        $filePaths = [];
        if (! empty($client->file_paths)) {
            $decoded = json_decode((string) $client->file_paths, true);
            if (is_array($decoded)) {
                $filePaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
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
            $client->file_path = $filePaths[0] ?? null; // Ã‘ÂÃÂ¾ÃÂ²ÃÂ¼ÃÂµÃ‘ÂÃ‘â€šÃÂ¸ÃÂ¼ÃÂ¾Ã‘ÂÃ‘â€šÃ‘Å’
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
                $filePaths = array_values(array_filter($decoded, fn ($p) => is_string($p) && $p !== ''));
            }
        }
        if (empty($filePaths) && ! empty($client->file_path)) {
            $filePaths = [$client->file_path];
        }

        // Ãâ€¢Ã‘ÂÃÂ»ÃÂ¸ ÃÂ°ÃÂ³Ã‘â‚¬ÃÂµÃÂ³ÃÂ°Ã‘â€šÃ‘â€¹ ÃÂ½ÃÂµ ÃÂ±Ã‘â€¹ÃÂ»ÃÂ¸ ÃÂ¿ÃÂ¾ÃÂ´ÃÂ³Ã‘â‚¬Ã‘Æ’ÃÂ¶ÃÂµÃÂ½Ã‘â€¹ ÃÂ·ÃÂ°Ã‘â‚¬ÃÂ°ÃÂ½ÃÂµÃÂµ, Ã‘ÂÃ‘â€¡ÃÂ¸Ã‘â€šÃÂ°ÃÂµÃÂ¼ ÃÂ¸Ã‘â€¦ Ã‘â€¡ÃÂµÃ‘â‚¬ÃÂµÃÂ· Ã‘ÂÃÂ²Ã‘ÂÃÂ·Ã‘Å’.
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


