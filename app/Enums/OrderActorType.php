<?php

namespace App\Enums;

/**
 * Who performed an order status change. actor_id on OrderStatusHistory
 * means moonshine_users.id for Staff, users.id for Customer, and is null
 * for System (e.g. a future automated payment webhook).
 */
enum OrderActorType: string
{
    case Staff = 'staff';
    case Customer = 'customer';
    case System = 'system';
}
