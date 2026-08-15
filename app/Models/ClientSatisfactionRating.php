<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSatisfactionRating extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'rated_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'rated_by_id');
    }
}
