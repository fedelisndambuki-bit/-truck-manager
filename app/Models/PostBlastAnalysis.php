<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostBlastAnalysis extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'fly_rock' => 'boolean',
    ];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }
}
