<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WeighbridgeDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Driver License', 'Vehicle Inspection', 'Loading Photo', 'Exit Photo',
        'Vehicle Photo — Front', 'Vehicle Photo — Rear', 'Vehicle Photo — Left', 'Vehicle Photo — Right',
        'Cargo Photo', 'Number Plate Photo', 'Delivery Proof', 'Exception Evidence', 'Other',
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
