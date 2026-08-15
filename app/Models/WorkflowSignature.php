<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowSignature extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'signed_at' => 'datetime',
        'verified' => 'boolean',
    ];

    public function approvalAction(): BelongsTo
    {
        return $this->belongsTo(ApprovalAction::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }
}
