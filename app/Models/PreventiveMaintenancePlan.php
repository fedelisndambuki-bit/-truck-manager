<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreventiveMaintenancePlan extends BaseModel
{
    protected $guarded = [];

    public const TRIGGER_TYPES = ['Calendar', 'EngineHours', 'Km', 'FuelUsage', 'ProductionCycles'];

    public const STATUSES = ['Active', 'Paused'];

    protected $casts = [
        'interval_value' => 'decimal:2',
        'last_triggered_at_value' => 'decimal:2',
        'next_due_value' => 'decimal:2',
        'checklist' => 'array',
        'required_parts' => 'array',
        'required_tools' => 'array',
        'required_skills' => 'array',
        'estimated_duration_hours' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'preventive_maintenance_plan_id');
    }

    public function hasOpenWorkOrder(): bool
    {
        return $this->workOrders()->whereNotIn('status', ['Closed'])->exists();
    }
}
