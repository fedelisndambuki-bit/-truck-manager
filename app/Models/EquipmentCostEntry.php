<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EquipmentCostEntry extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Purchase', 'Maintenance', 'Fuel', 'Tyre', 'Parts', 'Labor',
        'Insurance', 'Licensing', 'Depreciation', 'Other',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'incurred_on' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }
}
