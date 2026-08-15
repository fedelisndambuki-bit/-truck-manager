<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentInspection extends BaseModel
{
    protected $guarded = [];

    // Widened for EAM digital inspections — original type set (Daily,
    // Pre-start, Post-operation) still valid, plus the periodic cadence set.
    public const TYPES = ['Daily', 'Weekly', 'Monthly', 'Quarterly', 'Annual', 'Pre-start', 'Post-operation'];

    protected $casts = [
        'date' => 'date',
        'photos' => 'array',
        'checklist' => 'array',
        'videos' => 'array',
        'supervisor_approved' => 'boolean',
        'pass_fail' => 'boolean',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'inspector_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'operator_id');
    }
}
