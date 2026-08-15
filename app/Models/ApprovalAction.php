<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApprovalAction extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'assigned_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function approvalStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function signature(): HasOne
    {
        return $this->hasOne(WorkflowSignature::class);
    }

    public function isPending(): bool
    {
        return $this->action === null;
    }
}
