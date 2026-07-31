<?php

namespace App\Services\Crm;

use App\Models\Project;
use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\SupplierProduct;
use App\Support\OrderOfferNotifier;
use App\Support\PublicFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SupplyService
{
    public const STATUSES = ['draft', 'order_created', 'order_confirmed', 'advance_payment', 'full_payment', 'delivery_completed'];

    public function list(Project $project)
    {
        return $project->supplierOrders()->where('user_id', $project->user_id)->with('supplier')->latest('id')->get();
    }

    public function create(Project $project, int $userId, array $data, array $files = []): Supplier_orders
    {
        $order = new Supplier_orders(['user_id' => $userId, 'project_id' => $project->id]);

        return $this->save($project, $order, $data, $files);
    }

    public function update(Project $project, Supplier_orders $order, array $data, array $files = []): Supplier_orders
    {
        if ((int) $order->project_id !== (int) $project->id || (int) $order->user_id !== (int) $project->user_id) {
            abort(404);
        }

        return $this->save($project, $order, $data, $files);
    }

    public function delete(Supplier_orders $order): void
    {
        foreach ((array) $order->files as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
            }
        }
        $order->delete();
    }

    public function updateStatus(Supplier_orders $order, string $status): Supplier_orders
    {
        if (! $order->isInFunnel()) {
            throw ValidationException::withMessages(['status' => ['The offer must be accepted first.']]);
        }
        $order->update(['status' => $status]);
        $this->completeSideEffects($order);

        return $order->fresh(['project', 'supplier']);
    }

    public function sendProposal(Supplier_orders $order, ?string $percent, ?string $message): Supplier_orders
    {
        $wasSent = (bool) $order->is_sent_to_supplier;
        if ($percent !== null) {
            $order->bonus_percent = $percent;
        }
        $order->offer_message = $this->stringOrNull($message);
        $order->is_sent_to_supplier = true;

        if (! $wasSent || ! in_array($order->offer_status, [Supplier_orders::OFFER_ACCEPTED, Supplier_orders::OFFER_PENDING_SUPPLIER, Supplier_orders::OFFER_PENDING_DESIGNER], true)) {
            $order->offer_status = Supplier_orders::OFFER_PENDING_SUPPLIER;
            $order->status = 'order_created';
            $order->appendOfferHistory('designer', $order->bonus_percent !== null ? (float) $order->bonus_percent : null, $order->offer_message);
            $event = 'new';
        } elseif ($order->offer_status === Supplier_orders::OFFER_ACCEPTED) {
            $order->offer_status = Supplier_orders::OFFER_PENDING_SUPPLIER;
            $order->appendOfferHistory('designer', (float) $order->bonus_percent, $order->offer_message ?: 'renegotiate');
            $event = 'new';
        } else {
            $order->offer_status = Supplier_orders::OFFER_PENDING_SUPPLIER;
            $order->appendOfferHistory('designer', $order->bonus_percent !== null ? (float) $order->bonus_percent : null, $order->offer_message);
            $event = 'counter';
        }
        $order->save();
        OrderOfferNotifier::notify($order, $event, 'supplier');

        return $order->fresh(['project', 'supplier']);
    }

    public function acceptProposal(Supplier_orders $order): Supplier_orders
    {
        $this->assertDesignerCanRespond($order);
        $order->offer_status = Supplier_orders::OFFER_ACCEPTED;
        $order->status = 'order_confirmed';
        $order->offer_message = null;
        $order->appendOfferHistory('designer', $order->bonus_percent !== null ? (float) $order->bonus_percent : null, 'accepted');
        $order->save();
        OrderOfferNotifier::notify($order, 'accepted', 'supplier');

        return $order->fresh(['project', 'supplier']);
    }

    public function rejectProposal(Supplier_orders $order): Supplier_orders
    {
        $this->assertDesignerCanRespond($order);
        $order->offer_status = Supplier_orders::OFFER_REJECTED;
        $order->appendOfferHistory('designer', $order->bonus_percent !== null ? (float) $order->bonus_percent : null, 'rejected');
        $order->save();
        OrderOfferNotifier::notify($order, 'rejected', 'supplier');

        return $order->fresh(['project', 'supplier']);
    }

    public function counterProposal(Supplier_orders $order, string $percent, ?string $message): Supplier_orders
    {
        $this->assertDesignerCanRespond($order);
        if ($order->bonus_percent !== null && abs((float) $order->bonus_percent - (float) $percent) < 0.0001) {
            throw ValidationException::withMessages(['bonus_percent' => ['Counter percentage must differ from the current proposal.']]);
        }
        $order->bonus_percent = $percent;
        $order->offer_message = $this->stringOrNull($message);
        $order->offer_status = Supplier_orders::OFFER_PENDING_SUPPLIER;
        $order->appendOfferHistory('designer', (float) $percent, $order->offer_message);
        $order->save();
        OrderOfferNotifier::notify($order, 'counter', 'supplier');

        return $order->fresh(['project', 'supplier']);
    }

    private function save(Project $project, Supplier_orders $order, array $data, array $files): Supplier_orders
    {
        return DB::transaction(function () use ($project, $order, $data, $files) {
            $supplierId = (int) $data['supplier_id'];
            $this->assertSupplierAllowed($supplierId, (int) $project->user_id);
            $stepIds = Supplier_orders::normalizeStepIds($data['included_step_ids'] ?? $order->included_step_ids);
            if ($stepIds !== [] && Supplier_orders::countStepsInProject((int) $project->id, $stepIds) !== count($stepIds)) {
                throw ValidationException::withMessages(['included_step_ids' => ['Checklist items must belong to this project.']]);
            }

            $order->project_id = $project->id;
            $order->user_id = $project->user_id;
            $order->client_id = $project->client_id;
            $order->supplier_id = $supplierId;
            $order->included_step_ids = $stepIds;
            foreach (['summa', 'category', 'mark', 'room', 'date_planned', 'date_actual', 'prepayment_date', 'payment_date', 'prepayment_amount', 'payment_amount', 'comment'] as $field) {
                if (array_key_exists($field, $data)) {
                    $order->{$field} = $data[$field];
                }
            }
            $order->links = array_values(array_filter((array) ($data['links'] ?? $order->links), fn ($v) => trim((string) $v) !== ''));
            if (array_key_exists('bonus_percent', $data)) {
                $order->bonus_percent = $data['bonus_percent'];
            }
            $order->product_items = $this->productItems((array) ($data['items'] ?? $data['product_items'] ?? $order->product_items), $supplierId);
            $order->files = $this->syncFiles($order, (array) ($data['existing_files'] ?? $order->files), $files);

            $send = (bool) ($data['send_to_supplier'] ?? false);
            if (! $order->exists && ! $send) {
                $order->status = 'draft';
                $order->is_sent_to_supplier = false;
            } elseif (array_key_exists('status', $data)) {
                $order->status = $data['status'];
            }
            $order->save();

            if ($send) {
                return $this->sendProposal($order, $data['bonus_percent'] ?? null, $data['message'] ?? null);
            }

            return $order->fresh(['project', 'supplier']);
        });
    }

    private function productItems(array $items, int $supplierId): array
    {
        $ids = collect($items)->map(fn ($item) => (int) (is_array($item) ? ($item['product_id'] ?? $item['id'] ?? 0) : 0))->filter()->unique()->all();
        $products = SupplierProduct::query()->where('supplier_id', $supplierId)->whereIn('id', $ids)->get()->keyBy('id');
        $out = [];
        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? $item['id'] ?? 0));
            if ($product) {
                $out[] = ['product_id' => $product->id, 'name' => $product->name, 'qty' => max(1, (int) ($item['qty'] ?? 1)), 'price' => $product->price, 'unit' => $product->unit];
            }
        }
        return $out;
    }

    private function syncFiles(Supplier_orders $order, array $existing, array $uploads): array
    {
        $owned = array_values(array_filter((array) $order->files, 'is_string'));
        $keep = $order->exists ? array_values(array_intersect($owned, $existing)) : [];
        foreach ($uploads as $file) {
            if ($file instanceof UploadedFile) {
                $keep[] = PublicFileStorage::store($file, 'supplier-orders');
            }
        }
        foreach (array_diff($owned, $keep) as $removed) {
            Storage::disk('public')->delete($removed);
        }
        return array_values(array_unique($keep));
    }

    private function assertSupplierAllowed(int $supplierId, int $userId): void
    {
        $allowed = Supplier::query()->whereKey($supplierId)->where(function ($q) use ($userId) {
            $q->where('created_by_user_id', $userId)->orWhere(fn ($legacy) => $legacy->whereNull('created_by_user_id')->where('user_id', $userId))
                ->orWhere(fn ($public) => $public->where('profile_status', 'active')->where('moderation_status', 'approved'));
        })->exists();
        if (! $allowed) abort(422, 'Supplier is not available to this designer.');
    }

    private function assertDesignerCanRespond(Supplier_orders $order): void
    {
        if (! $order->canDesignerRespondToOffer()) abort(422, 'Offer action is unavailable.');
    }

    private function completeSideEffects(Supplier_orders $order): void
    {
        if ($order->status === 'delivery_completed') {
            $order->load('supplier:id,user_id,name', 'designer:id,name');
            \App\Models\Review::requestReviewsForCompletedOrder($order);
            \App\Support\CashbackAccrual::forCompletedOrder($order);
        }
    }

    private function stringOrNull(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? trim($value) : null;
    }
}
