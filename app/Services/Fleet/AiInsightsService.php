<?php

namespace App\Services\Fleet;

use App\Models\AiInsight;
use App\Models\AiUsageLog;
use App\Models\Employee;
use App\Models\Maintenance;
use App\Models\Trip;
use App\Models\Truck;
use App\Services\DriverRiskService;
use App\Services\AiProviderService;
use App\Services\PredictiveMaintenanceService;
use Illuminate\Support\Facades\Log;

/**
 * Fleet-domain AI insights — same shape as
 * App\Services\Construction\AiInsightsService (deterministic detectors, real
 * data, an optional AI-generated narrative on top with a templated fallback). The
 * `ai_insights.project_id` column is a real FK to `projects`, so it is never
 * set here — truck/driver identifiers live inside `facts` instead.
 */
class AiInsightsService
{
    public function __construct(
        protected PredictiveMaintenanceService $predictiveMaintenance,
        protected DriverRiskService $driverRisk,
        protected AiProviderService $aiProvider,
    ) {
    }

    public function generate(?int $companyId): \Illuminate\Support\Collection
    {
        $insights = collect([
            ...$this->predictiveMaintenanceRisks($companyId),
            ...$this->driverRiskAlerts($companyId),
            ...$this->expiringLicenses($companyId),
            ...$this->overdueMaintenance($companyId),
            ...$this->decliningOnTimePerformance($companyId),
            ...$this->underutilizedTrucks($companyId),
            ...$this->highPerformingDrivers($companyId),
        ]);

        return $insights->map(function (array $insight) use ($companyId) {
            $insight['narrative'] = $this->narrate($insight, $companyId);

            return AiInsight::create([
                'company_id' => $companyId,
                'type' => $insight['type'],
                'project_id' => null,
                'severity' => $insight['severity'],
                'title' => $insight['title'],
                'narrative' => $insight['narrative'],
                'facts' => $insight['facts'],
                'generated_at' => now(),
            ]);
        });
    }

    // ── Detectors ────────────────────────────────────────────────────────────

    protected function predictiveMaintenanceRisks(?int $companyId): array
    {
        if (! $companyId) {
            return [];
        }

        $results = [];

        foreach ($this->predictiveMaintenance->topRisks($companyId, 10) as $risk) {
            if ($risk['probability'] < 70) {
                continue;
            }

            $results[] = [
                'type' => 'predictive_maintenance_risk',
                'severity' => $risk['probability'] >= 90 ? 'Critical' : 'High',
                'title' => "{$risk['plate_number']} has a {$risk['probability']}% breakdown risk",
                'facts' => [
                    'plate_number' => $risk['plate_number'],
                    'probability_percent' => $risk['probability'],
                    'remaining_useful_life_days' => $risk['rul_days'],
                    'current_mileage' => $risk['current_mileage'],
                ],
            ];
        }

        return $results;
    }

