<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrushingPlant extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'commission_date' => 'date',
    ];

    public const STATUSES = ['Running', 'Idle', 'Maintenance', 'Breakdown', 'Retired'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (CrushingPlant $plant) {
            if (empty($plant->plant_code)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $plant->plant_code = 'PLT-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CrushingPlantLog::class);
    }

    public function productionLines(): HasMany
    {
        return $this->hasMany(ProductionLine::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(PlantOperation::class);
    }

    public function productionRuns(): HasMany
    {
        return $this->hasMany(ProductionRun::class);
    }

    public function downtimes(): HasMany
    {
        return $this->hasMany(ProductionDowntime::class);
    }

    public function energyReadings(): HasMany
    {
        return $this->hasMany(EnergyReading::class);
    }

    public function qualityTests(): HasMany
    {
        return $this->hasMany(PlantQualityTest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PlantDocument::class);
    }
}
