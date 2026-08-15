<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'sla_due_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function (ApprovalRequest $request) {
            if (empty($request->reference_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $request->reference_number = 'APR-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step_order');
    }

    public function currentStep(): ?ApprovalStep
    {
        return $this->steps()->where('status', 'Pending')->orderBy('step_order')->first();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WorkflowComment::class);
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(WorkflowAuditLog::class, 'auditable')->latest('created_at');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['Approved', 'Rejected', 'Cancelled'], true);
    }
}
