<?php

namespace App\Support;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Route;

class NotificationActions
{
    /**
     * Resolve display actions for a notification based on action_key / related ids.
     *
     * @return array{
     *     type: string,
     *     icon: string,
     *     primary: ?array,
     *     secondary: ?array,
     *     menu_secondary: ?array,
     *     row_href: ?string,
     *     accent_primary: bool
     * }
     */
    public static function resolve(UserNotification $notification, User $user): array
    {
        $type = self::typeOf($notification);
        $isDesigner = ($user->role ?? '') === 'designer';
        $isSupplier = ($user->role ?? '') === 'supplier';

        $primary = null;
        $secondary = null;
        $menuSecondary = null;
        $rowHref = null;
        $accent = false;

        switch ($type) {
            case 'confirm_referral_supplier':
                if ($isDesigner && (int) $notification->related_supplier_id > 0) {
                    $viewUrl = self::supplierUrl((int) $notification->related_supplier_id);
                    if ($notification->action_key === 'confirm_referral_supplier') {
                        $primary = [
                            'key' => 'add_supplier',
                            'label' => __('notifications.add_supplier'),
                            'mode' => 'post',
                            'url' => route('notifications.confirm_referral_supplier', $notification->id),
                        ];
                        $accent = true;
                        if ($viewUrl) {
                            $secondary = [
                                'key' => 'view_supplier',
                                'label' => __('notifications.view_supplier_short'),
                                'mode' => 'link',
                                'url' => $viewUrl,
                            ];
                            $menuSecondary = [
                                'key' => 'view_supplier',
                                'label' => __('notifications.view_supplier'),
                                'mode' => 'link',
                                'url' => $viewUrl,
                            ];
                        }
                    } elseif ($viewUrl) {
                        $primary = [
                            'key' => 'view_supplier',
                            'label' => __('notifications.view_supplier'),
                            'mode' => 'link',
                            'url' => $viewUrl,
                        ];
                    }
                }
                break;

            case 'order_offer':
                if ((int) $notification->related_order_id > 0) {
                    $orderUrl = self::orderUrl($notification, $isDesigner, $isSupplier, preferShow: true);
                    if ($orderUrl) {
                        $primary = [
                            'key' => 'open_order',
                            'label' => __('notifications.to_order'),
                            'mode' => 'link',
                            'url' => $orderUrl,
                        ];
                        $rowHref = $orderUrl;
                    }
                }
                break;

            case 'supplier_order':
                if ($isSupplier) {
                    $orderUrl = self::orderUrl($notification, false, true, preferShow: true);
                    if ($orderUrl && (int) $notification->related_order_id > 0) {
                        $primary = [
                            'key' => 'open_order',
                            'label' => __('notifications.to_order'),
                            'mode' => 'link',
                            'url' => $orderUrl,
                        ];
                        $rowHref = $orderUrl;
                    } elseif (Route::has('supplier.orders')) {
                        $primary = [
                            'key' => 'open_orders',
                            'label' => __('notifications.to_orders'),
                            'mode' => 'link',
                            'url' => route('supplier.orders'),
                        ];
                        $rowHref = route('supplier.orders');
                    }
                }
                break;

            case 'rate_supplier':
                if ($isDesigner && (int) $notification->related_order_id > 0 && $notification->action_key === 'rate_supplier') {
                    $primary = [
                        'key' => 'rate_supplier',
                        'label' => __('notifications.rate_supplier_action'),
                        'mode' => 'rate',
                        'order_id' => (int) $notification->related_order_id,
                        'title' => (string) $notification->title,
                    ];
                    $accent = true;
                }
                break;

            case 'rate_designer':
                if ($isSupplier && (int) $notification->related_order_id > 0 && $notification->action_key === 'rate_designer') {
                    $primary = [
                        'key' => 'rate_designer',
                        'label' => __('notifications.rate_designer_action'),
                        'mode' => 'rate',
                        'order_id' => (int) $notification->related_order_id,
                        'title' => (string) $notification->title,
                    ];
                    $accent = true;
                }
                break;

            case 'community_like':
            case 'community_comment':
            case 'community_reply':
                if ((int) $notification->related_post_id > 0 && Route::has('community.post')) {
                    $hash = $type === 'community_like' ? '' : '#comments';
                    $url = route('community.post', (int) $notification->related_post_id).$hash;
                    $primary = [
                        'key' => 'open_post',
                        'label' => __('notifications.to_post'),
                        'mode' => 'link',
                        'url' => $url,
                    ];
                    $rowHref = $url;
                }
                break;

            case 'supplier_moderation':
            case 'supplier_approved':
            case 'supplier_rejected':
            case 'supplier_status_changed':
                $viewUrl = (int) $notification->related_supplier_id > 0
                    ? self::supplierUrl((int) $notification->related_supplier_id)
                    : null;
                if ($viewUrl) {
                    $primary = [
                        'key' => 'view_supplier',
                        'label' => __('notifications.view_supplier'),
                        'mode' => 'link',
                        'url' => $viewUrl,
                    ];
                    $rowHref = $viewUrl;
                }
                break;

            case 'object_moderation':
            case 'object_approved':
            case 'object_rejected':
            case 'object_status_changed':
                // No related_object_id in schema yet — informational only.
                break;
        }

        $from = self::notificationsIndexUrl($isSupplier);

        return [
            'type' => $type,
            'icon' => self::iconFor($type),
            'primary' => self::withReturnUrl($primary, $from),
            'secondary' => self::withReturnUrl($secondary, $from),
            'menu_secondary' => self::withReturnUrl($menuSecondary, $from),
            'row_href' => $rowHref && $from ? BackNavigation::withFrom($rowHref, $from) : $rowHref,
            'accent_primary' => $accent,
        ];
    }

