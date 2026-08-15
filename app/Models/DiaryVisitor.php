<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryVisitor extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
    ];

    public function diary(): BelongsTo
    {
        return $this->belongsTo(DailySiteDiary::class, 'daily_site_diary_id');
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'host_staff_id');
    }
}
