<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends BaseModel
{
    protected $fillable = [
        'company_id',
        'truck_id',
        'equipment_id',
        'employee_id',
        'date',
        'quantity_litres',
        'cost_per_litre',
        'total_cost',
        'mileage_at_fueling',
        'fuel_station',
        'receipt_upload',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity_litres' => 'decimal:2',
        'cost_per_litre' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'mileage_at_fueling' => 'integer',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
