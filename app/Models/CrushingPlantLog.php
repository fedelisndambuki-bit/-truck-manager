<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrushingPlantLog extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public const SHIFTS = ['Day', 'Night'];

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }
}
