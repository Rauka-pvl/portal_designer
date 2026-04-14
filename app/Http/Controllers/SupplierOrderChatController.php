<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\SupplierOrderMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierOrderChatController extends Controller
{
    public function messages(Request $request, int $orderId): JsonResponse
    {
        if (! $this->chatTablesReady()) {
            return response()->json(['success' => true, 'messages' => []]);
        }
        $order = $this->resolveAccessibleOrder($request, $orderId);

        $messages = SupplierOrderMessage::query()
            ->where('supplier_order_id', $order->id)
            ->with('sender:id,name,role')
            ->orderBy('id')
            ->get()
            ->map(function (SupplierOrderMessage $message) use ($request) {
                return [
                    'id' => (int) $message->id,
                    'sender_user_id' => (int) $message->sender_user_id,
                    'sender_name' => (string) ($message->sender?->name ?? ''),
                    'sender_role' => (string) ($message->sender?->role ?? ''),
                    'is_mine' => (int) $message->sender_user_id === (int) $request->user()->id,
                    'message' => (string) $message->message,
                    'created_at' => optional($message->created_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, int $orderId): JsonResponse
    {
        if (! $this->chatTablesReady()) {
            return response()->json(['success' => false, 'message' => 'Chat is not initialized yet.'], 409);
        }
        $order = $this->resolveAccessibleOrder($request, $orderId);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = SupplierOrderMessage::query()->create([
            'supplier_order_id' => (int) $order->id,
            'sender_user_id' => (int) $request->user()->id,
            'message' => trim((string) $data['message']),
        ])->load('sender:id,name,role');

        return response()->json([
            'success' => true,
            'message_item' => [
                'id' => (int) $message->id,
                'sender_user_id' => (int) $message->sender_user_id,
                'sender_name' => (string) ($message->sender?->name ?? ''),
                'sender_role' => (string) ($message->sender?->role ?? ''),
                'is_mine' => true,
                'message' => (string) $message->message,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ],
        ]);
    }

    public function markRead(Request $request, int $orderId): JsonResponse
    {
        if (! $this->chatTablesReady()) {
            return response()->json(['success' => true, 'marked_count' => 0]);
        }
        $order = $this->resolveAccessibleOrder($request, $orderId);
        $userId = (int) $request->user()->id;
        $readColumn = $this->readColumnForRole((string) ($request->user()->role ?? ''));

        $markedCount = SupplierOrderMessage::query()
            ->where('supplier_order_id', (int) $order->id)
            ->where('sender_user_id', '!=', $userId)
            ->whereNull($readColumn)
            ->update([$readColumn => now()]);

        return response()->json([
            'success' => true,
            'marked_count' => (int) $markedCount,
        ]);
    }

    public function unreadMap(Request $request): JsonResponse
    {
        if (! $this->chatTablesReady()) {
            return response()->json(['success' => true, 'unread' => []]);
        }
        $userId = (int) $request->user()->id;
        $orderIds = $this->accessibleOrderIds($request);
        $readColumn = $this->readColumnForRole((string) ($request->user()->role ?? ''));
        if ($orderIds === []) {
            return response()->json(['success' => true, 'unread' => []]);
        }

        $rows = DB::table('supplier_order_messages as m')
            ->whereIn('m.supplier_order_id', $orderIds)
            ->where('m.sender_user_id', '!=', $userId)
            ->whereNull('m.'.$readColumn)
            ->select('m.supplier_order_id', DB::raw('COUNT(*) as unread_count'))
            ->groupBy('m.supplier_order_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->supplier_order_id] = (int) $row->unread_count;
        }

        return response()->json(['success' => true, 'unread' => $map]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        if (! $this->chatTablesReady()) {
            return response()->json(['count' => 0]);
        }
        $userId = (int) $request->user()->id;
        $orderIds = $this->accessibleOrderIds($request);
        $readColumn = $this->readColumnForRole((string) ($request->user()->role ?? ''));
        if ($orderIds === []) {
            return response()->json(['count' => 0]);
        }

        $count = DB::table('supplier_order_messages as m')
            ->whereIn('m.supplier_order_id', $orderIds)
            ->where('m.sender_user_id', '!=', $userId)
            ->whereNull('m.'.$readColumn)
            ->count();

        return response()->json(['count' => (int) $count]);
    }

    private function resolveAccessibleOrder(Request $request, int $orderId): Supplier_orders
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $userId = (int) $user->id;

        if ($role === 'designer') {
            return Supplier_orders::query()
                ->where('user_id', $userId)
                ->findOrFail($orderId);
        }

        if ($role === 'supplier') {
            $supplier = Supplier::query()
                ->where('user_id', $userId)
                ->firstOrFail();

            return Supplier_orders::query()
                ->where('supplier_id', (int) $supplier->id)
                ->where('is_sent_to_supplier', true)
                ->findOrFail($orderId);
        }

        abort(403);
    }

    /**
     * @return list<int>
     */
    private function accessibleOrderIds(Request $request): array
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $userId = (int) $user->id;

        if ($role === 'designer') {
            return Supplier_orders::query()
                ->where('user_id', $userId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($role === 'supplier') {
            $supplierId = (int) Supplier::query()
                ->where('user_id', $userId)
                ->value('id');
            if ($supplierId < 1) {
                return [];
            }

            return Supplier_orders::query()
                ->where('supplier_id', $supplierId)
                ->where('is_sent_to_supplier', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    private function chatTablesReady(): bool
    {
        return Schema::hasTable('supplier_order_messages')
            && Schema::hasColumn('supplier_order_messages', 'read_by_designer_at')
            && Schema::hasColumn('supplier_order_messages', 'read_by_supplier_at');
    }

    private function readColumnForRole(string $role): string
    {
        return $role === 'supplier'
            ? 'read_by_supplier_at'
            : 'read_by_designer_at';
    }
}
