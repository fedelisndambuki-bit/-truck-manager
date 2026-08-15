<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use App\Models\WorkflowAuditLog;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowEscalation;
use App\Models\WorkflowSetting;
use App\Models\WorkflowSignature;
use App\Notifications\WorkflowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * The platform-wide Workflow & Approvals engine. Every module in Tanaki Hub
 * calls this same API (via HasApprovalWorkflow on its models) instead of
 * hard-coding its own approve/reject logic. A workflow's shape is entirely
 * data-driven — the `definition` JSON graph on WorkflowDefinition, authored
 * via the visual Designer — this class never encodes a specific business
 * process, only how to walk/execute one.
 */
class WorkflowEngine
{
    public function __construct(protected WorkflowConditionEvaluator $evaluator)
    {
    }

    public function submitForApproval(Model $record, string $workflowKey, array $context = []): ApprovalRequest
    {
        $companyId = $context['company_id'] ?? $record->company_id ?? Auth::user()?->company_id;

        $definition = $this->resolveDefinition($workflowKey, $companyId);

        if (! $definition) {
            throw new \RuntimeException("No published workflow definition found for key [{$workflowKey}].");
        }

        $request = ApprovalRequest::create([
            'company_id' => $companyId,
            'workflow_definition_id' => $definition->id,
            'workflow_version' => $definition->version,
            'approvable_type' => get_class($record),
            'approvable_id' => $record->id,
            'title' => $context['title'] ?? (class_basename($record) . ' #' . $record->id),
            'status' => 'Pending',
            'submitted_by_id' => Auth::id(),
            'submitted_at' => now(),
            'metadata' => $context['metadata'] ?? [],
        ]);

        $this->logAudit($request, 'submitted', ['workflow' => $definition->name]);

        $start = $definition->startNode();

        if ($start) {
            $this->advanceFrom($request, $start['id']);
        } else {
            $this->complete($request, 'Approved');
        }

        return $request->fresh();
    }

    public function approve(ApprovalAction $action, ?string $comments = null): void
    {
        $this->recordAction($action, 'Approve', $comments);
    }

    public function reject(ApprovalAction $action, ?string $comments = null): void
    {
        $this->recordAction($action, 'Reject', $comments);
    }

    public function returnForRevision(ApprovalAction $action, ?string $comments = null): void
    {
        $this->recordAction($action, 'Return', $comments);
    }

    public function requestInformation(ApprovalAction $action, ?string $comments = null): void
    {
        $this->recordAction($action, 'RequestInfo', $comments);
    }

    public function reassign(ApprovalAction $action, User $newApprover, ?string $comments = null): void
    {
        $action->update([
            'approver_id' => $newApprover->id,
            'resolved_via' => 'reassigned',
            'assigned_at' => now(),
        ]);

        $this->logAudit($action->approvalStep->approvalRequest, 'reassigned', [
            'to' => $newApprover->name,
            'comments' => $comments,
        ]);
    }

    public function delegate(ApprovalAction $action, User $delegate, ?string $comments = null): void
    {
        $action->update([
            'approver_id' => $delegate->id,
            'resolved_via' => 'delegated',
            'assigned_at' => now(),
        ]);

        $this->logAudit($action->approvalStep->approvalRequest, 'delegated', [
            'to' => $delegate->name,
            'comments' => $comments,
        ]);
    }

    public function escalate(ApprovalStep $step, User $to, string $reason = 'manual', ?int $ruleHours = null): void
    {
        $request = $step->approvalRequest;
        $from = $step->actions()->whereNull('action')->first()?->approver;

        WorkflowEscalation::create([
            'company_id' => $request->company_id,
            'approval_step_id' => $step->id,
            'escalated_from_id' => $from?->id,
            'escalated_to_id' => $to->id,
            'escalated_at' => now(),
            'reason' => $reason,
            'rule_hours' => $ruleHours,
        ]);

        ApprovalAction::create([
            'company_id' => $request->company_id,
            'approval_step_id' => $step->id,
            'approver_type' => 'user',
            'approver_id' => $to->id,
            'resolved_via' => 'escalation',
            'assigned_at' => now(),
        ]);

        $step->update(['status' => 'Escalated']);
        $request->update(['status' => 'Escalated']);

        $this->logAudit($request, 'escalated', ['to' => $to->name, 'reason' => $reason]);
        $this->notifyRecipients(collect([$to]), $request, 'approval_escalated');
    }

    public function cancel(ApprovalRequest $request, ?string $comments = null): void
    {
        $request->update(['status' => 'Cancelled', 'completed_at' => now()]);
        $this->logAudit($request, 'cancelled', ['comments' => $comments]);
    }

