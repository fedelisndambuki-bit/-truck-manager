<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplosiveIssue extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public const APPROVAL_STATUSES = ['Pending', 'Approved', 'Rejected'];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ExplosiveIssue $issue) {
            $issue->remaining_quantity = max(0, (float) $issue->quantity_issued - (float) $issue->quantity_used);
        });
    }

    public function blastPlan(): BelongsTo
    {
        return $this->belongsTo(BlastPlan::class);
    }
}
