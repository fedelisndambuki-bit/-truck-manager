<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends BaseFinancialModel
{
    protected $table = 'fin_payouts';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'retry_count' => 'integer',
    ];

    /** Resolved when recipient_type = 'driver' */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'recipient_id');
    }
}
