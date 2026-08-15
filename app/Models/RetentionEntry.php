<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionEntry extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'percentage' => 'decimal:2',
        'held_amount' => 'decimal:2',
        'released_amount' => 'decimal:2',
        'dlp_end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function progressClaim(): BelongsTo
    {
        return $this->belongsTo(ProgressClaim::class, 'source_id');
    }

    public function paymentCertificate(): BelongsTo
    {
        return $this->belongsTo(PaymentCertificate::class, 'source_id');
    }

    public function getOutstandingAttribute(): float
    {
        return round((float) $this->held_amount - (float) $this->released_amount, 2);
    }

    public function release(float $amount): void
    {
        $released = min($this->outstanding, $amount);
        $this->released_amount = (float) $this->released_amount + $released;
        $this->release_status = $this->released_amount >= $this->held_amount ? 'Released' : 'Partially Released';
        $this->save();
    }
}
