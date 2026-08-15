<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'book_value_after' => 'decimal:2',
        'ledger_posted' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(DepreciationSchedule::class, 'depreciation_schedule_id');
    }
}
