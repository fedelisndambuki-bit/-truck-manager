<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowDelegation;
use App\Models\WorkflowEscalation;

/**
 * Aggregate analytics for the Workflow & Approvals Dashboard and Reports
 * pages — mirrors the BusinessIntelligenceService pattern (thin orchestration
 * over direct queries, no dedicated service per sub-concern).
 */
class WorkflowAnalyticsService
{
    protected function requests(?int $companyId)
    {
        return ApprovalRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function pendingRequestsCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Pending')->count();
    }

    public function approvedTodayCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Approved')->whereDate('completed_at', today())->count();
    }

    public function rejectedTodayCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Rejected')->whereDate('completed_at', today())->count();
    }

    public function averageApprovalTimeHours(?int $companyId): float
    {
        $completed = $this->requests($companyId)
            ->whereNotNull('completed_at')
            ->whereNotNull('submitted_at')
            ->get();

        if ($completed->isEmpty()) {
            return 0.0;
        }

        return round($completed->avg(fn (ApprovalRequest $r) => $r->submitted_at->diffInHours($r->completed_at)), 1);
    }

    public function approvalSlaCompliancePercent(?int $companyId): float
    {
        $requests = $this->requests($companyId)->whereNotNull('completed_at')->whereNotNull('sla_due_at')->get();

        if ($requests->isEmpty()) {
            return 100.0;
        }

        $onTime = $requests->filter(fn (ApprovalRequest $r) => $r->completed_at->lte($r->sla_due_at))->count();

        return round(($onTime / $requests->count()) * 100, 1);
    }

    public function escalatedRequestsCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Escalated')->count();
    }

    public function delegatedRequestsCount(?int $companyId): int
    {
        return WorkflowDelegation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->count();
    }

    public function activeWorkflowsCount(?int $companyId): int
    {
        return WorkflowDefinition::withoutGlobalScope('company')
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('status', 'Published')
            ->count();
    }

    public function approvalTrends(?int $companyId, int $months = 6): array
    {
        $labels = [];
        $values = [];

        foreach (range($months - 1, 0) as $i) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $values[] = $this->requests($companyId)
                ->whereYear('submitted_at', $month->year)
                ->whereMonth('submitted_at', $month->month)
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function workflowUsage(?int $companyId, int $limit = 8): array
    {
        return $this->requests($companyId)
            ->with('workflowDefinition')
            ->get()
            ->groupBy(fn (ApprovalRequest $r) => $r->workflowDefinition?->name ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take($limit)
            ->all();
    }

    public function departmentActivity(?int $companyId): array
    {
        return $this->requests($companyId)
            ->with('workflowDefinition.department')
            ->get()
            ->groupBy(fn (ApprovalRequest $r) => $r->workflowDefinition?->department?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->all();
    }

    public function pendingByModule(?int $companyId): array
    {
        return $this->requests($companyId)
            ->where('status', 'Pending')
            ->with('workflowDefinition')
            ->get()
            ->groupBy(fn (ApprovalRequest $r) => $r->workflowDefinition?->module ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->all();
    }

    public function rejectedRequests(?int $companyId)
    {
        return $this->requests($companyId)->where('status', 'Rejected')->with(['workflowDefinition', 'submittedBy'])->latest('completed_at')->get();
    }

    public function escalatedRequests(?int $companyId)
    {
        return $this->requests($companyId)->where('status', 'Escalated')->with(['workflowDefinition', 'submittedBy'])->latest('updated_at')->get();
    }

    public function approvalDelays(?int $companyId)
    {
        return $this->requests($companyId)
            ->whereNotNull('sla_due_at')
            ->where(function ($q) {
                $q->where('sla_due_at', '<', now())->whereNull('completed_at')
                    ->orWhereColumn('completed_at', '>', 'sla_due_at');
            })
            ->with(['workflowDefinition', 'submittedBy'])
            ->get();
    }
}
