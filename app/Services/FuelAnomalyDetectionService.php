<?php

namespace App\Services;

use App\Models\EamSetting;
use App\Models\FuelLog;
use Illuminate\Support\Collection;

/**
 * Scans recent equipment fuel-ups for consumption that's abnormally high
 * against that equipment's own rolling average — a simple percentage-over-
 * baseline check, not a statistical model. Returns the anomalies found;
 * SendEamAlerts (Phase 6) turns them into EquipmentAlert rows + notifications.
 */
class FuelAnomalyDetectionService
{
    public function __construct(
        protected float $varianceThresholdPercent = 50.0,
        protected int $baselineDays = 30,
    ) {
    }

    public function detect(?int $companyId, int $lookbackDays = 1): Collection
    {
        $threshold = $companyId
            ? (float) EamSetting::getSetting($companyId, 'fuel_theft_variance_percent', $this->varianceThresholdPercent)
            : $this->varianceThresholdPercent;

        $recentLogs = FuelLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('equipment_id')
            ->where('date', '>=', now()->subDays($lookbackDays))
            ->with('equipment')
            ->get();

        return $recentLogs->map(function (FuelLog $log) use ($companyId, $threshold) {
            $baselineAvg = FuelLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('equipment_id', $log->equipment_id)
                ->where('id', '!=', $log->id)
                ->where('date', '>=', now()->subDays($this->baselineDays))
                ->avg('quantity_litres');

            if (! $baselineAvg || $baselineAvg <= 0) {
                return null;
            }

            $variance = (((float) $log->quantity_litres - $baselineAvg) / $baselineAvg) * 100;

            if ($variance < $threshold) {
                return null;
            }

            return [
                'fuel_log_id' => $log->id,
                'equipment_id' => $log->equipment_id,
                'equipment_name' => $log->equipment?->name,
                'quantity_litres' => (float) $log->quantity_litres,
                'baseline_average_litres' => round($baselineAvg, 1),
                'variance_percent' => round($variance, 1),
                'date' => $log->date,
            ];
        })->filter()->values();
    }
}
