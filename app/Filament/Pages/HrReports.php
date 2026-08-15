<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Certification;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\Staff;
use App\Models\TrainingRecord;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class HrReports extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 18;

    protected static ?string $title = 'HR Reports';

    protected static string $view = 'filament.pages.hr-reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('workforce_report')
                ->label('Workforce Summary')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateWorkforceReport'),
            Action::make('attendance_report')
                ->label('Attendance Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateAttendanceReport'),
            Action::make('leave_report')
                ->label('Leave Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateLeaveReport'),
            Action::make('payroll_report')
                ->label('Payroll Summary')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('generatePayrollReport'),
            Action::make('training_compliance_report')
                ->label('Training Compliance')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action('generateTrainingComplianceReport'),
            Action::make('certification_compliance_report')
                ->label('Certification Compliance')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action('generateCertificationComplianceReport'),
            Action::make('recruitment_pipeline_report')
                ->label('Recruitment Pipeline')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateRecruitmentPipelineReport'),
            Action::make('ppe_compliance_report')
                ->label('PPE Compliance')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generatePpeComplianceReport'),
        ];
    }

    private function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function generateWorkforceReport()
    {
        $staff = Staff::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['department', 'position'])
            ->orderBy('full_name')
            ->get();

        $pdf = Pdf::loadView('reports.hr-workforce', [
            'title' => 'Workforce Summary Report',
            'date' => now()->format('d M Y'),
            'staff' => $staff,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'workforce-summary-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateAttendanceReport()
    {
        $attendances = Attendance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->with(['employee', 'site'])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('reports.hr-attendance', [
            'title' => 'Attendance Report — ' . now()->format('F Y'),
            'date' => now()->format('d M Y'),
            'attendances' => $attendances,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'attendance-report-' . now()->format('Y-m') . '.pdf');
    }

    public function generateLeaveReport()
    {
        $leaveRequests = LeaveRequest::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['staff', 'leaveType'])
            ->latest('start_date')
            ->get();

        $pdf = Pdf::loadView('reports.hr-leave', [
            'title' => 'Leave Report',
            'date' => now()->format('d M Y'),
            'leaveRequests' => $leaveRequests,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'leave-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generatePayrollReport()
    {
        $period = PayrollPeriod::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->latest('year')->latest('month')
            ->first();

        $payslips = $period ? $period->payslips()->with('staff')->get() : collect();

        $pdf = Pdf::loadView('reports.hr-payroll-summary', [
            'title' => 'Payroll Summary Report',
            'date' => now()->format('d M Y'),
            'period' => $period,
            'payslips' => $payslips,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'payroll-summary-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateTrainingComplianceReport()
    {
        $training = TrainingRecord::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('staff')
            ->orderBy('expiry_date')
            ->get();

        $pdf = Pdf::loadView('reports.hr-training-compliance', [
            'title' => 'Training Compliance Report',
            'date' => now()->format('d M Y'),
            'training' => $training,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'training-compliance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateCertificationComplianceReport()
    {
        $certifications = Certification::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('staff')
            ->orderBy('expiry_date')
            ->get();

        $pdf = Pdf::loadView('reports.hr-certification-compliance', [
            'title' => 'Certification Compliance Report',
            'date' => now()->format('d M Y'),
            'certifications' => $certifications,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'certification-compliance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateRecruitmentPipelineReport()
    {
        $vacancies = \App\Models\JobVacancy::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('applications')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.hr-recruitment-pipeline', [
            'title' => 'Recruitment Pipeline Report',
            'date' => now()->format('d M Y'),
            'vacancies' => $vacancies,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'recruitment-pipeline-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generatePpeComplianceReport()
    {
        $issuances = \App\Models\PpeIssuance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('staff')
            ->orderBy('replacement_due_date')
            ->get();

        $pdf = Pdf::loadView('reports.hr-ppe-compliance', [
            'title' => 'PPE Compliance Report',
            'date' => now()->format('d M Y'),
            'issuances' => $issuances,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'ppe-compliance-' . now()->format('Y-m-d') . '.pdf');
    }

    public function getReportCards(): array
    {
        $companyId = $this->companyId();

        return [
            [
                'title' => 'Workforce Summary',
                'description' => 'Full employee roster with department, position, and status.',
                'icon' => 'heroicon-o-users',
                'count' => Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' employees',
            ],
            [
                'title' => 'Attendance Report',
                'description' => 'This month\'s attendance across all employees.',
                'icon' => 'heroicon-o-finger-print',
                'count' => Attendance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereMonth('date', now()->month)->count() . ' records this month',
            ],
            [
                'title' => 'Leave Report',
                'description' => 'All leave requests, approvals, and balances.',
                'icon' => 'heroicon-o-calendar-days',
                'count' => LeaveRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' requests',
            ],
            [
                'title' => 'Payroll Summary',
                'description' => 'Latest processed payroll run with statutory breakdown.',
                'icon' => 'heroicon-o-banknotes',
                'count' => PayrollPeriod::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' payroll runs',
            ],
            [
                'title' => 'Training Compliance',
                'description' => 'Safety and technical training status across the workforce.',
                'icon' => 'heroicon-o-academic-cap',
                'count' => TrainingRecord::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' records',
            ],
            [
                'title' => 'Certification Compliance',
                'description' => 'Trade, equipment, and professional certification status.',
                'icon' => 'heroicon-o-document-check',
                'count' => Certification::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' certifications',
            ],
            [
                'title' => 'Recruitment Pipeline',
                'description' => 'Vacancies and application pipeline.',
                'icon' => 'heroicon-o-briefcase',
                'count' => \App\Models\JobVacancy::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' vacancies',
            ],
            [
                'title' => 'PPE Compliance',
                'description' => 'PPE issuance and replacement status.',
                'icon' => 'heroicon-o-shield-check',
                'count' => \App\Models\PpeIssuance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' issuances',
            ],
        ];
    }
}
