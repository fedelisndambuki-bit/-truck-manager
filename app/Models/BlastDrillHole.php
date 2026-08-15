<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastDrillHole extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'finish_time' => 'datetime',
    ];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function drillRig(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'drill_rig_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }
}
