<?php

namespace App\Models;

use Database\Factories\ManufacturerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo_path'])]
class Manufacturer extends Model
{
    /** @use HasFactory<ManufacturerFactory> */
    use HasFactory;

    /**
     * @return HasMany<Part, $this>
     */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
