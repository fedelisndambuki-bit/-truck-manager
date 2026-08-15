<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUser extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'invited_at' => 'datetime',
        'can_download_documents' => 'boolean',
        'can_upload_documents' => 'boolean',
        'can_approve' => 'boolean',
        'can_comment' => 'boolean',
        'can_view_financials' => 'boolean',
        'can_view_drawings' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'Active') {
            return false;
        }

        return $this->end_date === null || $this->end_date->isFuture();
    }
}
