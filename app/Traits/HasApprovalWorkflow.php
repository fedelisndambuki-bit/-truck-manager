<?php

namespace App\Traits;

use App\Models\ApprovalRequest;
use App\Services\WorkflowEngine;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * The standard integration point any Tanaki Hub module uses to plug a model
 * into the platform-wide Workflow & Approvals engine instead of hard-coding
 * its own approve/reject logic. Mirrors HasDocumentWorkflow's shape (EDMS)
 * but is intentionally decoupled from it — this is Core infrastructure, not
 * a document concern.
 */
trait HasApprovalWorkflow
{
    protected static function bootHasApprovalWorkflow(): void
    {
        static::deleting(function ($model) {
            $model->approvalRequests()->delete();
        });
    }

    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable')->latest('created_at');
    }

    public function currentApproval(): ?ApprovalRequest
    {
        return $this->approvalRequests()->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled'])->first();
    }

    public function submitForApproval(string $workflowKey, array $context = []): ApprovalRequest
    {
        return app(WorkflowEngine::class)->submitForApproval($this, $workflowKey, $context);
    }

    public function isApprovalPending(): bool
    {
        return $this->currentApproval() !== null;
    }

    public function isApproved(): bool
    {
        return $this->approvalRequests()->where('status', 'Approved')->exists();
    }
}