    protected function driverRiskAlerts(?int $companyId): array
    {
        if (! $companyId) {
            return [];
        }

        $results = [];

        foreach ($this->driverRisk->topRisks($companyId, 10) as $risk) {
            if ($risk['risk_score'] < 40) {
                continue;
            }

            $results[] = [
                'type' => 'driver_risk',
                'severity' => $risk['risk_score'] >= 70 ? 'High' : 'Medium',
                'title' => "{$risk['driver_name']} is trending high-risk",
                'facts' => [
                    'driver_id' => $risk['driver_id'],
                    'driver_name' => $risk['driver_name'],
                    'risk_score' => $risk['risk_score'],
                    'delayed_trips_30d' => $risk['delays_30d'],
                    'fuel_anomalies_30d' => $risk['fuel_anomalies_30d'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Same 30-day expiring-soon / already-expired thresholds as the existing
     * DriverLicenseAlert widget, but explicitly company-scoped (that widget
     * currently isn't).
     */
    protected function expiringLicenses(?int $companyId): array
    {
        $results = [];

        $expired = Employee::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->whereNotNull('license_expiry')
            ->where('license_expiry', '<', now())
            ->get();

        foreach ($expired as $employee) {
            $results[] = [
                'type' => 'license_expired',
                'severity' => 'High',
                'title' => "{$employee->full_name}'s driving license has expired",
                'facts' => ['employee_id' => $employee->id, 'employee_name' => $employee->full_name, 'license_expiry' => optional($employee->license_expiry)->toDateString()],
            ];
        }

        $expiringSoon = Employee::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->whereBetween('license_expiry', [now(), now()->addDays(30)])
            ->get();

        foreach ($expiringSoon as $employee) {
            $results[] = [
                'type' => 'license_expiring',
                'severity' => 'Medium',
                'title' => "{$employee->full_name}'s driving license expires soon",
                'facts' => ['employee_id' => $employee->id, 'employee_name' => $employee->full_name, 'license_expiry' => optional($employee->license_expiry)->toDateString()],
            ];
        }

        return $results;
    }

    protected function overdueMaintenance(?int $companyId): array
    {
        $results = [];

        $overdue = Maintenance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('next_maintenance_date')
            ->where('next_maintenance_date', '<', now())
            ->with('truck:id,plate_number')
            ->latest('next_maintenance_date')
            ->get()
            ->unique('truck_id');

        foreach ($overdue as $maintenance) {
            $daysOverdue = (int) now()->diffInDays($maintenance->next_maintenance_date);

            $results[] = [
                'type' => 'overdue_maintenance',
                'severity' => $daysOverdue > 30 ? 'High' : 'Medium',
                'title' => ($maintenance->truck?->plate_number ?? "Truck #{$maintenance->truck_id}") . ' is overdue for scheduled maintenance',
                'facts' => ['truck_id' => $maintenance->truck_id, 'days_overdue' => $daysOverdue, 'next_maintenance_date' => optional($maintenance->next_maintenance_date)->toDateString()],
            ];
        }

        return $results;
    }

    /**
     * Same "compare recent window to prior window" shape as Construction's
     * decliningProductivitySites() — here, on-time trip completion rate.
     */
    protected function decliningOnTimePerformance(?int $companyId): array
    {
        $recent = $this->onTimeRate($companyId, 7);
        $prior = $this->onTimeRate($companyId, 14, 7);

        if ($prior === null || $recent === null || $recent >= $prior - 10) {
            return [];
        }

        return [[
            'type' => 'declining_on_time_performance',
            'severity' => ($prior - $recent) > 25 ? 'High' : 'Medium',
            'title' => 'On-time trip completion has dropped ' . number_format($prior - $recent, 1) . ' points',
            'facts' => ['recent_on_time_rate' => $recent, 'prior_on_time_rate' => $prior],
        ]];
    }

    protected function onTimeRate(?int $companyId, int $daysBack, int $offsetDays = 0): ?float
    {
        $to = now()->subDays($offsetDays);
        $from = $to->copy()->subDays($daysBack);

        $trips = Trip::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('actual_arrival')
            ->whereNotNull('expected_arrival')
            ->whereBetween('departure_time', [$from, $to])
            ->get(['actual_arrival', 'expected_arrival']);

        if ($trips->isEmpty()) {
            return null;
        }

        $onTime = $trips->filter(fn (Trip $t) => $t->actual_arrival <= $t->expected_arrival)->count();

        return round(($onTime / $trips->count()) * 100, 1);
    }

    protected function underutilizedTrucks(?int $companyId): array
    {
        $results = [];

        $trucks = Truck::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', 'Under Maintenance')
            ->get();

        foreach ($trucks as $truck) {
            $tripsLast30Days = Trip::query()->where('truck_id', $truck->id)->where('departure_time', '>=', now()->subDays(30))->count();

            if ($tripsLast30Days > 1) {
                continue;
            }

            $results[] = [
                'type' => 'underutilized_truck',
                'severity' => 'Low',
                'title' => ($truck->plate_number ?? "Truck #{$truck->id}") . ' has had little to no activity this month',
                'facts' => ['truck_id' => $truck->id, 'plate_number' => $truck->plate_number, 'trips_last_30_days' => $tripsLast30Days],
            ];
        }

        return $results;
    }

    protected function highPerformingDrivers(?int $companyId): array
    {
        $results = [];

        $employees = Employee::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->get();

        foreach ($employees as $employee) {
            $trips = Trip::query()
                ->where('employee_id', $employee->id)
                ->where('departure_time', '>=', now()->subDays(30))
                ->whereNotNull('actual_arrival')
                ->whereNotNull('expected_arrival')
                ->get(['actual_arrival', 'expected_arrival']);

            if ($trips->count() < 5) {
                continue;
            }

            $onTime = $trips->filter(fn (Trip $t) => $t->actual_arrival <= $t->expected_arrival)->count();
            $rate = round(($onTime / $trips->count()) * 100, 1);

            if ($rate < 95) {
                continue;
            }

            $results[] = [
                'type' => 'high_performing_driver',
                'severity' => 'Low',
                'title' => "{$employee->full_name} is consistently on-time",
                'facts' => ['employee_id' => $employee->id, 'employee_name' => $employee->full_name, 'on_time_rate_30d' => $rate, 'trips_30d' => $trips->count()],
            ];
        }

        return $results;
    }

    protected function narrate(array $insight, ?int $companyId = null): string
    {
        $fallback = $insight['title'] . '. ' . collect($insight['facts'])
            ->map(fn ($v, $k) => str($k)->replace('_', ' ') . ': ' . (is_numeric($v) ? round($v, 2) : $v))
            ->implode(', ') . '.';

        $start = microtime(true);

        try {
            $prompt = "You are a fleet operations analyst. In 1-2 concise sentences, explain this finding to a fleet manager and suggest one concrete action. Finding: {$insight['title']}. Supporting data: " . json_encode($insight['facts']);

            $result = $this->aiProvider->generate($prompt, 150);

            if ($companyId) {
                AiUsageLog::create([
                    'company_id' => $companyId,
                    'tokens_used' => $result['tokens'],
                    'response_ms' => (int) round((microtime(true) - $start) * 1000),
                ]);
            }

            return $result['text'];
        } catch (\Throwable $e) {
            Log::info('Fleet AiInsightsService: AI narrative unavailable, using fallback.', ['error' => $e->getMessage()]);
        }

        return $fallback;
    }
}
