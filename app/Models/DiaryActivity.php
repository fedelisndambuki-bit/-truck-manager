<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryActivity extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'finish_time' => 'datetime:H:i',
        'completion_pct' => 'decimal:2',
    ];

    public function diary(): BelongsTo
    {
        return $this->belongsTo(DailySiteDiary::class, 'daily_site_diary_id');
    }
}
