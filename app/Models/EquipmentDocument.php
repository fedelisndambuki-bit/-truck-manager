<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EquipmentDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Purchase Document', 'Invoice', 'Warranty Certificate', 'Insurance', 'Inspection Report',
        'Service Report', 'Photo', 'Video', 'Manual', 'Parts Catalog', 'Other',
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
