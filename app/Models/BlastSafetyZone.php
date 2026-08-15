<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastSafetyZone extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'warning_sirens' => 'boolean',
        'clearance_confirmed' => 'boolean',
    ];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function clearanceConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'clearance_confirmed_by_id');
    }
}
