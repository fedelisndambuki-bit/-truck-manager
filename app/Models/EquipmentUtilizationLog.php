<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentUtilizationLog extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'log_date' => 'date',
        'engine_hours' => 'decimal:2',
        'operating_hours' => 'decimal:2',
        'idle_hours' => 'decimal:2',
        'travel_hours' => 'decimal:2',
        'standby_hours' => 'decimal:2',
        'tonnes_moved' => 'decimal:2',
        'material_loaded' => 'decimal:2',
        'fuel_used_litres' => 'decimal:2',
        'distance_km' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function getOutputPerHourAttribute(): float
    {
        $operating = (float) $this->operating_hours;

        return $operating > 0 ? round(((float) $this->tonnes_moved) / $operating, 2) : 0.0;
    }

    public function getAvailabilityPercentAttribute(): float
    {
        $operating = (float) $this->operating_hours;
        $total = $operating + (float) $this->idle_hours + (float) $this->standby_hours;

        return $total > 0 ? round(($operating / $total) * 100, 1) : 0.0;
    }

    public function getUtilizationPercentAttribute(): float
    {
        $operating = (float) $this->operating_hours;
        $total = $operating + (float) $this->idle_hours + (float) $this->travel_hours + (float) $this->standby_hours;

        return $total > 0 ? round(($operating / $total) * 100, 1) : 0.0;
    }
}
