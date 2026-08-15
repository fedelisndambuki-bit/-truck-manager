<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastExecution extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'actual_date' => 'date',
    ];

    public const OUTCOMES = ['Successful', 'Partial', 'Misfire', 'Delayed'];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function responsibleEngineer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_engineer_id');
    }

    public function shotFirer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'shot_firer_id');
    }

    public function safetyIncident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class);
    }
}
