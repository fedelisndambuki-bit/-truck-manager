<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqSupplier extends BaseModel
{
    protected $guarded = [];

    public const STATUSES = ['Invited', 'Responded', 'Declined'];

    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function requestForQuotation(): BelongsTo
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
