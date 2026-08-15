<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastEnvironmentalReading extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public const COMPLIANCE_STATUSES = ['Compliant', 'Exceeded', 'Under Review'];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }
}
