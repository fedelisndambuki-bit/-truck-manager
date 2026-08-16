<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Models\Equipment;
use App\Models\FuelLog;
use App\Models\Trip;
use App\Models\Truck;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiPeriodParser;
use App\Services\FuelFraudService;

class FuelAiDataProvider implements AiDataProviderInterface
{
    public function __construct(
        protected FuelFraudService $fuelFraud,
        protected AiComparisonEngine $comparisons,
        protected AiPeriodParser $periods,
    ) {
    }

    public function key(): string
    {
        return 'fuel';
    }

    public function label(): string
    {
        return 'Fuel';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return in_array($industry, [CompanyIndustry::Trucking->value, CompanyIndustry::Quarry->value, CompanyIndustry::MultiOperation->value], true);
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $current = $period ?? ['from' => now()->startOfMonth(), 'to' => now(), 'label' => 'this month'];
        $previous = $this->periods->previousEquivalent($current);

        [$litres, $cost] = $this->totals($companyId, $current['from'], $current['to']);
        [$prevLitres, $prevCost] = $this->totals($companyId, $previous['from'], $previous['to']);

        $byVehicle = FuelLog::where('company_id', $companyId)->whereBetween('date', [$current['from'], $current['to']])
            ->selectRaw('truck_id, equipment_id, SUM(quantity_litres) as litres, SUM(total_cost) as cost')
            ->groupBy('truck_id', 'equipment_id')
            ->orderByDesc('litres')
            ->limit(config('ai.max_records_per_provider', 40))
            ->get();

        $platesById = Truck::where('company_id', $companyId)->pluck('plate_number', 'id');
        $equipmentNamesById = Equipment::where('company_id', $companyId)->pluck('name', 'id');

        // FuelLog rows belong to EITHER a truck OR a piece of equipment (Quarry/
        // Construction fuel is logged against equipment_id, not truck_id) — group by
        // both columns and label from whichever is set, so equipment-based fuel isn't
        // silently collapsed into a single mislabeled "Truck #" bucket.
        $topConsumers = $byVehicle->map(function ($row) use ($platesById, $equipmentNamesById) {
            $label = match (true) {
                (bool) $row->truck_id => $platesById[$row->truck_id] ?? "Truck #{$row->truck_id}",
                (bool) $row->equipment_id => $equipmentNamesById[$row->equipment_id] ?? "Equipment #{$row->equipment_id}",
                default => 'Unassigned',
            };

            return [
                'truck' => $label,
                'litres' => round((float) $row->litres, 2),
                'cost' => round((float) $row->cost, 2),
            ];
        })->values()->all();

        $fraudFlags = $this->fuelFraud->recentFlags($companyId, 20);
        $flaggedTrucks = collect($fraudFlags)->pluck('truck_id')->unique()
            ->map(fn ($id) => $platesById[$id] ?? "Truck #{$id}")->values()->all();

        $distanceKm = (float) Trip::where('company_id', $companyId)
            ->whereBetween('created_at', [$current['from'], $current['to']])
            ->sum('distance_km');

        return [
            'period' => $current['label'],
            'previous_period' => $previous['label'],
            'total_litres' => round($litres, 2),
            'total_cost' => round($cost, 2),
            'total_cost_comparison' => $this->comparisons->compare('fuel_cost', $cost, $prevCost, 'KES', $current['label'], $previous['label']),
            'total_litres_comparison' => $this->comparisons->compare('fuel_litres', $litres, $prevLitres, 'litres', $current['label'], $previous['label']),
            'fuel_cost_per_km' => $distanceKm > 0 ? round($cost / $distanceKm, 2) : null,
            'top_consuming_vehicles' => $topConsumers,
            'fraud_flagged_vehicles' => $flaggedTrucks,
        ];
    }

    /**
     * @return array{0: float, 1: float} [litres, cost]
     */
    private function totals(int $companyId, $from, $to): array
    {
        $row = FuelLog::where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('SUM(quantity_litres) as litres, SUM(total_cost) as cost')
            ->first();

        return [(float) ($row->litres ?? 0), (float) ($row->cost ?? 0)];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_fuel_summary',
                    'description' => 'Get total fuel litres/cost for a period (compared to the equivalent previous period), fuel cost per km, ranked by consuming vehicle, plus any fraud-flagged vehicles.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => ['period' => ['type' => 'string', 'description' => 'e.g. today, this month, last month, this quarter']],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_vehicle_fuel_usage',
                    'description' => 'Drill down into one specific vehicle\'s fuel logs — litres, cost, and per-refuel efficiency anomalies.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => ['plate' => ['type' => 'string', 'description' => 'The truck plate number, e.g. KDA 001A']],
                        'required' => ['plate'],
                    ],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        if ($name === 'get_vehicle_fuel_usage') {
            $plate = trim((string) ($arguments['plate'] ?? ''));
            $truck = Truck::where('company_id', $companyId)
                ->where('plate_number', 'LIKE', '%'.$plate.'%')
                ->first();

            if (! $truck) {
                return ['error' => 'No truck found matching that plate.'];
            }

            $logs = FuelLog::where('company_id', $companyId)->where('truck_id', $truck->id)
                ->orderByDesc('date')->limit(config('ai.max_records_per_provider', 40))->get(['date', 'quantity_litres', 'total_cost']);

            return [
                'plate' => $truck->plate_number,
                'total_litres' => round((float) $logs->sum('quantity_litres'), 2),
                'total_cost' => round((float) $logs->sum('total_cost'), 2),
                'flags' => $this->fuelFraud->analyzeTruck($companyId, $truck->id),
            ];
        }

        return $this->summary($companyId, $this->argumentPeriod($arguments));
    }

    private function argumentPeriod(array $arguments): ?array
    {
        if (empty($arguments['period'])) {
            return null;
        }

        return $this->periods->parse((string) $arguments['period']);
    }
}
