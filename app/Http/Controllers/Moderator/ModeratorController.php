<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ModeratorController extends Controller
{
    private function authorizeModerator(Request $request): void
    {
        abort_unless(($request->user()->role ?? null) === 'moderator', 403);
    }

    public function index(Request $request)
    {
        $this->authorizeModerator($request);

        $pendingSuppliers = Supplier::query()
            ->where('moderation_status', 'pending')
            ->orderByDesc('id')
            ->with(['user:id,name'])
            ->limit(50)
            ->get(['id', 'name', 'user_id', 'city', 'address', 'moderation_status', 'moderation_comment']);

        $pendingProjects = Project::query()
            ->where('moderation_status', 'pending')
            ->orderByDesc('id')
            ->with([
                'user:id,name',
                'object:id,city,address,type,apartment,apartment_floor,apartment_entrance',
            ])
            ->limit(50)
            ->get(['id', 'name', 'user_id', 'object_id', 'moderation_status', 'moderation_comment', 'moderation_reason']);

        return view('moderator.index', [
            'pendingSuppliers' => $pendingSuppliers,
            'pendingProjects' => $pendingProjects,
        ]);
    }

    public function supplierShow(Request $request, int $supplierId)
    {
        $this->authorizeModerator($request);

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
        $this->authorizeModerator($request);

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

    public function projectShow(Request $request, int $projectId)
    {
        $this->authorizeModerator($request);

        $project = Project::query()
            ->where('id', $projectId)
            ->with([
                'user:id,name',
                'object:id,city,address,type,apartment,apartment_floor,apartment_entrance',
            ])
            ->findOrFail($projectId);

        return view('moderator.projects.show', [
            'project' => $project,
        ]);
    }

    public function projectDecide(Request $request, int $projectId)
    {
        $this->authorizeModerator($request);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('projects')
            ->where('id', $projectId)
            ->update([
                'moderation_status' => $data['decision'],
                'moderation_comment' => isset($data['comment']) && trim((string) $data['comment']) !== ''
                    ? $data['comment']
                    : null,
                'moderation_reviewer_id' => $request->user()->id,
                'moderation_reviewed_at' => now(),
            ]);

        return redirect()->route('moderator.projects.show', $projectId)->with('status', __('moderation.saved'));
    }
}

