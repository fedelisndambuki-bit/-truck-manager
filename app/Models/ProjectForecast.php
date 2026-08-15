<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectForecast extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'forecast_date' => 'date',
        'estimated_completion_date' => 'date',
        'forecast_final_cost' => 'decimal:2',
        'forecast_revenue' => 'decimal:2',
        'forecast_profit' => 'decimal:2',
        'cost_performance_index' => 'decimal:3',
        'schedule_performance_index' => 'decimal:3',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function forecastedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'forecasted_by_id');
    }
}
