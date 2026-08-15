<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveAction extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'verified_at' => 'datetime',
        'closure_approved_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (CorrectiveAction $action) {
            if (empty($action->action_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $action->action_number = 'CA-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function safetyIncident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class);
    }

    public function hazard(): BelongsTo
    {
        return $this->belongsTo(Hazard::class);
    }

    public function nearMiss(): BelongsTo
    {
        return $this->belongsTo(NearMiss::class);
    }

    public function safetyInspection(): BelongsTo
    {
        return $this->belongsTo(SafetyInspection::class);
    }

    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_staff_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'verified_by_id');
    }

    public function closureApprovedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'closure_approved_by_id');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['Completed', 'Verified', 'Closed'], true);
    }

    public function source(): ?string
    {
        return match (true) {
            $this->safety_incident_id !== null => 'Incident ' . $this->safetyIncident?->incident_number,
            $this->hazard_id !== null => 'Hazard ' . $this->hazard?->hazard_number,
            $this->near_miss_id !== null => 'Near Miss ' . $this->nearMiss?->near_miss_number,
            $this->safety_inspection_id !== null => 'Inspection ' . $this->safetyInspection?->inspection_number,
            default => null,
        };
    }
}
