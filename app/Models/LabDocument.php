<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LabDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Lab Report', 'Test Result', 'Certificate', 'Calibration Certificate',
        'SOP', 'Method Statement', 'Photo', 'Other',
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
