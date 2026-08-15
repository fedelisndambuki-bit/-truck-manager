<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyClaim extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Filed', 'Approved', 'Rejected', 'Repaired', 'Closed'];

    protected $casts = [
        'filed_at' => 'date',
        'cost_recovered' => 'decimal:2',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (WarrantyClaim $claim) {
            if (empty($claim->claim_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $claim->claim_number = 'WAR-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
