<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FragmentationAnalysis extends BaseModel
{
    protected $guarded = [];

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }
}
