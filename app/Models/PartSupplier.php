<?php

namespace App\Models;

use Database\Factories\PartSupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PartSupplier extends Pivot
{
    /** @use HasFactory<PartSupplierFactory> */
    use HasFactory;

    protected $table = 'part_supplier';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_cents' => 'integer',
            'stock_quantity' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }
}