    private static function notificationsIndexUrl(bool $isSupplier): ?string
    {
        if ($isSupplier && Route::has('supplier.notifications.index')) {
            return route('supplier.notifications.index');
        }

        if (Route::has('notifications.index')) {
            return route('notifications.index');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $action
     * @return array<string, mixed>|null
     */
    private static function withReturnUrl(?array $action, ?string $from): ?array
    {
        if ($action === null || $from === null) {
            return $action;
        }

        if (($action['mode'] ?? '') === 'link' && ! empty($action['url']) && is_string($action['url'])) {
            $action['url'] = BackNavigation::withFrom($action['url'], $from);
        }

        return $action;
    }

    public static function typeOf(UserNotification $notification): string
    {
        $key = trim((string) ($notification->action_key ?? ''));
        if ($key !== '') {
            return $key;
        }

        // Legacy moderation rows without action_key.
        if ((int) ($notification->related_supplier_id ?? 0) > 0) {
            return 'supplier_moderation';
        }

        return 'info';
    }

    private static function supplierUrl(int $supplierId): ?string
    {
        if ($supplierId < 1 || ! Route::has('suppliers.show')) {
            return null;
        }

        return route('suppliers.show', ['supplierId' => $supplierId, 'readonly' => 1]);
    }

    private static function orderUrl(UserNotification $notification, bool $isDesigner, bool $isSupplier, bool $preferShow = true): ?string
    {
        $orderId = (int) ($notification->related_order_id ?? 0);

        if ($isDesigner && $orderId > 0 && Route::has('supplier-orders.show')) {
            $url = route('supplier-orders.show', $orderId);
            if (self::typeOf($notification) === 'order_offer') {
                // Keep hash after query: /show/1?section=offer&from=...#offer-negotiation
                $url .= (str_contains($url, '?') ? '&' : '?').'section=offer';
                $url .= '#offer-negotiation';
            }

            return $url;
        }

        if ($isDesigner && Route::has('supplier-orders.index')) {
            return route('supplier-orders.index');
        }

        if ($isSupplier && Route::has('supplier.orders')) {
            $url = route('supplier.orders');
            if ($orderId > 0) {
                $url .= (str_contains($url, '?') ? '&' : '?').'order='.$orderId;
            }

            return $url;
        }

        return null;
    }

    private static function iconFor(string $type): string
    {
        return match ($type) {
            'confirm_referral_supplier' => 'user-plus',
            'order_offer' => 'offer',
            'supplier_order' => 'truck',
            'rate_supplier', 'rate_designer' => 'star',
            'community_like' => 'heart',
            'community_comment', 'community_reply' => 'chat',
            'supplier_moderation', 'supplier_approved', 'supplier_rejected', 'supplier_status_changed' => 'building',
            'object_moderation', 'object_approved', 'object_rejected', 'object_status_changed' => 'home',
            default => 'bell',
        };
    }
}
