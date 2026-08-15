<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueRecognition extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'period_month' => 'date',
        'percent_complete' => 'decimal:2',
        'contract_revenue' => 'decimal:2',
        'earned_revenue' => 'decimal:2',
        'recognized_revenue' => 'decimal:2',
        'deferred_revenue' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
