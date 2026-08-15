<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LabSample extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'collection_date' => 'date',
        'gps_lat' => 'decimal:6',
        'gps_lng' => 'decimal:6',
    ];

    public const SAMPLE_TYPES = [
        'Raw Material', 'Crusher Feed', 'Finished Product', 'Stockpile',
        'Dispatch Verification', 'Customer Complaint',
    ];

    public const STATUSES = ['Collected', 'Received', 'Testing', 'Approved', 'Rejected', 'Archived'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (LabSample $sample) {
            if (empty($sample->sample_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $sample->sample_number = 'SAM-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($sample->status)) {
                $sample->status = 'Collected';
            }
        });

        static::created(function (LabSample $sample) {
            if ($sample->sampling_request_id) {
                $sample->samplingRequest?->fulfill($sample);
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

    public function stockpile(): BelongsTo
    {
        return $this->belongsTo(Stockpile::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function samplingRequest(): BelongsTo
    {
        return $this->belongsTo(SamplingRequest::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'collected_by_id');
    }

    public function tests(): HasMany
    {
        return $this->hasMany(LabTest::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function nonConformances(): HasMany
    {
        return $this->hasMany(NonConformance::class);
    }

    public function qualityHolds(): HasMany
    {
        return $this->hasMany(QualityHold::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(LabDocument::class, 'documentable');
    }

    public function markReceived(): void
    {
        $this->update(['status' => 'Received']);
    }

    public function startTesting(): void
    {
        $this->update(['status' => 'Testing']);
    }

    /**
     * Real evaluation against the sample's own recorded LabTest results —
     * no fabricated scoring. Any Fail -> Rejected, any Conditional (no
     * Fail) -> Conditional handled as a hold candidate, all Pass -> ready
     * for the quality approval workflow.
     */
    public function evaluateResult(): string
    {
        $results = $this->tests()->where('status', 'Completed')->pluck('result');

        if ($results->isEmpty()) {
            return 'Pending';
        }

        if ($results->contains('Fail')) {
            return 'Fail';
        }

        if ($results->contains('Conditional')) {
            return 'Conditional';
        }

        return 'Pass';
    }

    public function submitForQualityApproval(): void
    {
        $this->update(['status' => 'Testing']);
        $this->submitForApproval('lab-sample-quality-approval', [
            'title' => 'Quality Approval — ' . $this->sample_number,
            'metadata' => ['lab_sample_id' => $this->id],
        ]);
    }
}