    public function suspend(ApprovalRequest $request, ?string $comments = null): void
    {
        $request->update(['status' => 'Suspended']);
        $this->logAudit($request, 'suspended', ['comments' => $comments]);
    }

    public function resume(ApprovalRequest $request): void
    {
        $request->update(['status' => 'Pending']);
        $this->logAudit($request, 'resumed');
    }

    /**
     * Captures a digital signature (typed name, or a drawn signature_pad
     * data URL) against a pending approval action — mirrors DigitalSignature's
     * shape from EDMS but scoped to this engine's own table.
     */
    public function signAction(ApprovalAction $action, string $signerName, ?string $signatureData = null, string $type = 'typed'): WorkflowSignature
    {
        $ip = request()?->ip() ?? '127.0.0.1';
        $signedAt = now();
        $hash = hash('sha256', $action->id . '|' . $signerName . '|' . $signedAt->toIso8601String() . '|' . $ip);

        return WorkflowSignature::create([
            'company_id' => $action->company_id,
            'approval_action_id' => $action->id,
            'signer_user_id' => Auth::id(),
            'signed_at' => $signedAt,
            'signature_hash' => $hash,
            'signature_type' => $type,
            'signature_data' => $signatureData ?? $signerName,
            'ip_address' => $ip,
            'user_agent' => request()?->userAgent(),
            'verified' => true,
        ]);
    }

    public function getStatus(ApprovalRequest $request): string
    {
        return $request->status;
    }

    public function getTimeline(ApprovalRequest $request): Collection
    {
        return $request->auditLogs()->with('user')->get();
    }

    // ── Internal graph execution ─────────────────────────────────────────

    protected function resolveDefinition(string $workflowKey, ?int $companyId): ?WorkflowDefinition
    {
        return WorkflowDefinition::withoutGlobalScope('company')
            ->where('status', 'Published')
            ->where('internal_key', $workflowKey)
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->orderByRaw('company_id IS NULL') // tenant-specific override takes priority over a platform template
            ->first();
    }

    protected function advanceFrom(ApprovalRequest $request, string $fromNodeKey): void
    {
        $definition = $request->workflowDefinition;
        $edges = $definition->edgesFrom($fromNodeKey);

        if (empty($edges)) {
            $this->complete($request, 'Approved');

            return;
        }

        // A control node (decision/condition) picks exactly one matching edge;
        // every other node type (including "parallel") fans out to every edge.
        $fromNode = $definition->findNode($fromNodeKey);
        $isDecision = in_array($fromNode['type'] ?? null, ['decision', 'condition'], true);

        if ($isDecision) {
            $edge = $this->pickMatchingEdge($edges, $request);
            if ($edge) {
                $this->enterNode($request, $definition->findNode($edge['to']));
            } else {
                $this->complete($request, 'Approved');
            }

            return;
        }

        foreach ($edges as $edge) {
            $target = $definition->findNode($edge['to']);
            if ($target) {
                $this->enterNode($request, $target);
            }
        }
    }

    protected function pickMatchingEdge(array $edges, ApprovalRequest $request): ?array
    {
        $default = null;

        foreach ($edges as $edge) {
            $condition = $edge['condition'] ?? null;

            if (empty($condition)) {
                $default ??= $edge;

                continue;
            }

            if ($this->evaluator->evaluate($condition, $request->metadata ?? [])) {
                return $edge;
            }
        }

        return $default;
    }

    protected function enterNode(ApprovalRequest $request, ?array $node): void
    {
        if (! $node) {
            return;
        }

        $type = $node['type'] ?? null;
        $config = $node['config'] ?? [];

        match ($type) {
            'end' => $this->complete($request, $request->metadata['__outcome'] ?? 'Approved'),
            'decision', 'condition' => $this->advanceFrom($request, $node['id']),
            'parallel', 'sequential' => $this->advanceFrom($request, $node['id']),
            'notification' => $this->fireInlineNotification($request, $config) && $this->advanceFrom($request, $node['id']),
            'timer' => $this->createTimerStep($request, $node),
            default => $this->createStep($request, $node),
        };
    }

    protected function createTimerStep(ApprovalRequest $request, array $node): void
    {
        $config = $node['config'] ?? [];
        $hours = (int) ($config['hours'] ?? 24);
        $stepOrder = (int) $request->steps()->max('step_order') + 1;

        $step = ApprovalStep::create([
            'company_id' => $request->company_id,
            'approval_request_id' => $request->id,
            'node_key' => $node['id'],
            'step_order' => $stepOrder,
            'status' => 'Pending',
            'approver_resolution_mode' => 'timer',
            'due_at' => now()->addHours($hours),
            'started_at' => now(),
        ]);

        $request->update(['current_node_key' => $node['id'], 'sla_due_at' => $step->due_at]);
    }

