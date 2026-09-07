<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['price_tier_id', 'name', 'legal_name', 'tax_id', 'email', 'phone'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BusinessStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PriceTier, $this>
     */
    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<BusinessPartPrice, $this>
     */
    public function partPrices(): HasMany
    {
        return $this->hasMany(BusinessPartPrice::class);
    }

    public function isApproved(): bool
    {
        return $this->status === BusinessStatus::Approved;
    }

    public function approve(): bool
    {
        return $this->forceFill([
            'status' => BusinessStatus::Approved,
            'approved_at' => now(),
        ])->save();
    }
}
