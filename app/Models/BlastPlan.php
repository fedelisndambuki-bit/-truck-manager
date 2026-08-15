<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BlastPlan extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    protected $casts = [
        'date_planned' => 'date',
        'scheduled_date' => 'date',
        'blast_team' => 'array',
        'equipment_required' => 'array',
    ];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Executed', 'Closed', 'Cancelled'];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'Urgent'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (BlastPlan $plan) {
            if (empty($plan->blast_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $plan->blast_number = 'BLST-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function extractionSite(): BelongsTo
    {
        return $this->belongsTo(ExtractionSite::class);
    }

    public function pit(): BelongsTo
    {
        return $this->belongsTo(Pit::class);
    }

    public function quarryFace(): BelongsTo
    {
        return $this->belongsTo(QuarryFace::class);
    }

    public function blastEngineer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'blast_engineer_id');
    }

    public function safetyOfficer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'safety_officer_id');
    }

    public function drillHoles(): HasMany
    {
        return $this->hasMany(BlastDrillHole::class);
    }

    public function explosiveIssues(): HasMany
    {
        return $this->hasMany(ExplosiveIssue::class);
    }

    public function safetyZone(): HasOne
    {
        return $this->hasOne(BlastSafetyZone::class);
    }

    public function execution(): HasOne
    {
        return $this->hasOne(BlastExecution::class);
    }

    public function postBlastAnalysis(): HasOne
    {
        return $this->hasOne(PostBlastAnalysis::class);
    }

    public function fragmentationAnalysis(): HasOne
    {
        return $this->hasOne(FragmentationAnalysis::class);
    }

    public function environmentalReadings(): HasMany
    {
        return $this->hasMany(BlastEnvironmentalReading::class);
    }

    public function cost(): HasOne
    {
        return $this->hasOne(BlastCost::class);
    }

    public function compliance(): HasOne
    {
        return $this->hasOne(BlastCompliance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BlastDocument::class);
    }

    public function totalHoles(): int
    {
        return $this->drillHoles()->count();
    }
}
