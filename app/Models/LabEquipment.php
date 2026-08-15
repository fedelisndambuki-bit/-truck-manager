<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabEquipment extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'calibration_due_date' => 'date',
        'maintenance_due_date' => 'date',
    ];

    public const STATUSES = ['Operational', 'Due Calibration', 'Out of Service', 'Under Maintenance'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (LabEquipment $equipment) {
            if (empty($equipment->equipment_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $equipment->equipment_number = 'LEQ-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($equipment->status)) {
                $equipment->status = 'Operational';
            }
        });
    }

    public function responsibleTechnician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_technician_id');
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    public function latestCalibration(): ?Calibration
    {
        return $this->calibrations()->latest('calibration_date')->first();
    }

    /**
     * Real check against calibration_due_date only — no fabricated
     * telemetry. Used to block LabTest creation against overdue equipment.
     */
    public function isCalibrationOverdue(): bool
    {
        return (bool) $this->calibration_due_date && now()->gt($this->calibration_due_date);
    }
}
