<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'api_driver', 'api_config', 'is_active'])]
#[Hidden(['api_config'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_config' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Part, $this, PartSupplier>
     */
    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class)
            ->using(PartSupplier::class)
            ->withPivot(['supplier_sku', 'cost_cents', 'stock_quantity', 'last_synced_at'])
            ->withTimestamps();
    }
}
