<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EamStore extends BaseModel
{
    protected $guarded = [];

    public const TYPES = ['Store', 'Depot'];

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
