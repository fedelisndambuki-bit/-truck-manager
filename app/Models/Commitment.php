<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commitment extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'committed_amount' => 'decimal:2',
        'invoiced_to_date' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (Commitment $commitment) {
            if (empty($commitment->commitment_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $commitment->commitment_number = 'CMT-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->committed_amount - (float) $this->invoiced_to_date, 2);
    }
}
