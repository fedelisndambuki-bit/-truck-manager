<?php

namespace App\Filament\Pages;

use App\Models\Certification;
use App\Models\PpeIssuance;
use App\Models\SafetyIncident;
use App\Models\SafetyMeeting;
use App\Models\Staff;
use App\Models\TrainingRecord;
use App\Traits\HasIndustryAccess;
use Illuminate\Support\Facades\Auth;

class HealthAndSafety extends \Filament\Pages\Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Health & Safety';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Health & Safety';

    protected static string $view = 'filament.pages.health-and-safety';

    public function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $incidents = SafetyIncident::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $openIncidents = (clone $incidents)->whereIn('status', ['Open', 'Investigating'])->count();
        $criticalIncidents = (clone $incidents)->where('severity', 'Critical')->whereIn('status', ['Open', 'Investigating'])->count();

        $activeStaff = Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Active');
        $totalActive = (clone $activeStaff)->count();

        $safetyTrainingTypes = ['Safety Induction', 'Fire Safety', 'First Aid'];
        $staffWithValidTraining = (clone $activeStaff)
            ->whereHas('trainingRecords', function ($q) use ($safetyTrainingTypes) {
                $q->whereIn('training_type', $safetyTrainingTypes)
                    ->where('status', 'Completed')
                    ->where(fn ($q2) => $q2->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()));
            })
            ->count();

        $staffWithValidCertification = (clone $activeStaff)
            ->whereHas('certifications', function ($q) {
                $q->where(fn ($q2) => $q2->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()));
            })
            ->count();

        $expiringCertifications = Certification::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        $ppeDueForReplacement = PpeIssuance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Due for Replacement')
            ->count();

        $meetingsThisMonth = SafetyMeeting::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        return [
            'open_incidents' => $openIncidents,
            'critical_incidents' => $criticalIncidents,
            'training_compliance_percent' => $totalActive > 0 ? round(($staffWithValidTraining / $totalActive) * 100) : 0,
            'certification_compliance_percent' => $totalActive > 0 ? round(($staffWithValidCertification / $totalActive) * 100) : 0,
            'expiring_certifications' => $expiringCertifications,
            'ppe_due_for_replacement' => $ppeDueForReplacement,
            'meetings_this_month' => $meetingsThisMonth,
        ];
    }

    public function getRecentMeetings()
    {
        $companyId = Auth::user()?->company_id;

        return SafetyMeeting::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('site')
            ->latest('date')
            ->limit(10)
            ->get();
    }
}
