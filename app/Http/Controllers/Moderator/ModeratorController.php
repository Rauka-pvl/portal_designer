<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\PassportObject;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModeratorController extends Controller
{
    public function index()
    {
        $pendingSuppliers = Supplier::query()
            ->where('moderation_status', 'pending')
            ->orderByDesc('id')
            ->with(['user:id,name'])
            ->limit(50)
            ->get(['id', 'name', 'user_id', 'city', 'address', 'moderation_status', 'moderation_comment']);

        $pendingObjects = PassportObject::query()
            ->where('moderation_status', 'pending')
            ->whereNotNull('moderation_duplicate_of_object_id')
            ->orderByDesc('id')
            ->with([
                'user:id,name',
                'moderationDuplicateOf.user:id,name',
            ])
            ->limit(50)
            ->get([
                'id',
                'user_id',
                'city',
                'address',
                'type',
                'apartment',
                'apartment_floor',
                'apartment_entrance',
                'moderation_status',
                'moderation_duplicate_of_object_id',
            ]);

        return view('moderator.index', [
            'pendingSuppliers' => $pendingSuppliers,
            'pendingObjects' => $pendingObjects,
        ]);
    }

    public function supplierShow(int $supplierId)
    {
        $supplier = Supplier::query()
            ->where('id', $supplierId)
            ->with('user:id,name')
            ->findOrFail($supplierId);

        return view('moderator.suppliers.show', [
            'supplier' => $supplier,
        ]);
    }

    public function supplierDecide(Request $request, int $supplierId)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('suppliers')
            ->where('id', $supplierId)
            ->update([
                'moderation_status' => $data['decision'],
                'moderation_comment' => isset($data['comment']) && trim((string) $data['comment']) !== ''
                    ? $data['comment']
                    : null,
                'moderation_reviewer_id' => $request->user()->id,
                'moderation_reviewed_at' => now(),
            ]);

        return redirect()->route('moderator.suppliers.show', $supplierId)->with('status', __('moderation.saved'));
    }

    public function objectShow(int $objectId)
    {
        $object = PassportObject::query()
            ->where('id', $objectId)
            ->with([
                'user:id,name',
                'moderationDuplicateOf.user:id,name',
            ])
            ->findOrFail($objectId);

        return view('moderator.objects.show', [
            'object' => $object,
            'existingObject' => $object->moderationDuplicateOf,
        ]);
    }

    public function objectDecide(Request $request, int $objectId)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $object = PassportObject::query()
            ->where('id', $objectId)
            ->where('moderation_status', 'pending')
            ->whereNotNull('moderation_duplicate_of_object_id')
            ->findOrFail($objectId);

        $comment = isset($data['comment']) && trim((string) $data['comment']) !== ''
            ? $data['comment']
            : null;

        if ($data['decision'] === 'approved') {
            $object->moderation_status = 'approved';
            $object->moderation_duplicate_of_object_id = null;
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->save();
        } else {
            $object->moderation_comment = $comment;
            $object->moderation_reviewer_id = $request->user()->id;
            $object->moderation_reviewed_at = now();
            $object->moderation_status = 'rejected';
            $object->save();
            $object->delete();
        }

        return redirect()->route('moderator.index')->with('status', __('moderation.saved'));
    }
}
