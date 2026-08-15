<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPerformanceReview extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'on_time_delivery_score' => 'decimal:1',
        'quality_score' => 'decimal:1',
        'pricing_score' => 'decimal:1',
        'responsiveness_score' => 'decimal:1',
        'compliance_score' => 'decimal:1',
        'overall_score' => 'decimal:1',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (SupplierPerformanceReview $review) {
            $scores = array_filter([
                $review->on_time_delivery_score,
                $review->quality_score,
                $review->pricing_score,
                $review->responsiveness_score,
                $review->compliance_score,
            ], fn ($score) => $score !== null);

            if (! empty($scores)) {
                $review->overall_score = round(array_sum($scores) / count($scores), 1);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