    /**
     * Completes a Timer step once its delay has elapsed — called by the
     * hourly workflow:process-escalations sweep, not by a human decision.
     */
    public function completeTimerStep(ApprovalStep $step): void
    {
        if ($step->status !== 'Pending' || $step->approver_resolution_mode !== 'timer') {
            return;
        }

        $step->update(['status' => 'Approved', 'completed_at' => now()]);
        $this->finishStep($step);
    }

    protected function fireInlineNotification(ApprovalRequest $request, array $config): bool
    {
        $this->notifyRecipients($this->resolveApprovers($config, $request), $request, 'approval_pending');

        return true;
    }

    protected function createStep(ApprovalRequest $request, array $node): void
    {
        $config = $node['config'] ?? [];
        $escalationHours = $config['escalation_hours'] ?? (int) WorkflowSetting::getSetting((int) $request->company_id, 'default_sla_hours', 48);
        $stepOrder = (int) $request->steps()->max('step_order') + 1;

        $step = ApprovalStep::create([
            'company_id' => $request->company_id,
            'approval_request_id' => $request->id,
            'node_key' => $node['id'],
            'step_order' => $stepOrder,
            'status' => 'Pending',
            'approver_resolution_mode' => $config['approver_resolution_mode'] ?? 'any_one',
            'due_at' => now()->addHours($escalationHours),
            'started_at' => now(),
        ]);

        $approvers = $this->resolveApprovers($config, $request);
        $resolvedVia = $this->describeResolution($config);

        foreach ($approvers as $approver) {
            ApprovalAction::create([
                'company_id' => $request->company_id,
                'approval_step_id' => $step->id,
                'approver_type' => $config['approver_type'] ?? 'role',
                'approver_id' => $approver->id,
                'resolved_via' => $resolvedVia,
                'assigned_at' => now(),
            ]);
        }

        $request->update(['current_node_key' => $node['id'], 'sla_due_at' => $step->due_at]);
        $this->notifyRecipients($approvers, $request, 'approval_pending');
    }

    protected function recordAction(ApprovalAction $action, string $decision, ?string $comments): void
    {
        $action->update([
            'action' => $decision,
            'comments' => $comments,
            'acted_at' => now(),
        ]);

        $step = $action->approvalStep;
        $request = $step->approvalRequest;

        $this->logAudit($request, match ($decision) {
            'Approve' => 'approved', 'Reject' => 'rejected', 'Return' => 'returned', default => 'commented',
        }, ['comments' => $comments, 'by' => $action->approver?->name]);

        if ($decision === 'Reject') {
            $step->update(['status' => 'Rejected', 'completed_at' => now()]);
            $this->complete($request, 'Rejected');

            return;
        }

        if ($decision === 'Return') {
            $step->update(['status' => 'Returned', 'completed_at' => now()]);
            $request->update(['status' => 'Returned']);

            return;
        }

        if ($decision === 'RequestInfo') {
            return; // step stays open, no state change beyond the logged comment
        }

        if (! $this->stepIsSatisfied($step)) {
            return;
        }

        $step->update(['status' => 'Approved', 'completed_at' => now()]);
        $this->finishStep($step);
    }

    protected function finishStep(ApprovalStep $step): void
    {
        if ($this->hasOtherPendingSiblingSteps($step)) {
            return; // wait for the rest of a parallel fan-out to complete
        }

        $this->advanceFrom($step->approvalRequest, $step->node_key);
    }

    protected function stepIsSatisfied(ApprovalStep $step): bool
    {
        $actions = $step->actions;
        $decided = $actions->whereNotNull('action');

        return match ($step->approver_resolution_mode) {
            'any_one' => $decided->contains(fn (ApprovalAction $a) => $a->action === 'Approve'),
            'all_required' => $actions->count() > 0 && $actions->every(fn (ApprovalAction $a) => $a->action === 'Approve'),
            'majority' => $actions->count() > 0 && $decided->where('action', 'Approve')->count() > ($actions->count() / 2),
            default => $decided->isNotEmpty(),
        };
    }

    protected function hasOtherPendingSiblingSteps(ApprovalStep $step): bool
    {
        return ApprovalStep::query()
            ->where('approval_request_id', $step->approval_request_id)
            ->where('step_order', $step->step_order)
            ->where('id', '!=', $step->id)
            ->where('status', 'Pending')
            ->exists();
    }

