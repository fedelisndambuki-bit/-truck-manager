<?php

namespace App\Models;

use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyIncident extends BaseModel
{
    use ProjectAccessScope;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SafetyIncident $incident) {
            if (empty($incident->incident_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $incident->incident_number = 'INC-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'closed_by_id');
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SafetyIncidentAttachment::class);
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public function medicalCases(): HasMany
    {
        return $this->hasMany(MedicalCase::class);
    }

    public const CATEGORIES = [
        'Fall From Height', 'Slip / Trip', 'Equipment Accident', 'Vehicle Accident', 'Electrical',
        'Fire', 'Explosion', 'Chemical Exposure', 'Manual Handling', 'Struck By Object',
        'Caught Between Objects', 'Confined Space', 'Excavation', 'Scaffolding',
        'Lifting Operations', 'Environmental Incident', 'Security Incident', 'Property Damage',
    ];
}
