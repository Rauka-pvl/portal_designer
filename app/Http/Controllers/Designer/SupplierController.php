<?php

namespace App\Http\Controllers\Designer;

use App\Http\Controllers\Controller;
use App\Models\DesignerFavoriteSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PublicFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $suppliers = $this->visibleToDesignerQuery($userId)
            ->orderByDesc('id')
            ->get();

        $favoriteLookup = $this->favoriteLookupForDesigner($userId);

        $suppliers->each(function (Supplier $supplier) use ($favoriteLookup): void {
            $supplier->setAttribute('is_favorite', (bool) ($favoriteLookup[(int) $supplier->id] ?? false));
        });

        $cities = $suppliers
            ->pluck('city')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->sort()
            ->values();

        $brands = $suppliers
            ->flatMap(function (Supplier $supplier) {
                return is_array($supplier->brands) ? $supplier->brands : [];
            })
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->sort()
            ->values();

        $spheres = $suppliers
            ->pluck('sphere')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->map(fn ($v) => trim($v))
            ->unique()
            ->sort()
            ->values();

        return view('designer.suppliers.index', [
            'suppliers' => $suppliers,
            'suppliersData' => $suppliers->map(fn (Supplier $s) => $this->payloadForDesigner($s, $userId, $favoriteLookup))->values(),
            'cities' => $cities,
            'brands' => $brands,
            'spheres' => $spheres,
            'sphereOptions' => $this->sphereOptions(),
        ]);
    }

    public function show(Request $request, int $supplierId)
    {
        $userId = (int) $request->user()->id;
        $supplier = Supplier::query()->findOrFail($supplierId);

        $isOwned = $this->isOwnedByDesigner($supplier, $userId);
        $canManage = $isOwned && ! $this->isLockedAfterModerationApproval($supplier);
        $isPublicApproved = (string) $supplier->profile_status === 'active'
            && (string) $supplier->moderation_status === 'approved';

        if (! $isOwned && ! $isPublicApproved) {
            abort(404);
        }

        $favoriteLookup = $this->favoriteLookupForDesigner($userId);

        $supplier->setAttribute('is_favorite', (bool) ($favoriteLookup[(int) $supplier->id] ?? false));
        $payload = $this->payloadForDesigner($supplier, $userId, $favoriteLookup);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        $isReadOnly = $request->boolean('readonly') || ! $canManage;

        return view('designer.suppliers.show', [
            'supplier' => $supplier,
            'supplierData' => $payload,
            'sphereOptions' => $this->sphereOptions(),
            'isReadOnly' => $isReadOnly,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateSupplierData($request);
        $temporaryPassword = $this->generateTemporaryPassword();
        $supplier = DB::transaction(function () use ($data, $request, $temporaryPassword) {
            $supplierUser = User::query()->create([
                'role' => 'supplier',
                'name' => trim((string) $data['name']),
                'email' => trim((string) $data['email']),
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'password_changed_at' => null,
            ]);

            $supplier = new Supplier;
            $supplier->user_id = (int) $supplierUser->id;
            $supplier->created_by_user_id = (int) $request->user()->id;
            $supplier->profile_status = 'draft';
            $supplier->setTemporaryPassword($temporaryPassword);

            $this->fillAndSave($request, $supplier, $data);

            // Автоматически отправляем на модерацию после создания
            $supplier->moderation_status = 'pending';
            $supplier->moderation_reviewer_id = null;
            $supplier->moderation_reviewed_at = null;
            $supplier->save();

            return $supplier;
        });

        return response()->json([
            'success' => true,
            'message' => __('suppliers.added'),
            'supplier' => $this->payloadForDesigner(
                $supplier,
                (int) $request->user()->id,
                $this->favoriteLookupForDesigner((int) $request->user()->id)
            ),
        ]);
    }

    public function update(Request $request, int $supplierId)
    {
        $userId = (int) $request->user()->id;
        $supplier = $this->ownedByDesignerQuery($userId)->findOrFail($supplierId);
        if ($this->isLockedAfterModerationApproval($supplier)) {
            return $this->lockedSupplierResponse($request);
        }
        $data = $this->validateSupplierData($request, $supplier);

        $supplierUser = $supplier->user;
        if ($supplierUser && (string) $supplierUser->role === 'supplier') {
            $supplierUser->name = trim((string) $data['name']);
            $supplierUser->email = trim((string) $data['email']);
            $supplierUser->save();
        }

        $this->fillAndSave($request, $supplier, $data);

        if (! ($request->expectsJson() || $request->wantsJson())) {
            return redirect()->route('suppliers.show', $supplier->id)->with('status', __('suppliers.updated'));
        }

        return response()->json([
            'success' => true,
            'message' => __('suppliers.updated'),
            'supplier' => $this->payloadForDesigner(
                $supplier,
                $userId,
                $this->favoriteLookupForDesigner($userId)
            ),
        ]);
    }

    public function destroy(Request $request, int $supplierId)
    {
        $supplier = $this->ownedByDesignerQuery((int) $request->user()->id)->findOrFail($supplierId);
        if ($this->isLockedAfterModerationApproval($supplier)) {
            return $this->lockedSupplierResponse($request);
        }
        if (! empty($supplier->logo)) {
            Storage::disk('public')->delete($supplier->logo);
        }
        DB::transaction(function () use ($supplier): void {
            $supplierUser = $supplier->user;

            $supplier->delete();

            if ($supplierUser && (string) $supplierUser->role === 'supplier') {
                $supplierUser->delete();
            }
        });
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
        $userId = (int) $request->user()->id;

        $supplierAllowed = $this->visibleToDesignerQuery($userId)
            ->whereKey($supplierId)
            ->exists();
        if (! $supplierAllowed) {
            abort(404);
        }

        $favorite = DesignerFavoriteSupplier::query()
            ->where('designer_user_id', $userId)
            ->where('supplier_id', $supplierId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorite = false;
        } else {
            DesignerFavoriteSupplier::query()->create([
                'designer_user_id' => $userId,
                'supplier_id' => $supplierId,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
        ]);
    }

    private function fillAndSave(Request $request, Supplier $supplier, array $data): void
    {
        if ($request->boolean('remove_logo') && ! empty($supplier->logo)) {
            Storage::disk('public')->delete($supplier->logo);
            $supplier->logo = null;
        }

        if ($request->hasFile('logo')) {
            if (! empty($supplier->logo)) {
                Storage::disk('public')->delete($supplier->logo);
            }
            $supplier->logo = PublicFileStorage::store($request->file('logo'), 'suppliers');
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

    private function validateSupplierData(Request $request, ?Supplier $supplier = null): array
    {
        $currentSupplierUserId = null;
        if ($supplier && $supplier->user && (string) $supplier->user->role === 'supplier') {
            $currentSupplierUserId = (int) $supplier->user_id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'recommend' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($currentSupplierUserId)],
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
            'inn' => ['required', 'string', 'max:255'],
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

    private function favoriteLookupForDesigner(int $designerUserId): array
    {
        return DesignerFavoriteSupplier::query()
            ->where('designer_user_id', $designerUserId)
            ->pluck('supplier_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    private function payloadForDesigner(Supplier $supplier, int $designerUserId, array $favoriteLookup): array
    {
        $base = $this->payload($supplier);
        $isOwner = $this->isOwnedByDesigner($supplier, $designerUserId);
        $canManage = $isOwner && ! $this->isLockedAfterModerationApproval($supplier);

        $base['is_owned_by_designer'] = $isOwner;
        $base['designer_can_manage'] = $canManage;
        $base['designer_can_place_order'] = true;
        $base['is_favorite'] = (bool) ($favoriteLookup[(int) $supplier->id] ?? false);

        return $base;
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
            'user_id' => $supplier->user_id,
            'created_by_user_id' => $supplier->created_by_user_id,
            'profile_status' => $supplier->profile_status,
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
            'is_favorite' => (bool) ($supplier->is_favorite ?? false),
            'logo' => $supplier->logo,
            'logo_url' => $supplier->logo ? asset('storage/'.$supplier->logo) : null,
        ];
    }

    private function visibleToDesignerQuery(int $designerUserId)
    {
        return Supplier::query()->where(function ($q) use ($designerUserId) {
            $q->where(function ($q2) {
                $q2->where('profile_status', 'active')
                    ->where('moderation_status', 'approved');
            })->orWhere(function ($q2) use ($designerUserId) {
                $q2->where('created_by_user_id', $designerUserId)
                    ->orWhere(function ($legacy) use ($designerUserId) {
                        $legacy->whereNull('created_by_user_id')
                            ->where('user_id', $designerUserId);
                    });
            });
        });
    }

    private function ownedByDesignerQuery(int $designerUserId)
    {
        return Supplier::query()->where(function ($q) use ($designerUserId) {
            $q->where('created_by_user_id', $designerUserId)
                ->orWhere(function ($legacy) use ($designerUserId) {
                    $legacy->whereNull('created_by_user_id')
                        ->where('user_id', $designerUserId);
                });
        });
    }

    private function isOwnedByDesigner(Supplier $supplier, int $designerUserId): bool
    {
        if ((int) ($supplier->created_by_user_id ?? 0) === $designerUserId) {
            return true;
        }

        return (int) ($supplier->created_by_user_id ?? 0) < 1
            && (int) ($supplier->user_id ?? 0) === $designerUserId;
    }

    private function isLockedAfterModerationApproval(Supplier $supplier): bool
    {
        return (string) ($supplier->moderation_status ?? '') === 'approved';
    }

    private function lockedSupplierResponse(Request $request)
    {
        $message = __('suppliers.approved_locked');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()->back()->withErrors([
            'supplier' => $message,
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return Str::password(length: 12, letters: true, numbers: true, symbols: false, spaces: false);
    }
}
