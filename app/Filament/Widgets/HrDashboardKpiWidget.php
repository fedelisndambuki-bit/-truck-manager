<?php

namespace App\Filament\Widgets;

use App\Models\Certification;
use App\Models\DisciplinaryCase;
use App\Models\JobVacancy;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PpeIssuance;
use App\Models\SafetyIncident;
use App\Models\Staff;
use App\Models\TrainingRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HrDashboardKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $staff = Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $staff)->count();
        $active = (clone $staff)->where('status', 'Active')->count();
        $onLeave = (clone $staff)->where('status', 'On Leave')->count();
        $newHires = (clone $staff)->whereMonth('date_hired', now()->month)->whereYear('date_hired', now()->year)->count();

        $openVacancies = JobVacancy::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Open')->sum('openings_count');
        $pendingLeave = LeaveRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Pending')->count();
        $openIncidents = SafetyIncident::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereIn('status', ['Open', 'Investigating'])->count();

        $trainingTotal = TrainingRecord::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $trainingCompleted = TrainingRecord::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Completed')->count();
        $trainingCompliance = $trainingTotal > 0 ? round(($trainingCompleted / $trainingTotal) * 100) : 0;

        $activeCertifications = Certification::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()))->count();
        $totalCertifications = Certification::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $certCompliance = $totalCertifications > 0 ? round(($activeCertifications / $totalCertifications) * 100) : 0;

        $ppeDue = PpeIssuance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Due for Replacement')->count();

        $latestPayroll = PayrollPeriod::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->latest('year')->latest('month')->first();

        $avgAttendanceRate = \App\Models\Attendance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('date', now()->month)
            ->selectRaw("avg(case when status = 'Present' then 100.0 else 0 end) as rate")
            ->value('rate');

        $pendingDisciplinary = DisciplinaryCase::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Open')->count();

        $expiringCerts = Certification::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])->count();

        return [
            Stat::make('Total Staff', (string) $total)->color('info'),
            Stat::make('Active', (string) $active)->color('success'),
            Stat::make('On Leave', (string) $onLeave)->color($onLeave > 0 ? 'warning' : 'success'),
            Stat::make('New Hires (This Month)', (string) $newHires)->color('success'),
            Stat::make('Open Vacancies', (string) $openVacancies)->color('info'),
            Stat::make('Pending Leave Requests', (string) $pendingLeave)->color($pendingLeave > 0 ? 'warning' : 'success'),
            Stat::make('Open Safety Incidents', (string) $openIncidents)->color($openIncidents > 0 ? 'danger' : 'success'),
            Stat::make('Training Compliance', $trainingCompliance . '%')->color($trainingCompliance >= 80 ? 'success' : 'warning'),
            Stat::make('Certification Compliance', $certCompliance . '%')->color($certCompliance >= 80 ? 'success' : 'warning'),
            Stat::make('PPE Due for Replacement', (string) $ppeDue)->color($ppeDue > 0 ? 'warning' : 'success'),
            Stat::make('Latest Payroll Net', $latestPayroll ? session('currency', 'KES') . ' ' . number_format($latestPayroll->total_net, 2) : '—')->color('success'),
            Stat::make('Avg. Attendance Rate', round($avgAttendanceRate ?? 0) . '%')->color('info'),
            Stat::make('Open Disciplinary Cases', (string) $pendingDisciplinary)->color($pendingDisciplinary > 0 ? 'danger' : 'success'),
            Stat::make('Certifications Expiring (30d)', (string) $expiringCerts)->color($expiringCerts > 0 ? 'warning' : 'success'),
        ];
    }
}
