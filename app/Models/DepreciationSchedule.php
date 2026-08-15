<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepreciationSchedule extends BaseModel
{
    protected $guarded = [];

    public const METHODS = ['StraightLine', 'ReducingBalance', 'UnitsOfProduction', 'Custom'];

    public const STATUSES = ['Active', 'Completed', 'Paused'];

    protected $casts = [
        'cost_basis' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'rate_percent' => 'decimal:2',
        'total_expected_units' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function accumulatedDepreciation(): float
    {
        return (float) $this->entries()->sum('amount');
    }

    public function currentBookValue(): float
    {
        $latest = $this->entries()->latest('period_end')->first();

        return $latest ? (float) $latest->book_value_after : (float) $this->cost_basis;
    }
}
