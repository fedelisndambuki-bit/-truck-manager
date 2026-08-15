<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'rating' => 'decimal:1',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewer_id');
    }
}
