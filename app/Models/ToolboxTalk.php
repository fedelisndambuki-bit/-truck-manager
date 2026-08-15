<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolboxTalk extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'photos' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'presenter_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(ToolboxTalkAttendee::class);
    }
}
