<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Variation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'cost_impact' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Variation $variation) {
            if (empty($variation->variation_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $variation->variation_number = 'VAR-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by_id');
    }

    public function approve(): void
    {
        $this->update(['status' => 'Approved', 'approved_at' => now()]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'Rejected', 'approved_at' => now()]);
    }
}
