<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource;
use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\WorkflowComment;
use App\Services\WorkflowEngine;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ViewApprovalRequest extends Page
{
    protected static string $resource = ApprovalRequestResource::class;

    protected static string $view = 'filament.clusters.workflow-approvals.resources.approval-request-resource.pages.view-approval-request';

    public ApprovalRequest $record;

    public string $actionComment = '';

    public string $commentBody = '';

    public string $signatureName = '';

    public function mount(ApprovalRequest $record): void
    {
        $this->record = $record;
        $this->refreshRecord();
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->record->fresh([
            'workflowDefinition', 'submittedBy', 'steps.actions.approver', 'comments.user',
        ]);
    }

    public function getTimeline()
    {
        return app(WorkflowEngine::class)->getTimeline($this->record);
    }

    public function myPendingAction(): ?ApprovalAction
    {
        return ApprovalAction::query()
            ->whereHas('approvalStep', fn ($q) => $q->where('approval_request_id', $this->record->id)->where('status', 'Pending'))
            ->where('approver_id', Auth::id())
            ->whereNull('action')
            ->first();
    }

    /**
     * The approvable behind a workflow instance can be any model
     * (LeaveRequest, PurchaseRequest, a Document, ...) — only some of them
     * carry a `project` relation. Project-restricted users can only approve
     * project-linked approvables they're actually assigned to; unrestricted
     * (internal) users are unaffected regardless of whether a project exists.
     */
    public function canApprove(): bool
    {
        $approvable = $this->record->approvable;
        $project = ($approvable && method_exists($approvable, 'project')) ? $approvable->project : null;

        return Auth::user()?->canOnProject($project, 'can_approve') ?? false;
    }

    public function approve(): void
    {
        $action = $this->myPendingAction();

        if (! $action || ! $this->canApprove()) {
            return;
        }

        app(WorkflowEngine::class)->approve($action, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->refreshRecord();
        Notification::make()->title('Approved')->success()->send();
    }

    public function requiresSignature(): bool
    {
        $action = $this->myPendingAction();

        return $action !== null && $action->approvalStep?->nodeType() === 'signature';
    }

    public function signAndApprove(): void
    {
        $action = $this->myPendingAction();

        if (! $action || trim($this->signatureName) === '' || ! $this->canApprove()) {
            return;
        }

        app(WorkflowEngine::class)->signAction($action, $this->signatureName);
        app(WorkflowEngine::class)->approve($action, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->signatureName = '';
        $this->refreshRecord();
        Notification::make()->title('Signed and approved')->success()->send();
    }

    public function reject(): void
    {
        $action = $this->myPendingAction();

        if (! $action) {
            return;
        }

        app(WorkflowEngine::class)->reject($action, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->refreshRecord();
        Notification::make()->title('Rejected')->danger()->send();
    }

    public function returnForRevision(): void
    {
        $action = $this->myPendingAction();

        if (! $action) {
            return;
        }

        app(WorkflowEngine::class)->returnForRevision($action, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->refreshRecord();
        Notification::make()->title('Returned for revision')->warning()->send();
    }

    public function requestInformation(): void
    {
        $action = $this->myPendingAction();

        if (! $action) {
            return;
        }

        app(WorkflowEngine::class)->requestInformation($action, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->refreshRecord();
        Notification::make()->title('Information requested')->send();
    }

    public function cancelRequest(): void
    {
        app(WorkflowEngine::class)->cancel($this->record, $this->actionComment ?: null);
        $this->actionComment = '';
        $this->refreshRecord();
        Notification::make()->title('Request cancelled')->send();
    }

    public function addComment(): void
    {
        if (trim($this->commentBody) === '') {
            return;
        }

        WorkflowComment::create([
            'company_id' => $this->record->company_id,
            'approval_request_id' => $this->record->id,
            'user_id' => Auth::id(),
            'body' => $this->commentBody,
        ]);

        $this->commentBody = '';
        $this->refreshRecord();
    }
}
