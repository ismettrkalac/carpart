<?php

namespace App\Models;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * A checkout attempt's result. Every order is created as pending_payment —
 * no payment provider is integrated yet (see OrderService for the seam
 * where one will connect).
 */
#[Fillable([
    'user_id',
    'email',
    'shipping_name', 'shipping_line1', 'shipping_line2', 'shipping_city', 'shipping_state', 'shipping_postal_code', 'shipping_country',
    'billing_name', 'billing_line1', 'billing_line2', 'billing_city', 'billing_state', 'billing_postal_code', 'billing_country',
    'subtotal_cents', 'shipping_cents', 'tax_cents', 'total_cents', 'currency',
    'idempotency_key',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'fulfillment_status' => FulfillmentStatus::class,
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * Use the UUID (not the sequential ID) for route binding, so order
     * URLs aren't guessable/enumerable.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isGuestOrder(): bool
    {
        return $this->user_id === null;
    }

    public function formattedTotal(): string
    {
        return Number::currency($this->total_cents / 100, in: $this->currency);
    }

    public function formattedSubtotal(): string
    {
        return Number::currency($this->subtotal_cents / 100, in: $this->currency);
    }

    public function formattedShipping(): string
    {
        return Number::currency($this->shipping_cents / 100, in: $this->currency);
    }

    public function formattedTax(): string
    {
        return Number::currency($this->tax_cents / 100, in: $this->currency);
    }
}
