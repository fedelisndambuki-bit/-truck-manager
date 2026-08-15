<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\Trip;
use App\Models\Truck;
use Illuminate\Support\Carbon;

class PredictiveMaintenanceService
{
    public function topRisks(int $companyId, int $limit = 5): array
    {
        $trucks = Truck::where('company_id', $companyId)->get();
        $risks = [];
        foreach ($trucks as $truck) {
            $r = $this->assessTruck($companyId, $truck);
            if ($r) {
                $risks[] = $r;
            }
        }
        usort($risks, fn ($a, $b) => $b['probability'] <=> $a['probability']);
        return array_slice($risks, 0, $limit);
    }

    public function assessTruck(int $companyId, Truck $truck): ?array
    {
        $last = Maintenance::where('company_id', $companyId)
            ->where('truck_id', $truck->id)
            ->orderByDesc('maintenance_date')
            ->first();

        $nextDate = $last?->next_maintenance_date ? Carbon::parse($last->next_maintenance_date) : null;
        $avgDailyKm = $this->avgDailyKm($companyId, $truck->id);
        $currentMileage = (int) ($truck->current_mileage ?? 0);

        $rulDays = null;
        if ($nextDate) {
            $rulDays = now()->diffInDays($nextDate, false);
        }

        $prob = 0.3;
        if ($rulDays !== null) {
            if ($rulDays < 0) {
                $prob = 0.9;
            } elseif ($rulDays <= 7) {
                $prob = 0.72;
            } elseif ($rulDays <= 14) {
                $prob = 0.6;
            } else {
                $prob = 0.35;
            }
        } else {
            if ($avgDailyKm !== null && $avgDailyKm > 0 && $last?->mileage_at_service && $truck->current_mileage) {
                $delta = max(0, (int) $truck->current_mileage - (int) $last->mileage_at_service);
                $estDays = $delta > 0 ? round($delta / max(1, $avgDailyKm)) : null;
                if ($estDays !== null && $estDays >= 60) {
                    $prob = 0.35;
                } else {
                    $prob = 0.6;
                }
            } else {
                $prob = 0.5;
            }
        }

        $windowStart = null;
        $windowEnd = null;
        if ($rulDays !== null) {
            $windowStart = now()->copy()->addDays(max(0, $rulDays - 3))->toDateString();
            $windowEnd = now()->copy()->addDays(max(0, $rulDays + 3))->toDateString();
        } else {
            $windowStart = now()->toDateString();
            $windowEnd = now()->copy()->addDays(7)->toDateString();
        }

        return [
            'plate_number' => $truck->plate_number ?? ('Truck #'.$truck->id),
            'probability' => round($prob * 100),
            'rul_days' => $rulDays,
            'service_window' => [$windowStart, $windowEnd],
            'avg_daily_km' => $avgDailyKm,
            'current_mileage' => $currentMileage,
        ];
    }

    private function avgDailyKm(int $companyId, int $truckId): ?float
    {
        $from = now()->subDays(30);
        $total = Trip::where('company_id', $companyId)
            ->where('truck_id', $truckId)
            ->where('departure_time', '>=', $from)
            ->sum('distance_km');
        return $total > 0 ? round($total / 30, 2) : null;
    }
}

