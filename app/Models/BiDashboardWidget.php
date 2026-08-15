<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiDashboardWidget extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
