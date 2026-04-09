<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierPortalController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = Supplier::query()
            ->where('account_user_id', (int) $request->user()->id)
            ->first();

        return view('supplier.index', [
            'supplier' => $supplier,
        ]);
    }

    public function saveProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $supplier = Supplier::query()->firstOrNew([
            'account_user_id' => (int) $user->id,
        ]);

        if (! $supplier->exists) {
            $supplier->user_id = (int) $user->id;
            $supplier->name = (string) $user->name;
            $supplier->email = (string) $user->email;
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'sphere' => ['nullable', 'string', 'max:255'],
            'work_terms_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'work_terms_value' => ['nullable', 'string', 'max:255'],
            'inn' => ['required', 'string', 'max:32'],
            'kpp' => ['nullable', 'string', 'max:255'],
            'ogrn' => ['nullable', 'string', 'max:255'],
            'okpo' => ['nullable', 'string', 'max:255'],
        ]);

        $supplier->name = $data['name'];
        $supplier->phone = $data['phone'] ?? null;
        $supplier->email = $data['email'] ?? null;
        $supplier->city = $data['city'] ?? null;
        $supplier->address = $data['address'] ?? null;
        $supplier->sphere = $data['sphere'] ?? null;
        $supplier->work_terms_type = $data['work_terms_type'] ?? null;
        $supplier->work_terms_value = $data['work_terms_value'] ?? null;
        $supplier->inn = trim((string) $data['inn']);
        $supplier->kpp = $data['kpp'] ?? null;
        $supplier->ogrn = $data['ogrn'] ?? null;
        $supplier->okpo = $data['okpo'] ?? null;

        $supplier->profile_status = 'pending';
        $supplier->moderation_status = 'pending';
        $supplier->moderation_comment = null;
        $supplier->moderation_reviewer_id = null;
        $supplier->moderation_reviewed_at = null;
        $supplier->save();

        return redirect()
            ->route('supplier.index')
            ->with('status', __('supplier-portal.submitted_for_review'));
    }
}
