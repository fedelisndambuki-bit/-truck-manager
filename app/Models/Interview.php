<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'interviewer_id');
    }
}
