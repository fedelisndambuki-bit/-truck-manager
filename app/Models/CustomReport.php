<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomReport extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'fields' => 'array',
        'filters' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
