<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderNote;

/**
 * Internal, staff-only notes. Never rendered on any customer-facing page —
 * see App\Models\Order::notes() and the account/order Blade views, which
 * deliberately never touch this relation.
 */
class OrderNoteService
{
    public function add(Order $order, string $body, ?int $moonshineUserId): OrderNote
    {
        return $order->notes()->create([
            'body' => $body,
            'moonshine_user_id' => $moonshineUserId,
        ]);
    }
}
