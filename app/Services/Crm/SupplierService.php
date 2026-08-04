<?php

namespace App\Services\Crm;

use App\Models\DesignerFavoriteSupplier;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;
use App\Support\PublicFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupplierService
{
    public function list(int $designerId, array $filters = [])
    {
        $query = $this->visible($designerId);
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")->orWhere('sphere', 'like', "%{$search}%"));
        }
        foreach (['city', 'sphere'] as $field) {
            if (! empty($filters[$field])) $query->where($field, $filters[$field]);
        }
        if (! empty($filters['favorite'])) {
            $query->whereIn('id', DesignerFavoriteSupplier::query()->where('designer_user_id', $designerId)->select('supplier_id'));
        }
        return $query->orderBy('name')->get();
    }

    public function findVisible(int $designerId, int $id): Supplier
    {
        return $this->visible($designerId)->findOrFail($id);
    }

    public function create(int $designerId, array $data, ?UploadedFile $logo = null): Supplier
    {
        return DB::transaction(function () use ($designerId, $data, $logo) {
            $password = Str::password(length: 12, letters: true, numbers: true, symbols: false);
            $user = new User;
            $user->forceFill(['role' => 'supplier', 'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($password), 'must_change_password' => true])->save();
            $supplier = new Supplier(['user_id' => $user->id, 'created_by_user_id' => $designerId, 'profile_status' => 'draft', 'moderation_status' => 'pending']);
            $supplier->setTemporaryPassword($password);
            return $this->fill($supplier, $data, $logo);
        });
    }

    public function update(int $designerId, int $id, array $data, ?UploadedFile $logo = null): Supplier
    {
        $supplier = $this->owned($designerId)->findOrFail($id);
        if ((string) $supplier->moderation_status === 'approved') abort(422, 'Approved suppliers are locked.');
        if ($supplier->user && $supplier->user->role === 'supplier') {
            $supplier->user->fill(['name' => $data['name'], 'email' => $data['email']])->save();
        }
        return $this->fill($supplier, $data, $logo);
    }

    public function delete(int $designerId, int $id): void
    {
        $supplier = $this->owned($designerId)->findOrFail($id);
        if ((string) $supplier->moderation_status === 'approved') abort(422, 'Approved suppliers are locked.');
        if ($supplier->logo) Storage::disk('public')->delete($supplier->logo);
        DB::transaction(function () use ($supplier) {
            $user = $supplier->user;
            $supplier->delete();
            if ($user && $user->role === 'supplier') $user->delete();
        });
    }

    public function toggleFavorite(int $designerId, int $id): bool
    {
        $this->findVisible($designerId, $id);
        $favorite = DesignerFavoriteSupplier::query()->where('designer_user_id', $designerId)->where('supplier_id', $id)->first();
        if ($favorite) {
            $favorite->delete();
            return false;
        }
        DesignerFavoriteSupplier::create(['designer_user_id' => $designerId, 'supplier_id' => $id]);
        return true;
    }

    public function products(int $designerId, int $supplierId, array $filters = [])
    {
        $this->findVisible($designerId, $supplierId);
        $query = SupplierProduct::query()->where('supplier_id', $supplierId);
        if ($search = trim((string) ($filters['search'] ?? ''))) $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        if (! empty($filters['category'])) $query->where('category', $filters['category']);
        return $query->orderBy('name')->get();
    }

    public function favoriteIds(int $designerId): array
    {
        return DesignerFavoriteSupplier::query()->where('designer_user_id', $designerId)->pluck('supplier_id')->map(fn ($id) => (int) $id)->all();
    }

    private function fill(Supplier $supplier, array $data, ?UploadedFile $logo): Supplier
    {
        if (! empty($data['remove_logo']) && $supplier->logo) {
            Storage::disk('public')->delete($supplier->logo);
            $supplier->logo = null;
        }
        if ($logo) {
            if ($supplier->logo) Storage::disk('public')->delete($supplier->logo);
            $supplier->logo = PublicFileStorage::store($logo, 'suppliers');
        }
        $fillable = array_flip((new Supplier)->getFillable());
        $supplier->fill(array_intersect_key($data, $fillable));
        foreach (['brands', 'cities_presence'] as $field) {
            if (array_key_exists($field, $data)) $supplier->{$field} = $this->strings((array) $data[$field]);
        }
        if (array_key_exists('comment_main', $data)) $supplier->comment = $data['comment_main'];
        $supplier->save();
        return $supplier->fresh();
    }

    private function visible(int $designerId)
    {
        return Supplier::query()->where(function ($q) use ($designerId) {
            $q->where(fn ($public) => $public->where('profile_status', 'active')->where('moderation_status', 'approved'))
                ->orWhere(fn ($owned) => $owned->where('created_by_user_id', $designerId)->orWhere(fn ($legacy) => $legacy->whereNull('created_by_user_id')->where('user_id', $designerId)));
        });
    }

    private function owned(int $designerId)
    {
        return Supplier::query()->where(fn ($q) => $q->where('created_by_user_id', $designerId)->orWhere(fn ($legacy) => $legacy->whereNull('created_by_user_id')->where('user_id', $designerId)));
    }

    private function strings(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $values))));
    }
}
