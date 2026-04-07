<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class ReferralSupplierController extends Controller
{
    public function create(Request $request)
    {
        if (! $request->hasValidSignature()) {
            return response()->view('referrals.suppliers.invalid-signature', [], 403);
        }

        $designerId = (int) $request->query('designer');
        $designer = User::query()
            ->where('id', $designerId)
            ->where('role', 'designer')
            ->firstOrFail(['id', 'name']);

        return view('referrals.suppliers.create', [
            'designer' => $designer,
            'sphereOptions' => $this->sphereOptions(),
            'submitUrl' => URL::signedRoute('referrals.suppliers.store', ['designer' => $designerId]),
        ]);
    }   
    private function sphereOptions(): array
    {
        $all = trans('supplier_spheres');
        if (! is_array($all) || $all === []) {
            return [];
        }

        return $all;
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(Response::HTTP_FORBIDDEN, __('referrals.invalid_signature'));
        }

        $designerId = (int) $request->query('designer');
        $designer = User::query()
            ->where('id', $designerId)
            ->where('role', 'designer')
            ->firstOrFail(['id', 'name']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'recommend' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'sphere' => ['nullable', 'string', 'max:255'],
            'work_terms_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'work_terms_value' => ['nullable', 'string', 'max:255'],
            'brands' => ['nullable', 'array'],
            'brands.*' => ['nullable', 'string', 'max:255'],
            'cities_presence' => ['nullable', 'array'],
            'cities_presence.*' => ['nullable', 'string', 'max:255'],
            'comment_main' => ['nullable', 'string'],
            'org_form' => ['nullable', Rule::in(['ooo', 'ip'])],
            'inn' => ['nullable', 'string', 'max:255'],
            'kpp' => ['nullable', 'string', 'max:255'],
            'ogrn' => ['nullable', 'string', 'max:255'],
            'okpo' => ['nullable', 'string', 'max:255'],
            'legal_address' => ['nullable', 'string', 'max:1000'],
            'actual_address' => ['nullable', 'string', 'max:1000'],
            'address_match' => ['nullable', 'boolean'],
            'director' => ['nullable', 'string', 'max:255'],
            'accountant' => ['nullable', 'string', 'max:255'],
            'bik' => ['nullable', 'string', 'max:255'],
            'bank' => ['nullable', 'string', 'max:255'],
            'checking_account' => ['nullable', 'string', 'max:255'],
            'corr_account' => ['nullable', 'string', 'max:255'],
            'comment_bank' => ['nullable', 'string'],
        ]);

        $supplier = new Supplier();
        $supplier->user_id = $designer->id;
        $supplier->name = $data['name'];
        $supplier->recommend = $request->boolean('recommend');
        $supplier->phone = $data['phone'] ?? null;
        $supplier->email = $data['email'] ?? null;
        $supplier->telegram = $data['telegram'] ?? null;
        $supplier->whatsapp = $data['whatsapp'] ?? null;
        $supplier->website = $data['website'] ?? null;
        $supplier->city = $data['city'] ?? null;
        $supplier->address = $data['address'] ?? null;
        $supplier->sphere = $data['sphere'] ?? null;
        $supplier->work_terms_type = $data['work_terms_type'] ?? null;
        $supplier->work_terms_value = $data['work_terms_value'] ?? null;
        $supplier->brands = $this->cleanStringArray($request->input('brands', []));
        $supplier->cities_presence = $this->cleanStringArray($request->input('cities_presence', []));
        $supplier->comment = $data['comment_main'] ?? null;
        $supplier->org_form = $data['org_form'] ?? 'ooo';
        $supplier->inn = $data['inn'] ?? null;
        $supplier->kpp = $data['kpp'] ?? null;
        $supplier->ogrn = $data['ogrn'] ?? null;
        $supplier->okpo = $data['okpo'] ?? null;
        $supplier->legal_address = $data['legal_address'] ?? null;
        $supplier->actual_address = $data['actual_address'] ?? null;
        $supplier->address_match = $request->boolean('address_match');
        $supplier->director = $data['director'] ?? null;
        $supplier->accountant = $data['accountant'] ?? null;
        $supplier->bik = $data['bik'] ?? null;
        $supplier->bank = $data['bank'] ?? null;
        $supplier->checking_account = $data['checking_account'] ?? null;
        $supplier->corr_account = $data['corr_account'] ?? null;
        $supplier->comment_bank = $data['comment_bank'] ?? null;

        $supplier->is_referral_submitted = true;
        $supplier->is_confirmed_by_designer = false;
        $supplier->moderation_status = 'pending';
        $supplier->moderation_comment = null;
        $supplier->moderation_reviewer_id = null;
        $supplier->moderation_reviewed_at = null;
        $supplier->save();

        UserNotification::query()->create([
            'user_id' => $designer->id,
            'title' => __('notifications.referral_supplier_title'),
            'comment' => __('notifications.referral_supplier_comment', ['name' => $supplier->name]),
            'is_read' => false,
            'related_supplier_id' => $supplier->id,
            'action_key' => 'confirm_referral_supplier',
        ]);

        return redirect()
            ->to(URL::signedRoute('referrals.suppliers.create', ['designer' => $designerId]))
            ->with('status', __('referrals.supplier_submitted'));
    }

    private function cleanStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($item) {
            return is_string($item) ? trim($item) : '';
        }, $value), fn ($v) => $v !== '')));
    }
}
