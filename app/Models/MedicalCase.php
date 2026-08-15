<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCase extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'visit_date' => 'date',
        'return_to_work_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (MedicalCase $case) {
            if (empty($case->case_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $case->case_number = 'MED-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function safetyIncident(): BelongsTo
    {
        return $this->belongsTo(SafetyIncident::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
