<?php

namespace App\Models;

use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A time-boxed hold on Part::stock_quantity for one order's line item,
 * created while that order is pending payment (see
 * App\Services\Inventory\StockReservationService) — closes the window
 * between checkout-session creation and payment confirmation where
 * App\Services\Inventory\StockChecker's live read alone could let two
 * shoppers both check out the last unit.
 *
 * Expired rows are simply ignored by the active() scope rather than
 * proactively cleaned up — they're inert, not incorrect.
 */
#[Fillable(['part_id', 'order_id', 'quantity', 'expires_at'])]
class StockReservation extends Model
{
    /** @use HasFactory<StockReservationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @param  Builder<StockReservation>  $query
     * @return Builder<StockReservation>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
