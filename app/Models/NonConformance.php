<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonConformance extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    public const SEVERITIES = ['Minor', 'Major', 'Critical'];

    public const STATUSES = ['Open', 'In Progress', 'Closed', 'Verified'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (NonConformance $ncr) {
            if (empty($ncr->ncr_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $ncr->ncr_number = 'NCR-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($ncr->severity)) {
                $ncr->severity = 'Minor';
            }

            if (empty($ncr->status)) {
                $ncr->status = 'Open';
            }
        });
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_person_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(QualityCorrectiveAction::class);
    }

    public function close(): void
    {
        $this->update(['status' => 'Closed']);
    }
}
