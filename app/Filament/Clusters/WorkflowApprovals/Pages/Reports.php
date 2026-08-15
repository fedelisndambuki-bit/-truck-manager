<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Models\WorkflowAuditLog;
use App\Models\WorkflowDefinition;
use App\Services\Construction\ReportExportService;
use App\Services\WorkflowAnalyticsService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.export';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Approval Performance', 'description' => 'Volume, average time and SLA compliance across every workflow.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportApprovalPerformance'],
            ['label' => 'Approval SLA', 'description' => 'On-time vs breached approvals against each step\'s SLA.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportApprovalSla'],
            ['label' => 'Workflow Usage', 'description' => 'Which workflows are used most.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWorkflowUsage'],
            ['label' => 'Department Activity', 'description' => 'Approval volume by owning department.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDepartmentActivity'],
            ['label' => 'Approval Delays', 'description' => 'Requests that missed or are at risk of missing their SLA.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportApprovalDelays'],
            ['label' => 'Rejected Requests', 'description' => 'Every rejected approval request.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportRejectedRequests'],
            ['label' => 'Escalated Requests', 'description' => 'Every escalated approval request.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEscalatedRequests'],
            ['label' => 'Audit Report', 'description' => 'Full workflow audit trail export.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportAuditReport'],
        ];
    }

    public function exportApprovalPerformance()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Pending Requests', 'value' => (string) $svc->pendingRequestsCount($companyId)],
            ['label' => 'Approved Today', 'value' => (string) $svc->approvedTodayCount($companyId)],
            ['label' => 'Average Approval Time', 'value' => $svc->averageApprovalTimeHours($companyId) . ' hrs'],
            ['label' => 'SLA Compliance', 'value' => $svc->approvalSlaCompliancePercent($companyId) . '%'],
        ];

        $rows = collect($svc->workflowUsage($companyId, 20))->map(fn ($count, $name) => [$name, $count])->values()->all();

        return app(ReportExportService::class)->toPdf('Approval Performance Report', [
            ['title' => 'Requests by Workflow', 'columns' => ['Workflow', 'Requests'], 'rows' => $rows],
        ], $kpis);
    }

    public function exportApprovalSla()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'SLA Compliance', 'value' => $svc->approvalSlaCompliancePercent($companyId) . '%'],
            ['label' => 'Average Approval Time', 'value' => $svc->averageApprovalTimeHours($companyId) . ' hrs'],
        ];

        $rows = $svc->approvalDelays($companyId)->map(fn ($r) => [
            $r->reference_number, $r->title, $r->workflowDefinition?->name, optional($r->sla_due_at)->format('d M Y H:i'),
        ])->all();

        return app(ReportExportService::class)->toPdf('Approval SLA Report', [
            ['title' => 'At-Risk / Breached Requests', 'columns' => ['Reference', 'Title', 'Workflow', 'SLA Due'], 'rows' => $rows],
        ], $kpis);
    }

    public function exportWorkflowUsage()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $rows = collect($svc->workflowUsage($this->companyId(), 50))->map(fn ($count, $name) => [$name, $count])->values()->all();

        return app(ReportExportService::class)->toPdf('Workflow Usage Report', [
            ['title' => 'Requests by Workflow', 'columns' => ['Workflow', 'Requests'], 'rows' => $rows],
        ]);
    }

    public function exportDepartmentActivity()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $rows = collect($svc->departmentActivity($this->companyId()))->map(fn ($count, $name) => [$name, $count])->values()->all();

        return app(ReportExportService::class)->toPdf('Department Activity Report', [
            ['title' => 'Requests by Department', 'columns' => ['Department', 'Requests'], 'rows' => $rows],
        ]);
    }

    public function exportApprovalDelays()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $rows = $svc->approvalDelays($this->companyId())->map(fn ($r) => [
            $r->reference_number, $r->title, $r->status, optional($r->sla_due_at)->format('d M Y H:i'),
        ])->all();

        return app(ReportExportService::class)->toPdf('Approval Delays Report', [
            ['title' => 'Delayed Requests', 'columns' => ['Reference', 'Title', 'Status', 'SLA Due'], 'rows' => $rows],
        ]);
    }

    public function exportRejectedRequests()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $rows = $svc->rejectedRequests($this->companyId())->map(fn ($r) => [
            $r->reference_number, $r->title, $r->workflowDefinition?->name, $r->submittedBy?->name, optional($r->completed_at)->format('d M Y'),
        ])->all();

        return app(ReportExportService::class)->toPdf('Rejected Requests Report', [
            ['title' => 'Rejected Requests', 'columns' => ['Reference', 'Title', 'Workflow', 'Submitted By', 'Rejected On'], 'rows' => $rows],
        ]);
    }

    public function exportEscalatedRequests()
    {
        $svc = app(WorkflowAnalyticsService::class);
        $rows = $svc->escalatedRequests($this->companyId())->map(fn ($r) => [
            $r->reference_number, $r->title, $r->workflowDefinition?->name, $r->submittedBy?->name,
        ])->all();

        return app(ReportExportService::class)->toPdf('Escalated Requests Report', [
            ['title' => 'Escalated Requests', 'columns' => ['Reference', 'Title', 'Workflow', 'Submitted By'], 'rows' => $rows],
        ]);
    }

    public function exportAuditReport()
    {
        $companyId = $this->companyId();

        $rows = WorkflowAuditLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('user')
            ->latest()
            ->limit(500)
            ->get()
            ->map(fn (WorkflowAuditLog $log) => [
                $log->action, class_basename($log->auditable_type), $log->auditable_id,
                $log->user?->name ?? 'System', $log->created_at->format('d M Y H:i'),
            ])
            ->all();

        return app(ReportExportService::class)->toPdf('Workflow Audit Report', [
            ['title' => 'Audit Trail (last 500 entries)', 'columns' => ['Action', 'Entity', 'Ref #', 'User', 'When'], 'rows' => $rows],
        ]);
    }
}
