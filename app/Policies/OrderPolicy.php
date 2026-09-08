<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * Serves two distinct authorization contexts for the same Order model:
 *
 * - The public storefront (App\Http\Controllers\OrderController), where
 *   `Gate::authorize('view', $order)` resolves $user from the default
 *   ('web') guard as an ?App\Models\User.
 * - The MoonShine admin panel, which resolves $user from its own
 *   ('moonshine') guard as a MoonshineUser and always supplies one for
 *   every ability (staff must be authenticated to reach /admin at all).
 *
 * Both classes extend the same Illuminate\Foundation\Auth\User base, so
 * `view()` accepts either via the shared Authenticatable contract and
 * branches on instanceof — that's the only method the storefront calls.
 */
class OrderPolicy
{
    /**
     * A signed-in customer may view only their own orders. A guest order
     * (no owner) is accessible to anyone with its unguessable UUID URL —
     * that link is the access control for guest checkouts. Staff can
     * always view any order.
     */
    public function view(?Authenticatable $user, Order $order): bool
    {
        if ($user instanceof MoonshineUser) {
            return true;
        }

        if ($order->isGuestOrder()) {
            return true;
        }

        return $user instanceof User && $order->user_id === $user->id;
    }

    public function viewAny(MoonshineUser $user): bool
    {
        return true;
    }

    /**
     * Orders are only ever created through checkout (OrderService), never
     * fabricated ad-hoc from the admin panel.
     */
    public function create(MoonshineUser $user): bool
    {
        return false;
    }

    /**
     * There is no generic "edit" form — status changes, shipment info,
     * and internal notes are all explicit actions on the detail page,
     * each going through its own service (see OrderResource). Disabling
     * update() here just hides the generic edit-pencil affordance.
     */
    public function update(MoonshineUser $user, Order $order): bool
    {
        return false;
    }

    /**
     * Order deletion is disabled entirely — orders are a permanent
     * record, cancel them instead via OrderFulfillmentService.
     */
    public function delete(MoonshineUser $user, Order $order): bool
    {
        return false;
    }

    public function massDelete(MoonshineUser $user): bool
    {
        return false;
    }

    public function restore(MoonshineUser $user, Order $order): bool
    {
        return false;
    }

    public function forceDelete(MoonshineUser $user, Order $order): bool
    {
        return false;
    }
}
