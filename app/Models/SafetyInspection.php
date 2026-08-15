<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyInspection extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'photos' => 'array',
        'score' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SafetyInspection $inspection) {
            if (empty($inspection->inspection_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $inspection->inspection_number = 'INSP-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspector_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SafetyInspectionItem::class);
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public const TYPES = [
        'Daily Site Inspection', 'Weekly Inspection', 'Scaffold Inspection', 'Excavation Inspection',
        'Crane Inspection', 'Electrical Inspection', 'Fire Inspection', 'PPE Inspection',
        'Vehicle Inspection', 'Equipment Inspection',
    ];
}
