<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends BaseModel
{
    protected $fillable = [
        'company_id',
        'truck_id',
        'equipment_id',
        'technician_id',
        'maintenance_work_order_id',
        'maintenance_type',
        'description',
        'service_provider',
        'cost',
        'mileage_at_service',
        'next_service_mileage',
        'next_maintenance_date',
        'maintenance_date',
        'status',
        'invoice_upload',
        'downtime_hours',
        'recommendations',
        'warranty_covered',
        'photos',
        'notes',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'mileage_at_service' => 'integer',
        'next_service_mileage' => 'integer',
        'downtime_hours' => 'decimal:2',
        'warranty_covered' => 'boolean',
        'photos' => 'array',
    ];

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(MaintenanceWorkOrder::class, 'maintenance_work_order_id');
    }

    public function partUsages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PartUsage::class);
    }
}
