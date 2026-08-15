<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\PaymentCertificate;
use App\Models\ScopeOfWork;
use App\Models\Subcontractor;
use App\Models\SubcontractorPerformanceReview;
use App\Models\Variation;
use App\Models\WorkOrder;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SubcontractorReports extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Subcontractor Reports';

    protected static string $view = 'filament.pages.subcontractor-reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('register_report')
                ->label('Subcontractor Register')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateRegisterReport'),
            Action::make('contract_summary_report')
                ->label('Contract Summary')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('generateContractSummaryReport'),
            Action::make('performance_report')
                ->label('Performance Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action('generatePerformanceReport'),
            Action::make('compliance_report')
                ->label('Compliance Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('generateComplianceReport'),
            Action::make('payment_report')
                ->label('Payment Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generatePaymentReport'),
            Action::make('variation_report')
                ->label('Variation Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateVariationReport'),
            Action::make('scope_progress_report')
                ->label('Scope Progress Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateScopeProgressReport'),
            Action::make('retention_report')
                ->label('Retention Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateRetentionReport'),
            Action::make('work_order_report')
                ->label('Work Order Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateWorkOrderReport'),
            Action::make('project_subcontractor_report')
                ->label('Project Subcontractor Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateProjectSubcontractorReport'),
        ];
    }

    private function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function generateRegisterReport()
    {
        $subcontractors = Subcontractor::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('specialization')
            ->orderBy('company_name')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-register', [
            'title' => 'Subcontractor Register',
            'date' => now()->format('d M Y'),
            'subcontractors' => $subcontractors,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'subcontractor-register-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateContractSummaryReport()
    {
        $contracts = Contract::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['subcontractor', 'project'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-contract-summary', [
            'title' => 'Contract Summary Report',
            'date' => now()->format('d M Y'),
            'contracts' => $contracts,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'contract-summary-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generatePerformanceReport()
    {
        $reviews = SubcontractorPerformanceReview::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('subcontractor')
            ->latest('review_date')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-performance', [
            'title' => 'Performance Report',
            'date' => now()->format('d M Y'),
            'reviews' => $reviews,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'subcontractor-performance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateComplianceReport()
    {
        $subcontractors = Subcontractor::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->orderBy('company_name')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-compliance', [
            'title' => 'Compliance Report',
            'date' => now()->format('d M Y'),
            'subcontractors' => $subcontractors,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'subcontractor-compliance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generatePaymentReport()
    {
        $certificates = PaymentCertificate::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('contract.subcontractor')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-payments', [
            'title' => 'Payment Report',
            'date' => now()->format('d M Y'),
            'certificates' => $certificates,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'subcontractor-payments-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateVariationReport()
    {
        $variations = Variation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('contract.subcontractor')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-variations', [
            'title' => 'Variation Report',
            'date' => now()->format('d M Y'),
            'variations' => $variations,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'subcontractor-variations-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateScopeProgressReport()
    {
        $scopes = ScopeOfWork::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['contract.subcontractor', 'milestone', 'items'])
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-scope-progress', [
            'title' => 'Scope Progress Report',
            'date' => now()->format('d M Y'),
            'scopes' => $scopes,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'scope-progress-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateRetentionReport()
    {
        $contracts = Contract::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['subcontractor', 'paymentCertificates'])
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-retention', [
            'title' => 'Retention Report',
            'date' => now()->format('d M Y'),
            'contracts' => $contracts,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'retention-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateWorkOrderReport()
    {
        $workOrders = WorkOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['subcontractor', 'project', 'supervisor'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-work-orders', [
            'title' => 'Work Order Report',
            'date' => now()->format('d M Y'),
            'workOrders' => $workOrders,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'work-order-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateProjectSubcontractorReport()
    {
        $companyId = $this->companyId();

        $projects = \App\Models\Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('contracts')
            ->with(['contracts.subcontractor'])
            ->get();

        $pdf = Pdf::loadView('reports.subcontractor-by-project', [
            'title' => 'Project Subcontractor Report',
            'date' => now()->format('d M Y'),
            'projects' => $projects,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'project-subcontractor-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function getReportCards(): array
    {
        $companyId = $this->companyId();

        return [
            [
                'title' => 'Subcontractor Register',
                'description' => 'Full master list with registration, category, and compliance status.',
                'icon' => 'heroicon-o-building-office-2',
                'count' => Subcontractor::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' subcontractors',
            ],
            [
                'title' => 'Contract Summary',
                'description' => 'Awarded contracts with value, retention, and status.',
                'icon' => 'heroicon-o-document-text',
                'count' => Contract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' contracts',
            ],
            [
                'title' => 'Performance Report',
                'description' => 'Ratings across quality, timeliness, safety, and cost control.',
                'icon' => 'heroicon-o-star',
                'count' => SubcontractorPerformanceReview::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' reviews',
            ],
            [
                'title' => 'Compliance Report',
                'description' => 'Insurance, license, and NCA registration status.',
                'icon' => 'heroicon-o-shield-check',
                'count' => Subcontractor::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get()->filter(fn ($s) => $s->compliance_status !== 'Compliant')->count() . ' flagged',
            ],
            [
                'title' => 'Payment Report',
                'description' => 'Payment certificates, retention, and outstanding balances.',
                'icon' => 'heroicon-o-banknotes',
                'count' => PaymentCertificate::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' certificates',
            ],
            [
                'title' => 'Variation Report',
                'description' => 'Cost and time-impact variations by contract.',
                'icon' => 'heroicon-o-arrows-right-left',
                'count' => Variation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' variations',
            ],
            [
                'title' => 'Scope Progress Report',
                'description' => 'Deliverable completion against BOQ.',
                'icon' => 'heroicon-o-list-bullet',
                'count' => ScopeOfWork::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' scopes tracked',
            ],
            [
                'title' => 'Retention Report',
                'description' => 'Retention held per contract, pending release.',
                'icon' => 'heroicon-o-lock-closed',
                'count' => Contract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' contracts tracked',
            ],
            [
                'title' => 'Work Order Report',
                'description' => 'Work order status and completion by subcontractor.',
                'icon' => 'heroicon-o-clipboard-document-list',
                'count' => WorkOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' work orders',
            ],
            [
                'title' => 'Project Subcontractor Report',
                'description' => 'Subcontractors engaged per project.',
                'icon' => 'heroicon-o-squares-2x2',
                'count' => Contract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->distinct('project_id')->count('project_id') . ' projects',
            ],
        ];
    }
}
