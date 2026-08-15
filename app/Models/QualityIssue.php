<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityIssue extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (QualityIssue $issue) {
            if (empty($issue->issue_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $prefix = match ($issue->type) {
                    'NCR' => 'NCR', 'CAPA' => 'CAPA', 'Punch List' => 'PL', default => 'DEF',
                };
                $issue->issue_number = $prefix . '-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
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

    public function qualityInspection(): BelongsTo
    {
        return $this->belongsTo(QualityInspection::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'raised_by_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_to_id');
    }

    public function close(): void
    {
        $this->update(['status' => 'Closed', 'closed_at' => now()]);
    }
}
