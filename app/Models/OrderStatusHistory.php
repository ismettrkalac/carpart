<?php

namespace App\Models;

use App\Enums\OrderActorType;
use App\Enums\OrderStatusType;
use Database\Factories\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * An immutable, append-only log entry: never updated after creation, only
 * ever created by App\Services\Orders\OrderFulfillmentService.
 */
#[Fillable(['order_id', 'status_type', 'from_status', 'to_status', 'actor_type', 'actor_id', 'note'])]
class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_type' => OrderStatusType::class,
            'actor_type' => OrderActorType::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * A human-readable label for who made this change. actor_id refers to
     * a different table depending on actor_type (moonshine_users.id for
     * Staff, users.id for Customer) — resolve it here rather than via a
     * belongsTo relation, since a single relation can't safely span two
     * unrelated tables.
     */
    public function actorLabel(): string
    {
        return match ($this->actor_type) {
            OrderActorType::System => 'System',
            OrderActorType::Staff => MoonshineUser::find($this->actor_id)?->name ?? 'Staff',
            OrderActorType::Customer => User::find($this->actor_id)?->name ?? 'Customer',
        };
    }
}
