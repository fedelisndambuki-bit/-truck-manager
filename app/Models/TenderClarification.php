<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderClarification extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function tenderInvitation(): BelongsTo
    {
        return $this->belongsTo(TenderInvitation::class);
    }

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(Subcontractor::class);
    }

    public function askedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'asked_by_id');
    }
}
