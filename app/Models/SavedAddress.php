<?php

namespace App\Models;

use Database\Factories\SavedAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's reusable address, saved to their account so it doesn't
 * need retyping at every checkout — see App\Services\Account\SavedAddressService
 * for creation/update/default-swapping rules, and
 * App\Services\Checkout\Address for the separate, unpersisted per-order
 * DTO this gets copied from/into.
 */
#[Fillable(['user_id', 'label', 'name', 'line1', 'line2', 'city', 'state', 'postal_code', 'country', 'is_default'])]
class SavedAddress extends Model
{
    /** @use HasFactory<SavedAddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
