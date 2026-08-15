<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiaryDelay extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'duration_hours' => 'decimal:2',
    ];

    public function diary(): BelongsTo
    {
        return $this->belongsTo(DailySiteDiary::class, 'daily_site_diary_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'diary_delay_id');
    }
}
