<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tyre extends BaseModel
{
    protected $fillable = [
        'company_id',
        'truck_id',
        'equipment_id',
        'serial_number',
        'brand',
        'model',
        'size',
        'tread_depth',
        'type', // New, Retread
        'position', // Axle position
        'status', // Active, Discarded, Spare, Under Repair, New
        'installation_date',
        'installation_mileage',
        'removal_date',
        'removal_mileage',
        'removal_reason',
        'notes',
        'purchase_date',
        'supplier',
        'purchase_cost',
        'warranty_details',
        'tyre_category', // steer, drive, trailer
        'pressure',
        'retread_count',
        'warehouse_location',
        'cost_per_hour',
        'expected_life_hours',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'removal_date' => 'date',
        'purchase_date' => 'date',
        'installation_mileage' => 'integer',
        'removal_mileage' => 'integer',
        'purchase_cost' => 'decimal:2',
        'pressure' => 'decimal:2',
        'retread_count' => 'integer',
        'cost_per_hour' => 'decimal:2',
        'expected_life_hours' => 'integer',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(TyreInspection::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(TyreRepair::class);
    }

    public function rotationLogs(): HasMany
    {
        return $this->hasMany(TyreRotationLog::class);
    }
}
