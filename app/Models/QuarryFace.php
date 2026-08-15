<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuarryFace extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Active', 'Inactive', 'Closed'];

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }
}
