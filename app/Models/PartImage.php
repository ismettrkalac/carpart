<?php

namespace App\Models;

use Database\Factories\PartImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photo in a Part's gallery. Ordered by position — the lowest
 * position is the part's primary/cover image (see Part::primaryImage()).
 */
#[Fillable(['part_id', 'path', 'position'])]
class PartImage extends Model
{
    /** @use HasFactory<PartImageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Part, $this>
     */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
