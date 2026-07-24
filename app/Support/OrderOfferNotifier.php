<?php

namespace App\Support;

use App\Models\Supplier;
use App\Models\Supplier_orders;
use App\Models\UserNotification;

class OrderOfferNotifier
{
    public static function notify(Supplier_orders $order, string $event, string $toRole): void
    {
        $order->loadMissing(['supplier:id,user_id,name', 'designer:id,name']);

        $percent = $order->bonus_percent !== null ? rtrim(rtrim(number_format((float) $order->bonus_percent, 2, '.', ''), '0'), '.') : '—';

        $title = match ($event) {
            'new' => __('notifications.offer_new_title'),
            'accepted' => __('notifications.offer_accepted_title'),
            'rejected' => __('notifications.offer_rejected_title'),
            'counter' => __('notifications.offer_counter_title'),
            default => __('notifications.offer_new_title'),
        };

        $comment = match ($event) {
            'new' => __('notifications.offer_new_comment', [
                'order' => (string) $order->id,
                'percent' => $percent,
            ]),
            'accepted' => __('notifications.offer_accepted_comment', [
                'order' => (string) $order->id,
                'percent' => $percent,
            ]),
            'rejected' => __('notifications.offer_rejected_comment', [
                'order' => (string) $order->id,
            ]),
            'counter' => __('notifications.offer_counter_comment', [
                'order' => (string) $order->id,
                'percent' => $percent,
                'by' => $toRole === 'designer'
                    ? (string) ($order->supplier?->name ?? __('supplier-orders.supplier'))
                    : (string) ($order->designer?->name ?? __('supplier-orders.designer')),
            ]),
            default => '',
        };

        $userId = null;
        $relatedSupplierId = (int) ($order->supplier_id ?? 0);

        if ($toRole === 'supplier') {
            $userId = (int) ($order->supplier?->user_id ?? 0);
        } else {
            $userId = (int) ($order->user_id ?? 0);
        }

        if ($userId <= 0) {
            return;
        }

        UserNotification::query()->create([
            'user_id' => $userId,
            'title' => $title,
            'comment' => $comment,
            'is_read' => false,
            'related_supplier_id' => $relatedSupplierId > 0 ? $relatedSupplierId : null,
            'related_order_id' => (int) $order->id,
            'action_key' => 'order_offer',
        ]);
    }

    public static function notifyWithdrawn(int $supplierId, int $orderId, string $designerName = ''): void
    {
        $supplier = Supplier::query()->find($supplierId);
        if (! $supplier || (int) ($supplier->user_id ?? 0) <= 0) {
            return;
        }

        UserNotification::query()->create([
            'user_id' => (int) $supplier->user_id,
            'title' => __('notifications.order_withdrawn_title'),
            'comment' => __('notifications.order_withdrawn_comment', [
                'order' => (string) $orderId,
                'designer' => $designerName !== '' ? $designerName : __('supplier-orders.designer'),
            ]),
            'is_read' => false,
            'related_supplier_id' => (int) $supplier->id,
            'related_order_id' => $orderId,
            'action_key' => 'supplier_order',
        ]);
    }

    /**
     * @param  list<string>  $changedFields
     */
    public static function notifyOrderUpdated(Supplier_orders $order, string $designerName, array $changedFields = []): void
    {
        $order->loadMissing(['supplier:id,user_id,name', 'project:id,name']);

        $userId = (int) ($order->supplier?->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $projectName = (string) ($order->project?->name ?? '');

        UserNotification::query()->create([
            'user_id' => $userId,
            'title' => __('notifications.order_updated_title'),
            'comment' => __('notifications.order_updated_comment', [
                'order' => (string) $order->id,
                'project' => $projectName !== '' ? $projectName : '—',
                'designer' => $designerName !== '' ? $designerName : __('supplier-orders.designer'),
            ]),
            'is_read' => false,
            'related_supplier_id' => (int) ($order->supplier_id ?? 0) ?: null,
            'related_order_id' => (int) $order->id,
            'action_key' => 'supplier_order_updated',
        ]);
    }
}
