<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends BaseModel
{
    protected $guarded = [];

    // Enterprise Asset Management status set (widened from the original
    // Available/In Use/Under Maintenance set — existing rows keep their old
    // values until edited, both sets render fine as plain badge text).
    public const STATUSES = ['Operational', 'Idle', 'Under Maintenance', 'Out of Service', 'Disposed'];

    protected $casts = [
        'purchase_date' => 'date',
        'commission_date' => 'date',
        'insurance_expiry' => 'date',
        'warranty_expiry' => 'date',
        'next_calibration_date' => 'date',
        'fuel_level' => 'decimal:2',
        'engine_hours' => 'decimal:2',
        'idle_hours' => 'decimal:2',
        'current_value' => 'decimal:2',
        'purchase_cost' => 'decimal:2',
        'replacement_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'gps_lat' => 'decimal:6',
        'gps_lng' => 'decimal:6',
        'covered_components' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Equipment $equipment) {
            if (empty($equipment->equipment_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $equipment->equipment_code = 'EQ-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(EquipmentAllocation::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function tyres(): HasMany
    {
        return $this->hasMany(Tyre::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class, 'equipment_category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(EamStore::class, 'eam_store_id');
    }

    public function utilizationLogs(): HasMany
    {
        return $this->hasMany(EquipmentUtilizationLog::class);
    }

    public function healthReadings(): HasMany
    {
        return $this->hasMany(EquipmentHealthReading::class);
    }

    public function telematicsReadings(): HasMany
    {
        return $this->hasMany(TelematicsReading::class);
    }

    public function latestHealthReading(): ?EquipmentHealthReading
    {
        return $this->healthReadings()->latest('recorded_at')->first();
    }

    public function preventiveMaintenancePlans(): HasMany
    {
        return $this->hasMany(PreventiveMaintenancePlan::class);
    }

    public function breakdowns(): HasMany
    {
        return $this->hasMany(Breakdown::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(EquipmentCostEntry::class);
    }

    public function currentAttachments(): HasMany
    {
        return $this->hasMany(EquipmentAttachment::class, 'current_equipment_id');
    }

    public function attachmentUsageLogs(): HasMany
    {
        return $this->hasMany(EquipmentAttachmentUsageLog::class);
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(DepreciationSchedule::class);
    }

    public function warrantyClaims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function eamDocuments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(EquipmentDocument::class, 'documentable');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(EquipmentAlert::class);
    }

    public function currentAllocation(): ?EquipmentAllocation
    {
        return $this->allocations()
            ->where('approval_status', 'Approved')
            ->whereNull('actual_return_date')
            ->latest('allocated_from')
            ->first();
    }
}
