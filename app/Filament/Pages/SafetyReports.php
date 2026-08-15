<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SafetyTrainingResource;
use App\Models\ComplianceRecord;
use App\Models\CorrectiveAction;
use App\Models\Hazard;
use App\Models\MedicalCase;
use App\Models\NearMiss;
use App\Models\PpeIssuance;
use App\Models\RiskAssessment;
use App\Models\SafetyIncident;
use App\Models\SafetyInspection;
use App\Models\SafetyMeeting;
use App\Models\ToolboxTalk;
use App\Models\TrainingRecord;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SafetyReports extends \Filament\Pages\Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 160;

    protected static ?string $title = 'Safety Reports';

    protected static string $view = 'filament.pages.safety-reports';

    private function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    private function download($view, array $data, string $filename)
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename . '-' . now()->format('Y-m-d') . '.pdf');
    }

    private function table(string $title, array $headers, array $rows, string $filename)
    {
        return $this->download('reports.safety-generic', [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'headers' => $headers,
            'rows' => $rows,
        ], $filename);
    }

    public function getReportCards(): array
    {
        $c = $this->companyId();
        $when = fn ($q) => $q->when($c, fn ($qq) => $qq->where('company_id', $c));

        return [
            ['title' => 'Incident Report', 'icon' => 'heroicon-o-exclamation-triangle', 'action' => 'generateIncidentReport', 'count' => $when(SafetyIncident::query())->count() . ' incidents'],
            ['title' => 'Near Miss Report', 'icon' => 'heroicon-o-eye', 'action' => 'generateNearMissReport', 'count' => $when(NearMiss::query())->count() . ' near misses'],
            ['title' => 'Hazard Register', 'icon' => 'heroicon-o-fire', 'action' => 'generateHazardRegister', 'count' => $when(Hazard::query())->count() . ' hazards'],
            ['title' => 'Risk Assessment Report', 'icon' => 'heroicon-o-clipboard-document-list', 'action' => 'generateRiskAssessmentReport', 'count' => $when(RiskAssessment::query())->count() . ' assessments'],
            ['title' => 'Inspection Report', 'icon' => 'heroicon-o-clipboard-document-check', 'action' => 'generateInspectionReport', 'count' => $when(SafetyInspection::query())->count() . ' inspections'],
            ['title' => 'Toolbox Talk Report', 'icon' => 'heroicon-o-megaphone', 'action' => 'generateToolboxTalkReport', 'count' => $when(ToolboxTalk::query())->count() . ' talks'],
            ['title' => 'Safety Meeting Report', 'icon' => 'heroicon-o-user-group', 'action' => 'generateSafetyMeetingReport', 'count' => $when(SafetyMeeting::query())->count() . ' meetings'],
            ['title' => 'PPE Report', 'icon' => 'heroicon-o-shield-check', 'action' => 'generatePpeReport', 'count' => $when(PpeIssuance::query())->count() . ' issuances'],
            ['title' => 'Medical Report', 'icon' => 'heroicon-o-heart', 'action' => 'generateMedicalReport', 'count' => $when(MedicalCase::query())->count() . ' cases'],
            ['title' => 'Compliance Report', 'icon' => 'heroicon-o-scale', 'action' => 'generateComplianceReport', 'count' => $when(ComplianceRecord::query())->count() . ' records'],
            ['title' => 'Executive Safety Dashboard', 'icon' => 'heroicon-o-presentation-chart-line', 'action' => 'generateExecutiveDashboard', 'count' => 'Company-wide summary'],
        ];
    }

    public function generateIncidentReport()
    {
        $c = $this->companyId();
        $rows = SafetyIncident::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'reportedBy'])->latest('date')->get()
            ->map(fn ($r) => [$r->incident_number, $r->date?->format('d M Y'), $r->site?->name, $r->incident_category, $r->severity, $r->injury_classification, $r->status, $r->reportedBy?->full_name])
            ->toArray();

        return $this->table('Incident Report', ['#', 'Date', 'Site', 'Category', 'Severity', 'Injury Type', 'Status', 'Reported By'], $rows, 'incident-report');
    }

    public function generateNearMissReport()
    {
        $c = $this->companyId();
        $rows = NearMiss::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'reportedBy'])->latest('date')->get()
            ->map(fn ($r) => [$r->near_miss_number, $r->date?->format('d M Y'), $r->site?->name, Str::limit($r->description, 60), $r->risk_level, $r->status, $r->reportedBy?->full_name])
            ->toArray();

        return $this->table('Near Miss Report', ['#', 'Date', 'Site', 'Description', 'Risk Level', 'Status', 'Reported By'], $rows, 'near-miss-report');
    }

    public function generateHazardRegister()
    {
        $c = $this->companyId();
        $rows = Hazard::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'responsibleStaff'])->latest()->get()
            ->map(fn ($r) => [$r->hazard_number, $r->site?->name, $r->hazard_type, $r->risk_rating, $r->responsibleStaff?->full_name, $r->target_date?->format('d M Y'), $r->status])
            ->toArray();

        return $this->table('Hazard Register', ['ID', 'Site', 'Type', 'Risk Rating', 'Responsible', 'Target Date', 'Status'], $rows, 'hazard-register');
    }

    public function generateRiskAssessmentReport()
    {
        $c = $this->companyId();
        $rows = RiskAssessment::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'assessedBy'])->latest()->get()
            ->map(fn ($r) => [$r->assessment_number, $r->site?->name, $r->activity, $r->risk_level, $r->residual_risk_level ?? '—', $r->status, $r->assessedBy?->full_name])
            ->toArray();

        return $this->table('Risk Assessment Report', ['#', 'Site', 'Activity', 'Initial Risk', 'Residual Risk', 'Status', 'Assessed By'], $rows, 'risk-assessment-report');
    }

    public function generateInspectionReport()
    {
        $c = $this->companyId();
        $rows = SafetyInspection::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'inspector'])->latest('date')->get()
            ->map(fn ($r) => [$r->inspection_number, $r->inspection_type, $r->site?->name, $r->date?->format('d M Y'), $r->inspector?->full_name, $r->result, $r->status])
            ->toArray();

        return $this->table('Safety Inspection Report', ['#', 'Type', 'Site', 'Date', 'Inspector', 'Result', 'Status'], $rows, 'inspection-report');
    }

    public function generateToolboxTalkReport()
    {
        $c = $this->companyId();
        $rows = ToolboxTalk::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with(['site', 'presenter'])->withCount('attendees')->latest('date')->get()
            ->map(fn ($r) => [$r->topic, $r->site?->name, $r->date?->format('d M Y'), $r->presenter?->full_name, $r->attendees_count])
            ->toArray();

        return $this->table('Toolbox Talk Report', ['Topic', 'Site', 'Date', 'Presenter', 'Attendees'], $rows, 'toolbox-talk-report');
    }

    public function generateSafetyMeetingReport()
    {
        $c = $this->companyId();
        $rows = SafetyMeeting::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with('site')->withCount('actionItems')->latest('date')->get()
            ->map(fn ($r) => [$r->title, $r->site?->name, $r->date?->format('d M Y'), $r->attendees_count, $r->action_items_count])
            ->toArray();

        return $this->table('Safety Meeting Report', ['Title', 'Site', 'Date', 'Attendees', 'Action Items'], $rows, 'safety-meeting-report');
    }

    public function generatePpeReport()
    {
        $c = $this->companyId();
        $rows = PpeIssuance::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with('staff')->latest('issue_date')->get()
            ->map(fn ($r) => [$r->staff?->full_name, $r->item, $r->size, $r->issue_date?->format('d M Y'), $r->replacement_due_date?->format('d M Y') ?? '—', $r->status])
            ->toArray();

        return $this->table('PPE Compliance Report', ['Employee', 'Item', 'Size', 'Issue Date', 'Expiry / Due', 'Status'], $rows, 'ppe-report');
    }

    public function generateMedicalReport()
    {
        $c = $this->companyId();
        $rows = MedicalCase::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with('staff')->latest('visit_date')->get()
            ->map(fn ($r) => [$r->case_number, $r->staff?->full_name, $r->visit_date?->format('d M Y'), $r->hospital, $r->medical_clearance, $r->status])
            ->toArray();

        return $this->table('Medical Case Report', ['Case #', 'Employee', 'Visit Date', 'Hospital', 'Clearance', 'Status'], $rows, 'medical-report');
    }

    public function generateComplianceReport()
    {
        $c = $this->companyId();
        $rows = ComplianceRecord::query()->when($c, fn ($q) => $q->where('company_id', $c))
            ->with('site')->orderBy('expiry_date')->get()
            ->map(fn ($r) => [$r->type, $r->title, $r->site?->name ?? '—', $r->issuing_authority, $r->expiry_date?->format('d M Y') ?? '—', $r->status])
            ->toArray();

        return $this->table('Compliance Report', ['Type', 'Title', 'Site', 'Issuing Authority', 'Expiry', 'Status'], $rows, 'compliance-report');
    }

    public function generateExecutiveDashboard()
    {
        $c = $this->companyId();
        $when = fn ($q) => $q->when($c, fn ($qq) => $qq->where('company_id', $c));

        $stats = [
            'Total Incidents' => $when(SafetyIncident::query())->count(),
            'Lost Time Injuries' => $when(SafetyIncident::query())->where('injury_classification', 'Lost Time Injury')->count(),
            'Near Misses' => $when(NearMiss::query())->count(),
            'Open Hazards' => $when(Hazard::query())->whereNotIn('status', ['Closed'])->count(),
            'Open Corrective Actions' => $when(CorrectiveAction::query())->whereNotIn('status', ['Closed'])->count(),
            'Safety Inspections' => $when(SafetyInspection::query())->count(),
            'Toolbox Talks Held' => $when(ToolboxTalk::query())->count(),
            'Safety Training Records' => $when(TrainingRecord::query())->whereIn('training_type', SafetyTrainingResource::SAFETY_TYPES)->count(),
        ];

        $pdf = Pdf::loadView('reports.safety-executive-dashboard', [
            'title' => 'Executive Safety Dashboard',
            'date' => now()->format('d M Y'),
            'stats' => $stats,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'executive-safety-dashboard-' . now()->format('Y-m-d') . '.pdf');
    }
}
