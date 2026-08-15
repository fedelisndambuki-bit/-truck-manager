<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpeIssuance extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'issue_date' => 'date',
        'replacement_due_date' => 'date',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
