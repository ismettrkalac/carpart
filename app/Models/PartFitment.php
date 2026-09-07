<?php

namespace App\Models;

use Database\Factories\PartFitmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['part_id', 'make', 'model', 'year_start', 'year_end', 'engine', 'trim'])]
class PartFitment extends Model
{
    /** @use HasFactory<PartFitmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_start' => 'integer',
            'year_end' => 'integer',
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
     * @param  Builder<PartFitment>  $query
     * @return Builder<PartFitment>
     */
    public function scopeForVehicle(Builder $query, string $make, string $model, int $year): Builder
    {
        return $query->where('make', $make)
            ->where('model', $model)
            ->where('year_start', '<=', $year)
            ->where('year_end', '>=', $year);
    }
}
