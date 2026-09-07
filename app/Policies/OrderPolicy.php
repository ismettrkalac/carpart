<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * A signed-in user may view only their own orders. A guest order (no
     * owner) is accessible to anyone who has its unguessable UUID URL —
     * that link is the access control for guest checkouts.
     */
    public function view(?User $user, Order $order): bool
    {
        if ($order->isGuestOrder()) {
            return true;
        }

        return $user !== null && $order->user_id === $user->id;
    }
}