    protected function complete(ApprovalRequest $request, string $status): void
    {
        $request->update(['status' => $status, 'completed_at' => now(), 'current_node_key' => null]);
        $this->logAudit($request, strtolower($status));
        $this->notifyRecipients(collect([$request->submittedBy])->filter(), $request, 'approval_' . strtolower($status));
        $this->reflectOutcomeOnApprovable($request, $status);
    }

    /**
     * Generic completion callback: if the source record being approved has
     * its own `status` column, mirror the outcome onto it — this is how an
     * existing model's own business logic (e.g. LeaveRequest's balance
     * deduction on status change) keeps firing unmodified when routed
     * through this engine instead of a manual status flip.
     */
    protected function reflectOutcomeOnApprovable(ApprovalRequest $request, string $status): void
    {
        if (! in_array($status, ['Approved', 'Rejected'], true)) {
            return;
        }

        $approvable = $request->approvable;

        if ($approvable && \Illuminate\Support\Facades\Schema::hasColumn($approvable->getTable(), 'status')) {
            $approvable->update(['status' => $status]);
        }
    }

    protected function describeResolution(array $config): string
    {
        $type = $config['approver_type'] ?? 'role';

        return match ($type) {
            'specific_user' => 'specific_user:' . ($config['user_id'] ?? '?'),
            'role' => 'role:' . ($config['role'] ?? '?'),
            'department' => 'department:' . ($config['department_id'] ?? '?'),
            'department_head' => 'department_head:' . ($config['department_id'] ?? '?'),
            'supervisor_chain' => 'supervisor_chain:level.' . ($config['level'] ?? 1),
            'committee' => 'committee:' . ($config['committee_role'] ?? 'Approval Committee'),
            default => $type,
        };
    }

    protected function resolveApprovers(array $config, ApprovalRequest $request): Collection
    {
        $type = $config['approver_type'] ?? 'role';

        return match ($type) {
            'specific_user' => User::query()->whereKey($config['user_id'] ?? null)->get(),
            'role' => $this->usersWithRole($request->company_id, $config['role'] ?? null),
            'department' => $this->usersForDepartment($request->company_id, $config['department_id'] ?? null),
            'department_head' => $this->resolveDepartmentHead($config),
            'supervisor_chain' => $this->resolveSupervisorChain($request, $config),
            'committee' => $this->usersWithRole($request->company_id, $config['committee_role'] ?? 'Approval Committee'),
            default => collect(),
        };
    }

    protected function usersWithRole(?int $companyId, ?string $role): Collection
    {
        if (! $role) {
            return collect();
        }

        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole($role))
            ->values();
    }

    protected function resolveDepartmentHead(array $config): Collection
    {
        $department = Department::withoutGlobalScope('company')->find($config['department_id'] ?? null);
        $user = $department ? $this->userForStaff($department->head) : null;

        return $user ? collect([$user]) : collect();
    }

    protected function usersForDepartment(?int $companyId, ?int $departmentId): Collection
    {
        if (! $departmentId) {
            return collect();
        }

        return Staff::withoutGlobalScope('company')
            ->where('department_id', $departmentId)
            ->get()
            ->map(fn (Staff $staff) => $this->userForStaff($staff))
            ->filter()
            ->values();
    }

    protected function resolveSupervisorChain(ApprovalRequest $request, array $config): Collection
    {
        $staffId = $request->metadata['staff_id'] ?? null;

        if (! $staffId) {
            return collect();
        }

        $staff = Staff::withoutGlobalScope('company')->find($staffId);
        $level = (int) ($config['level'] ?? 1);

        for ($i = 0; $i < $level && $staff; $i++) {
            $staff = $staff->supervisor;
        }

        $user = $staff ? $this->userForStaff($staff) : null;

        return $user ? collect([$user]) : collect();
    }

    protected function userForStaff(?Staff $staff): ?User
    {
        if (! $staff || ! $staff->email) {
            return null;
        }

        return User::query()
            ->where('company_id', $staff->company_id)
            ->where('email', $staff->email)
            ->first();
    }

    protected function logAudit(ApprovalRequest $request, string $action, array $metadata = []): void
    {
        WorkflowAuditLog::create([
            'company_id' => $request->company_id,
            'auditable_type' => ApprovalRequest::class,
            'auditable_id' => $request->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    protected function notifyRecipients(Collection $users, ApprovalRequest $request, string $type): void
    {
        $users = $users->filter();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new WorkflowNotification($type, [
            'reference' => $request->reference_number,
            'title' => $request->title,
        ]));
    }
}
