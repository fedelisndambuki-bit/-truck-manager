<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelStock extends BaseModel
{
    protected $guarded = [];

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function isLow(): bool
    {
        if (empty($this->capacity_litres)) {
            return false;
        }

        return (float) $this->current_level_litres <= (float) $this->capacity_litres * 0.2;
    }
}
