<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'review_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (RiskAssessment $ra) {
            if (empty($ra->assessment_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $ra->assessment_number = 'RA-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            self::applyScores($ra);
        });

        static::updating(function (RiskAssessment $ra) {
            self::applyScores($ra);
        });
    }

    protected static function applyScores(RiskAssessment $ra): void
    {
        $ra->risk_score = (int) $ra->likelihood * (int) $ra->severity;
        $ra->risk_level = self::levelForScore($ra->risk_score);

        if ($ra->residual_likelihood && $ra->residual_severity) {
            $ra->residual_risk_score = (int) $ra->residual_likelihood * (int) $ra->residual_severity;
            $ra->residual_risk_level = self::levelForScore($ra->residual_risk_score);
        }
    }

    public static function levelForScore(int $score): string
    {
        return match (true) {
            $score >= 15 => 'Critical',
            $score >= 8 => 'High',
            $score >= 4 => 'Medium',
            default => 'Low',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assessed_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }
}
