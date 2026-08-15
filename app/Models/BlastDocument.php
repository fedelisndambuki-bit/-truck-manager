<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlastDocument extends BaseModel
{
    protected $guarded = [];

    public const CATEGORIES = [
        'Blast Design', 'Blast Maps', 'Permits', 'Risk Assessments', 'Method Statements',
        'Safety Plans', 'Photos', 'Videos', 'Drone Images', 'Survey Reports',
    ];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
