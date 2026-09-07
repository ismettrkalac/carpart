<?php

namespace App\Models;

use App\Enums\PartStatus;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

#[Fillable([
    'manufacturer_id',
    'category_id',
    'sku',
    'name',
    'slug',
    'description',
    'status',
    'base_price_cents',
    'currency',
    'stock_quantity',
    'weight_kg',
])]
class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PartStatus::class,
            'base_price_cents' => 'integer',
            'stock_quantity' => 'integer',
            'weight_kg' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<PartFitment, $this>
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(PartFitment::class);
    }

    /**
     * @return BelongsToMany<Supplier, $this, PartSupplier>
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->using(PartSupplier::class)
            ->withPivot(['supplier_sku', 'cost_cents', 'stock_quantity', 'last_synced_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<BusinessPartPrice, $this>
     */
    public function businessPrices(): HasMany
    {
        return $this->hasMany(BusinessPartPrice::class);
    }

    /**
     * Resolve the price in cents for the given business: a negotiated
     * override takes precedence, otherwise the tier discount applies.
     */
    public function priceForBusinessCents(Business $business): int
    {
        $override = $this->businessPrices->firstWhere('business_id', $business->id);

        if ($override !== null) {
            return $override->price_cents;
        }

        $discountPercent = $business->priceTier?->discount_percent ?? 0;

        return (int) round($this->base_price_cents * (100 - $discountPercent) / 100);
    }

    /**
     * The list (retail) price, formatted for display to a visitor who
     * hasn't signed in with a business account yet.
     */
    public function formattedPrice(): string
    {
        return Number::currency($this->base_price_cents / 100, in: $this->currency);
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * @param  Builder<Part>  $query
     * @return Builder<Part>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PartStatus::Active);
    }

    /**
     * @param  Builder<Part>  $query
     * @return Builder<Part>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
