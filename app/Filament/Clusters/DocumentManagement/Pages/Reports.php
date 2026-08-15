<?php

namespace App\Filament\Clusters\DocumentManagement\Pages;

use App\Filament\Clusters\DocumentManagement;
use App\Models\DocumentApproval;
use App\Models\DocumentAuditLog;
use App\Models\DocumentVersion;
use App\Models\Drawing;
use App\Models\EdmsDocument;
use App\Models\Rfi;
use App\Traits\HasModuleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.export';
    }

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 19;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.document-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getReportDefinitions(): array
    {
        return [
            ['key' => 'document_register', 'label' => 'Document Register', 'icon' => 'heroicon-o-document-text'],
            ['key' => 'drawing_register', 'label' => 'Drawing Register', 'icon' => 'heroicon-o-pencil-square'],
            ['key' => 'rfi_report', 'label' => 'RFI Report', 'icon' => 'heroicon-o-question-mark-circle'],
            ['key' => 'approval_report', 'label' => 'Approval Report', 'icon' => 'heroicon-o-check-badge'],
            ['key' => 'revision_report', 'label' => 'Revision Report', 'icon' => 'heroicon-o-clock'],
            ['key' => 'audit_trail_report', 'label' => 'Audit Trail Report', 'icon' => 'heroicon-o-shield-check'],
            ['key' => 'expired_documents', 'label' => 'Expired Documents', 'icon' => 'heroicon-o-exclamation-triangle'],
            ['key' => 'contract_register', 'label' => 'Contract Register', 'icon' => 'heroicon-o-document-check'],
            ['key' => 'document_activity', 'label' => 'Document Activity Report', 'icon' => 'heroicon-o-chart-bar'],
        ];
    }

    protected function download(string $title, array $sections, string $filename)
    {
        $pdf = Pdf::loadView('reports.project-finance-report', [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'kpis' => [],
            'sections' => $sections,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateDocumentRegister()
    {
        $rows = EdmsDocument::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('project')->get()
            ->map(fn (EdmsDocument $d) => [$d->document_number, $d->title, $d->category, $d->project?->name, $d->current_version, $d->status])
            ->all();

        return $this->download('Document Register', [
            ['title' => 'All Documents', 'columns' => ['Doc #', 'Title', 'Category', 'Project', 'Version', 'Status'], 'rows' => $rows],
        ], 'document-register');
    }

    public function generateDrawingRegister()
    {
        $rows = Drawing::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('project')->get()
            ->map(fn (Drawing $d) => [$d->drawing_number, $d->title, $d->type, $d->discipline, $d->project?->name, $d->current_version, $d->status])
            ->all();

        return $this->download('Drawing Register', [
            ['title' => 'All Drawings', 'columns' => ['Drawing #', 'Title', 'Type', 'Discipline', 'Project', 'Version', 'Status'], 'rows' => $rows],
        ], 'drawing-register');
    }

    public function generateRfiReport()
    {
        $rows = Rfi::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['project', 'raisedBy', 'assignedTo'])->get()
            ->map(fn (Rfi $r) => [$r->rfi_number, $r->subject, $r->project?->name, $r->raisedBy?->full_name, $r->assignedTo?->full_name, $r->priority, optional($r->due_date)->format('d M Y'), $r->status])
            ->all();

        return $this->download('RFI Report', [
            ['title' => 'All RFIs', 'columns' => ['RFI #', 'Subject', 'Project', 'Raised By', 'Assigned To', 'Priority', 'Due Date', 'Status'], 'rows' => $rows],
        ], 'rfi-report');
    }

    public function generateApprovalReport()
    {
        $rows = DocumentApproval::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('approvable', 'approver')->get()
            ->map(fn (DocumentApproval $a) => [class_basename($a->approvable_type), $a->approvable?->title ?? $a->approvable?->subject ?? '—', $a->step_order, $a->approver_role, $a->status, optional($a->acted_at)->format('d M Y H:i')])
            ->all();

        return $this->download('Approval Report', [
            ['title' => 'Approval Workflow Steps', 'columns' => ['Type', 'Item', 'Step', 'Approver Role', 'Status', 'Acted At'], 'rows' => $rows],
        ], 'approval-report');
    }

    public function generateRevisionReport()
    {
        $rows = DocumentVersion::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('versionable', 'uploadedBy')->latest('created_at')->limit(200)->get()
            ->map(fn (DocumentVersion $v) => [class_basename($v->versionable_type), $v->versionable?->title ?? $v->versionable?->subject ?? '—', $v->version_number, $v->revision_number, $v->uploadedBy?->name, $v->is_current ? 'Current' : 'Superseded', $v->created_at->format('d M Y')])
            ->all();

        return $this->download('Revision Report', [
            ['title' => 'Version History', 'columns' => ['Type', 'Item', 'Version', 'Revision #', 'Uploaded By', 'Status', 'Date'], 'rows' => $rows],
        ], 'revision-report');
    }

    public function generateAuditTrailReport()
    {
        $rows = DocumentAuditLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('user', 'auditable')->latest('created_at')->limit(300)->get()
            ->map(fn (DocumentAuditLog $log) => [class_basename($log->auditable_type), $log->auditable?->title ?? $log->auditable?->subject ?? '—', $log->action, $log->user?->name, $log->ip_address, $log->created_at->format('d M Y H:i')])
            ->all();

        return $this->download('Audit Trail Report', [
            ['title' => 'Access & Change Log', 'columns' => ['Type', 'Item', 'Action', 'User', 'IP Address', 'Date'], 'rows' => $rows],
        ], 'audit-trail-report');
    }

    public function generateExpiredDocuments()
    {
        $rows = EdmsDocument::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())
            ->with('project')->get()
            ->map(fn (EdmsDocument $d) => [$d->document_number, $d->title, $d->category, $d->project?->name, optional($d->expiry_date)->format('d M Y')])
            ->all();

        return $this->download('Expired Documents', [
            ['title' => 'Expired Documents', 'columns' => ['Doc #', 'Title', 'Category', 'Project', 'Expired On'], 'rows' => $rows],
        ], 'expired-documents');
    }

    public function generateContractRegister()
    {
        $rows = EdmsDocument::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('category', 'Contracts')
            ->with('project')->get()
            ->map(fn (EdmsDocument $d) => [$d->document_number, $d->title, $d->project?->name, optional($d->expiry_date)->format('d M Y'), $d->status])
            ->all();

        return $this->download('Contract Register', [
            ['title' => 'Contracts on File', 'columns' => ['Doc #', 'Title', 'Project', 'Expiry Date', 'Status'], 'rows' => $rows],
        ], 'contract-register');
    }

    public function generateDocumentActivity()
    {
        $rows = DocumentAuditLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->get()
            ->map(fn ($row) => [$row->action, $row->total])
            ->all();

        return $this->download('Document Activity Report', [
            ['title' => 'Activity Summary', 'columns' => ['Action', 'Count'], 'rows' => $rows],
        ], 'document-activity-report');
    }
}
