<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * A snapshot of one purchased part at order time. Fields here are frozen
 * on creation and must never be re-derived from the live Part afterward —
 * that's what keeps historical orders accurate if a part's name, SKU, or
 * price changes later.
 */
#[Fillable(['order_id', 'part_id', 'sku', 'name', 'unit_price_cents', 'quantity', 'line_total_cents'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'line_total_cents' => 'integer',
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
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function formattedLineTotal(?string $currency = null): string
    {
        return Number::currency($this->line_total_cents / 100, in: $currency ?? $this->order->currency ?? 'USD');
    }
}
