<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubcontractorPrequalification extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'insurance_verified' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }
}
