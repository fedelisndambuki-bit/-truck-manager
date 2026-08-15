<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityHold extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'held_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public const REASONS = [
        'Failed Test', 'Pending Test', 'Customer Complaint', 'Contamination',
        'Wrong Product', 'Moisture Above Limit', 'Equipment Issue', 'Safety Concern',
    ];

    public const RELEASE_REASONS = ['Retesting', 'Corrective Action', 'Management Approval', 'Quality Approval'];

    public const STATUSES = ['Active', 'Released'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (QualityHold $hold) {
            if (empty($hold->hold_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $hold->hold_number = 'HLD-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($hold->status)) {
                $hold->status = 'Active';
            }

            if (empty($hold->held_at)) {
                $hold->held_at = now();
            }
        });
    }

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'held_by_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'released_by_id');
    }
}
