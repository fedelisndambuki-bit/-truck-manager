<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
