<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FuelLog;
use App\Models\Trip;

class DriverRiskService
{
    public function topRisks(int $companyId, int $limit = 5): array
    {
        $drivers = Employee::where('company_id', $companyId)->get();
        $scored = [];
        foreach ($drivers as $d) {
            $s = $this->scoreDriver($companyId, $d->id);
            if ($s) {
                $scored[] = $s;
            }
        }
        usort($scored, fn ($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        return array_slice($scored, 0, $limit);
    }

    public function scoreDriver(int $companyId, int $driverId): ?array
    {
        $name = Employee::find($driverId)?->full_name ?? ('Driver #'.$driverId);
        $delays = $this->delayedTripsCount($companyId, $driverId);
        $fuelAnoms = $this->fuelAnomalyCount($companyId, $driverId);
        $score = min(100, ($delays * 10) + ($fuelAnoms * 15));
        if ($delays === 0 && $fuelAnoms === 0) {
            return null;
        }
        return [
            'driver_id' => $driverId,
            'driver_name' => $name,
            'delays_30d' => $delays,
            'fuel_anomalies_30d' => $fuelAnoms,
            'risk_score' => $score,
        ];
    }

    private function delayedTripsCount(int $companyId, int $driverId): int
    {
        $start = now()->subDays(30);
        return Trip::where('company_id', $companyId)
            ->where('employee_id', $driverId)
            ->whereNotNull('expected_arrival')
            ->where('departure_time', '>=', $start)
            ->where(function ($q) {
                $q->whereNull('actual_arrival')
                  ->orWhereColumn('actual_arrival', '>', 'expected_arrival');
            })
            ->count();
    }

    private function fuelAnomalyCount(int $companyId, int $driverId): int
    {
        $start = now()->subDays(30)->toDateString();
        $logs = FuelLog::where('company_id', $companyId)
            ->where('employee_id', $driverId)
            ->where('date', '>=', $start)
            ->orderBy('date')
            ->get();
        if ($logs->count() < 2) {
            return 0;
        }
        $anoms = 0;
        for ($i = 1; $i < $logs->count(); $i++) {
            $prev = $logs[$i - 1];
            $curr = $logs[$i];
            $deltaKm = ($curr->mileage ?? 0) - ($prev->mileage ?? 0);
            if ($deltaKm <= 5 && ($curr->liters ?? 0) >= 10) {
                $anoms++;
            }
            if ($curr->mileage !== null && $prev->mileage !== null && $curr->mileage < $prev->mileage) {
                $anoms++;
            }
        }
        return $anoms;
    }
}

