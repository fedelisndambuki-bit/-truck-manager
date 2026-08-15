<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAlert extends BaseModel
{
    protected $guarded = [];

    public const TYPES = [
        'maintenance_due', 'breakdown_reported', 'inspection_overdue', 'warranty_expiring',
        'low_spare_parts', 'fuel_theft_detected', 'equipment_offline', 'engine_fault_detected', 'calibration_due',
    ];

    public const SEVERITIES = ['Low', 'Medium', 'High', 'Critical'];

    protected $casts = [
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
}
