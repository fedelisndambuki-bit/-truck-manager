<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pit extends BaseModel
{
    protected $guarded = [];

    public const SAFETY_STATUSES = ['Safe', 'Caution', 'Restricted'];

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }
}
