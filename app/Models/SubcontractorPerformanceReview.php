<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubcontractorPerformanceReview extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'review_date' => 'date',
        'overall_rating' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (SubcontractorPerformanceReview $review) {
            $ratings = array_filter([
                $review->quality_of_work_rating,
                $review->timeliness_rating,
                $review->safety_rating,
                $review->communication_rating,
                $review->cost_control_rating,
                $review->issue_resolution_rating,
            ], fn ($r) => $r > 0);

            $review->overall_rating = count($ratings) ? round(array_sum($ratings) / count($ratings), 2) : 0;
        });
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by_id');
    }
}
