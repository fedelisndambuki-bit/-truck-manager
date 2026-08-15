<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentHealthReading extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'recorded_at' => 'datetime',
        'engine_temp' => 'decimal:2',
        'oil_pressure' => 'decimal:2',
        'hydraulic_pressure' => 'decimal:2',
        'coolant_temp' => 'decimal:2',
        'battery_voltage' => 'decimal:2',
        'engine_hours' => 'decimal:2',
        'fuel_level' => 'decimal:2',
        'def_level' => 'decimal:2',
        'hydraulic_oil_level' => 'decimal:2',
        'error_codes' => 'array',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
