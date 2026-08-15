<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockpileDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Survey Report', 'Compliance Certificate', 'Quality Certificate', 'Photo', 'Adjustment Evidence', 'Other',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
