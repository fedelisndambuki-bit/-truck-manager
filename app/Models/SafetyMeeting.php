<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SafetyMeeting extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'attachments' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(SafetyMeetingAttendee::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(SafetyMeetingActionItem::class);
    }
}
