<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = Supplier::query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('is_confirmed_by_designer', true)
                    ->orWhereNull('is_confirmed_by_designer');
            });

        $suppliers = $query
            ->orderByDesc('id')
            ->get();

        $cities = Supplier::query()
            ->where('user_id', $userId)
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $brands = Supplier::query()
            ->where('user_id', $userId)
            ->get(['brands'])
            ->flatMap(function (Supplier $supplier) {
                return is_array($supplier->brands) ? $supplier->brands : [];
            })
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->sort()
            ->values();

        $spheres = Supplier::query()
            ->where('user_id', $userId)
            ->whereNotNull('sphere')
            ->where('sphere', '!=', '')
            ->distinct()
            ->orderBy('sphere')
            ->pluck('sphere');

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'suppliersData' => $suppliers->map(fn (Supplier $s) => $this->payload($s))->values(),
            'cities' => $cities,
            'brands' => $brands,
            'spheres' => $spheres,
            'sphereOptions' => $this->sphereOptions(),
        ]);
    }

    public function show(Request $request, int $supplierId)
    {
        $supplier = Supplier::where('user_id', $request->user()->id)->findOrFail($supplierId);
        $payload = $this->payload($supplier);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return view('suppliers.show', [
            'supplier' => $supplier,
            'supplierData' => $payload,
            'sphereOptions' => $this->sphereOptions(),
            'isReadOnly' => $request->boolean('readonly'),
        ]);
    }

    public function store(Request $request)
    {
        $supplier = new Supplier;
        $supplier->user_id = $request->user()->id;

        $this->fillAndSave($request, $supplier);

        // Автоматически отправляем на модерацию после создания
        $supplier->moderation_status = 'pending';
        $supplier->moderation_reviewer_id = null;
        $supplier->moderation_reviewed_at = null;
        $supplier->save();

        return response()->json([
            'success' => true,
            'message' => __('suppliers.added'),
            'supplier' => $this->payload($supplier),
        ]);
    }

    public function update(Request $request, int $supplierId)
    {
        $supplier = Supplier::where('user_id', $request->user()->id)->findOrFail($supplierId);

        $this->fillAndSave($request, $supplier);

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('suppliers.show', $supplier->id)->with('status', __('suppliers.updated'));
        }

        return response()->json([
            'success' => true,
            'message' => __('suppliers.updated'),
            'supplier' => $this->payload($supplier),
        ]);
    }

    public function destroy(Request $request, int $supplierId)
    {
        $supplier = Supplier::where('user_id', $request->user()->id)->findOrFail($supplierId);
        if (! empty($supplier->logo)) {
            Storage::disk('public')->delete($supplier->logo);
        }
        $supplier->delete();
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('suppliers.deleted'),
            ]);
        }

        return redirect()->route('suppliers.index')->with('status', __('suppliers.deleted'));
    }

    public function toggleFavorite(Request $request, int $supplierId)
    {
        $supplier = Supplier::where('user_id', $request->user()->id)->findOrFail($supplierId);
        $supplier->is_favorite = ! (bool) $supplier->is_favorite;
        $supplier->save();

        return response()->json([
            'success' => true,
            'is_favorite' => (bool) $supplier->is_favorite,
        ]);
    }

    private function fillAndSave(Request $request, Supplier $supplier): void
    {
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
            'logo' => ['nullable', 'image', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && ! empty($supplier->logo)) {
            Storage::disk('public')->delete($supplier->logo);
            $supplier->logo = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($supplier->logo)) {
                Storage::disk('public')->delete($supplier->logo);
            }
            $supplier->logo = $request->file('logo')->store('suppliers', 'public');
        }

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

        $supplier->save();
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

    private function sphereOptions(): array
    {
        $all = trans('supplier_spheres');
        if (! is_array($all) || $all === []) {
            return [];
        }

        return $all;
    }

    private function payload(Supplier $supplier): array
    {
        $sphere = $supplier->sphere;
        $sphereDisplay = $sphere;
        if (is_string($sphere) && trim($sphere) !== '') {
            $translated = __('supplier_spheres.'.$sphere);
            $sphereDisplay = $translated !== 'supplier_spheres.'.$sphere ? $translated : $sphere;
        }

        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'recommend' => (bool) $supplier->recommend,
            'phone' => $supplier->phone,
            'email' => $supplier->email,
            'telegram' => $supplier->telegram,
            'whatsapp' => $supplier->whatsapp,
            'website' => $supplier->website,
            'city' => $supplier->city,
            'address' => $supplier->address,
            'sphere' => $sphere,
            'sphere_display' => $sphereDisplay,
            'work_terms_type' => $supplier->work_terms_type,
            'work_terms_value' => $supplier->work_terms_value,
            'brands' => is_array($supplier->brands) ? $supplier->brands : [],
            'brand_display' => is_array($supplier->brands) && ! empty($supplier->brands)
                ? implode(', ', $supplier->brands)
                : null,
            'cities_presence' => is_array($supplier->cities_presence) ? $supplier->cities_presence : [],
            'comment' => $supplier->comment,

            // Moderation
            'moderation_status' => $supplier->moderation_status,
            'moderation_comment' => $supplier->moderation_comment,
            'org_form' => $supplier->org_form,
            'inn' => $supplier->inn,
            'kpp' => $supplier->kpp,
            'ogrn' => $supplier->ogrn,
            'okpo' => $supplier->okpo,
            'legal_address' => $supplier->legal_address,
            'actual_address' => $supplier->actual_address,
            'address_match' => (bool) $supplier->address_match,
            'director' => $supplier->director,
            'accountant' => $supplier->accountant,
            'bik' => $supplier->bik,
            'bank' => $supplier->bank,
            'checking_account' => $supplier->checking_account,
            'corr_account' => $supplier->corr_account,
            'comment_bank' => $supplier->comment_bank,
            'is_favorite' => (bool) $supplier->is_favorite,
            'logo' => $supplier->logo,
            'logo_url' => $supplier->logo ? asset('storage/'.$supplier->logo) : null,
        ];
    }
}
