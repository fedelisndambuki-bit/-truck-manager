<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionPlan extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'planned_date' => 'date',
        'assigned_crew' => 'array',
    ];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    public const STATUSES = ['Planned', 'In Progress', 'Completed', 'Cancelled'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (ProductionPlan $plan) {
            if (empty($plan->plan_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $plan->plan_number = 'PLN-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function crushingPlant(): BelongsTo
    {
        return $this->belongsTo(CrushingPlant::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'supervisor_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedCrewStaff()
    {
        return Staff::query()->whereIn('id', $this->assigned_crew ?? [])->get();
    }
}
