<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAllocation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'allocated_from' => 'date',
        'allocated_to' => 'date',
        'actual_return_date' => 'date',
        'approved_at' => 'datetime',
        'expected_daily_hours' => 'decimal:2',
        'expected_fuel_usage' => 'decimal:2',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_employee_id');
    }

    public function backupOperator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'backup_operator_employee_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function crusher(): BelongsTo
    {
        return $this->belongsTo(Crusher::class);
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }
}
