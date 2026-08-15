<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantQualityTest extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'pass_fail' => 'boolean',
        'approved_for_dispatch' => 'boolean',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (PlantQualityTest $test) {
            if (empty($test->sample_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $test->sample_number = 'SMP-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function productionRun(): BelongsTo
    {
        return $this->belongsTo(ProductionRun::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function testedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'tested_by_id');
    }
}
