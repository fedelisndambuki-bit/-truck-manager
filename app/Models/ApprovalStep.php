<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStep extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'Pending' && $this->due_at !== null && $this->due_at->isPast();
    }

    public function nodeType(): ?string
    {
        return $this->approvalRequest?->workflowDefinition?->findNode($this->node_key)['type'] ?? null;
    }
}
