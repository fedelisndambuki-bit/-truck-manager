<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCorrectiveAction extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'progress_percent' => 'integer',
        'closure_approved_at' => 'datetime',
    ];

    public const STATUSES = ['Open', 'In Progress', 'Completed', 'Verified', 'Closed'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (QualityCorrectiveAction $action) {
            if (empty($action->action_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $action->action_number = 'QCA-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($action->status)) {
                $action->status = 'Open';
            }
        });
    }

    public function nonConformance(): BelongsTo
    {
        return $this->belongsTo(NonConformance::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_person_id');
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

    public function approveClosure(int $approvedById): void
    {
        $this->update([
            'status' => 'Closed',
            'closure_approved_by_id' => $approvedById,
            'closure_approved_at' => now(),
        ]);
    }
}
