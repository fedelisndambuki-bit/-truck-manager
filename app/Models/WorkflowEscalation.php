<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowEscalation extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'escalated_at' => 'datetime',
    ];

    public function approvalStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class);
    }

    public function escalatedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_from_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }
}
