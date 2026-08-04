<?php

namespace App\Policies;

use App\Models\Supplier_orders;
use App\Models\User;
use App\Support\WorkspaceAccess;

class SupplierOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier_orders $order): bool
    {
        return (int) $order->user_id === (int) $user->id
            || WorkspaceAccess::canAccessProject($user, $order->project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Supplier_orders $order): bool
    {
        return (int) $order->user_id === (int) $user->id
            || WorkspaceAccess::canAccessProject($user, $order->project);
    }

    public function delete(User $user, Supplier_orders $order): bool
    {
        return (int) $order->user_id === (int) $user->id
            || WorkspaceAccess::canAccessProject($user, $order->project);
    }
}
