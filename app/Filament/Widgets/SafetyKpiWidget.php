<?php

namespace App\Filament\Widgets;

use App\Models\CorrectiveAction;
use App\Models\Hazard;
use App\Models\NearMiss;
use App\Models\PpeIssuance;
use App\Filament\Resources\SafetyTrainingResource;
use App\Models\SafetyIncident;
use App\Models\SafetyInspection;
use App\Models\TrainingRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SafetyKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $incidents = SafetyIncident::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $totalIncidents = (clone $incidents)->count();
        $lostTimeInjuries = (clone $incidents)->where('injury_classification', 'Lost Time Injury')->count();
        $medicalTreatmentCases = (clone $incidents)->where('injury_classification', 'Medical Treatment Case')->count();
        $firstAidCases = (clone $incidents)->where('injury_classification', 'First Aid Case')->count();

        $nearMisses = NearMiss::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        $hazards = Hazard::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $openHazards = (clone $hazards)->whereNotIn('status', ['Closed'])->count();

        $correctiveActions = CorrectiveAction::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $openCorrectiveActions = (clone $correctiveActions)->whereNotIn('status', ['Closed'])->count();

        $inspections = SafetyInspection::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $inspectionsDue = (clone $inspections)->where('status', 'Draft')->count();

        $ppe = PpeIssuance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', '!=', 'Returned')->get();
        $ppeCompliant = $ppe->filter(fn ($p) => ! $p->replacement_due_date || $p->replacement_due_date->isFuture())->count();
        $ppeCompliancePercent = $ppe->count() > 0 ? round(($ppeCompliant / $ppe->count()) * 100) : 100;

        $safetyTraining = TrainingRecord::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('training_type', SafetyTrainingResource::SAFETY_TYPES);
        $trainingTotal = (clone $safetyTraining)->count();
        $trainingCompliant = (clone $safetyTraining)->where('status', 'Completed')
            ->where(fn ($q) => $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()))->count();
        $trainingCompliancePercent = $trainingTotal > 0 ? round(($trainingCompliant / $trainingTotal) * 100) : 100;

        $lastLostTimeInjury = (clone $incidents)->where('injury_classification', 'Lost Time Injury')->latest('date')->first();
        $daysSinceLostTime = $lastLostTimeInjury ? (int) now()->diffInDays($lastLostTimeInjury->date) : null;

        // Composite safety score: starts at 100, penalized by open risk exposure, rewarded by compliance.
        $safetyScore = max(0, min(100, round(
            70
            + ($trainingCompliancePercent * 0.15)
            + ($ppeCompliancePercent * 0.15)
            - ($openHazards * 2)
            - ($openCorrectiveActions * 1.5)
            - ($totalIncidents > 0 ? min(20, $totalIncidents * 2) : 0)
        )));

        return [
            Stat::make('Total Incidents', (string) $totalIncidents)->color($totalIncidents > 0 ? 'danger' : 'success'),
            Stat::make('Lost Time Injuries', (string) $lostTimeInjuries)->color($lostTimeInjuries > 0 ? 'danger' : 'success'),
            Stat::make('Medical Treatment Cases', (string) $medicalTreatmentCases)->color($medicalTreatmentCases > 0 ? 'warning' : 'success'),
            Stat::make('First Aid Cases', (string) $firstAidCases)->color('info'),
            Stat::make('Near Miss Reports', (string) $nearMisses)->color('info'),
            Stat::make('Open Hazards', (string) $openHazards)->color($openHazards > 0 ? 'warning' : 'success'),
            Stat::make('Open Corrective Actions', (string) $openCorrectiveActions)->color($openCorrectiveActions > 0 ? 'warning' : 'success'),
            Stat::make('Inspections Due', (string) $inspectionsDue)->color($inspectionsDue > 0 ? 'warning' : 'success'),
            Stat::make('PPE Compliance', $ppeCompliancePercent . '%')->color($ppeCompliancePercent >= 80 ? 'success' : 'warning'),
            Stat::make('Training Compliance', $trainingCompliancePercent . '%')->color($trainingCompliancePercent >= 80 ? 'success' : 'warning'),
            Stat::make('Days Without LTI', $daysSinceLostTime !== null ? (string) $daysSinceLostTime : '—')->color('success'),
            Stat::make('Safety Score', $safetyScore . ' / 100')->color($safetyScore >= 80 ? 'success' : ($safetyScore >= 60 ? 'warning' : 'danger')),
        ];
    }
}
