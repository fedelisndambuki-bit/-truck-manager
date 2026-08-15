<?php

namespace App\Services;

use App\Models\FuelLog;
use App\Models\Trip;
use Illuminate\Support\Collection;

class FuelFraudService
{
    public function recentFlags(int $companyId, int $limit = 20): array
    {
        $flags = [];
        $trucks = FuelLog::where('company_id', $companyId)
            ->select('truck_id')
            ->distinct()
            ->pluck('truck_id')
            ->filter();

        foreach ($trucks as $truckId) {
            $truckFlags = $this->analyzeTruck($companyId, $truckId);
            $flags = array_merge($flags, $truckFlags);
        }

        usort($flags, fn ($a, $b) => strcmp($b['date'], $a['date']));
        return array_slice($flags, 0, $limit);
    }

    public function analyzeTruck(int $companyId, int $truckId): array
    {
        $logs = FuelLog::where('company_id', $companyId)
            ->where('truck_id', $truckId)
            ->orderBy('date')
            ->get();

        if ($logs->count() < 2) {
            return [];
        }

        $ratios = $this->kmPerLiterSeries($logs);
        $median = $this->median($ratios->pluck('kpl')->filter()->values()->all());

        $flags = [];
        for ($i = 1; $i < $logs->count(); $i++) {
            $prev = $logs[$i - 1];
            $curr = $logs[$i];
            $deltaKm = ($curr->mileage ?? 0) - ($prev->mileage ?? 0);
            $kpl = $ratios[$i]['kpl'] ?? null;

            if ($curr->mileage !== null && $prev->mileage !== null && $curr->mileage < $prev->mileage) {
                $flags[] = [
                    'type' => 'odometer_rollback',
                    'truck_id' => $truckId,
                    'date' => $curr->date?->toDateString(),
                    'details' => 'Mileage decreased between refuels',
                ];
            }

            if ($deltaKm <= 5 && ($curr->liters ?? 0) >= 10) {
                $flags[] = [
                    'type' => 'no_movement_refuel',
                    'truck_id' => $truckId,
                    'date' => $curr->date?->toDateString(),
                    'details' => 'Refuel with almost no mileage delta',
                ];
            }

            if ($kpl !== null && $median !== null && $median > 0 && $kpl < 0.7 * $median) {
                $flags[] = [
                    'type' => 'abnormal_consumption',
                    'truck_id' => $truckId,
                    'date' => $curr->date?->toDateString(),
                    'details' => 'Km/L well below truck median',
                ];
            }

            $hasTrip = Trip::where('truck_id', $truckId)
                ->whereDate('departure_time', $curr->date?->toDateString())
                ->exists();
            if (! $hasTrip) {
                $flags[] = [
                    'type' => 'refuel_without_trip',
                    'truck_id' => $truckId,
                    'date' => $curr->date?->toDateString(),
                    'details' => 'Refuel with no trip recorded same day',
                ];
            }
        }

        return $flags;
    }

    private function kmPerLiterSeries(Collection $logs): Collection
    {
        $series = collect([]);
        for ($i = 1; $i < $logs->count(); $i++) {
            $prev = $logs[$i - 1];
            $curr = $logs[$i];
            $deltaKm = ($curr->mileage ?? 0) - ($prev->mileage ?? 0);
            $liters = (float) ($curr->liters ?? 0);
            $kpl = $liters > 0 ? $deltaKm / $liters : null;
            $series->push([
                'date' => $curr->date?->toDateString(),
                'kpl' => $kpl,
            ]);
        }
        return $series;
    }

    private function median(array $values): ?float
    {
        if (empty($values)) {
            return null;
        }
        sort($values);
        $count = count($values);
        $mid = (int) floor(($count - 1) / 2);
        if ($count % 2) {
            return (float) $values[$mid];
        }
        return (float) (($values[$mid] + $values[$mid + 1]) / 2);
    }
}

