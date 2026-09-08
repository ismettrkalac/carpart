<?php

namespace App\Models;

use Database\Factories\OrderNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MoonShine\Laravel\Models\MoonshineUser;

/**
 * An internal, staff-only note on an order. Never rendered on any
 * customer-facing page.
 */
#[Fillable(['order_id', 'moonshine_user_id', 'body'])]
class OrderNote extends Model
{
    /** @use HasFactory<OrderNoteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<MoonshineUser, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(MoonshineUser::class, 'moonshine_user_id');
    }
}
