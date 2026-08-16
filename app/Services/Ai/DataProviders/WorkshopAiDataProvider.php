<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Models\Equipment;
use App\Models\Maintenance;
use App\Models\Truck;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiPeriodParser;
use App\Services\PredictiveMaintenanceService;
use App\Services\Quarry\EamAnalyticsService;

/**
 * Vehicle/equipment maintenance. The Maintenance model covers both fleet
 * trucks (truck_id) and quarry/construction equipment (equipment_id) — a
 * single company's jobs can reference either column, so cost-by-vehicle
 * rankings must label from whichever is set. Quarry additionally has a
 * separate MaintenanceWorkOrder/Workshop/WorkshopBay (EAM) system surfaced
 * via the quarry_equipment_* aggregates below.
 */
class WorkshopAiDataProvider implements AiDataProviderInterface
{
    public function __construct(
        protected PredictiveMaintenanceService $predictiveMaintenance,
        protected EamAnalyticsService $eam,
        protected AiComparisonEngine $comparisons,
        protected AiPeriodParser $periods,
    ) {
    }

    public function key(): string
    {
        return 'workshop';
    }

    public function label(): string
    {
        return 'Workshop / Maintenance';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return true;
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $current = $period ?? ['from' => now()->startOfMonth(), 'to' => now(), 'label' => 'this month'];
        $previous = $this->periods->previousEquivalent($current);
        $limit = config('ai.max_records_per_provider', 40);

        $currentCost = $this->fleetMaintenanceCost($companyId, $current['from'], $current['to']);
        $previousCost = $this->fleetMaintenanceCost($companyId, $previous['from'], $previous['to']);

        $byVehicle = Maintenance::where('company_id', $companyId)
            ->whereBetween('maintenance_date', [$current['from'], $current['to']])
            ->selectRaw('truck_id, equipment_id, SUM(cost) as cost, COUNT(*) as jobs')
            ->groupBy('truck_id', 'equipment_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get();

        $platesById = Truck::where('company_id', $companyId)->pluck('plate_number', 'id');
        $equipmentNamesById = Equipment::where('company_id', $companyId)->pluck('name', 'id');

        $rankedByCost = $byVehicle->map(function ($row) use ($platesById, $equipmentNamesById) {
            $label = match (true) {
                (bool) $row->truck_id => $platesById[$row->truck_id] ?? "Truck #{$row->truck_id}",
                (bool) $row->equipment_id => $equipmentNamesById[$row->equipment_id] ?? "Equipment #{$row->equipment_id}",
                default => 'Unassigned',
            };

            return [
                'truck' => $label,
                'cost' => round((float) $row->cost, 2),
                'jobs' => (int) $row->jobs,
            ];
        })->values()->all();

        $topRisks = $this->predictiveMaintenance->topRisks($companyId, $limit);

        return [
            'period' => $current['label'],
            'previous_period' => $previous['label'],
            'fleet_maintenance_jobs' => Maintenance::where('company_id', $companyId)->whereBetween('maintenance_date', [$current['from'], $current['to']])->count(),
            'fleet_maintenance_cost' => $currentCost,
            'fleet_maintenance_cost_comparison' => $this->comparisons->compare('fleet_maintenance_cost', $currentCost, $previousCost, 'KES', $current['label'], $previous['label']),
            'fleet_maintenance_cost_by_vehicle' => $rankedByCost,
            'fleet_maintenance_pending' => Maintenance::where('company_id', $companyId)->where('status', 'Pending')->count(),
            'fleet_predictive_risks' => collect($topRisks)->map(fn ($r) => [
                'plate' => $r['plate_number'] ?? null,
                'probability_percent' => $r['probability'] ?? null,
            ])->values()->all(),
            'quarry_equipment_open_work_orders' => $this->eam->openWorkOrdersCount($companyId),
            'quarry_equipment_overdue_maintenance' => $this->eam->overdueMaintenanceCount($companyId),
            'quarry_equipment_under_maintenance' => $this->eam->underMaintenanceCount($companyId),
            'quarry_equipment_maintenance_cost_today' => $this->eam->maintenanceCostToday($companyId),
            'quarry_equipment_cost_per_hour' => $this->eam->costPerHour($companyId),
            'quarry_equipment_cost_per_tonne' => $this->eam->costPerTonne($companyId),
        ];
    }

    private function fleetMaintenanceCost(int $companyId, $from, $to): float
    {
        return round((float) Maintenance::where('company_id', $companyId)
            ->whereBetween('maintenance_date', [$from, $to])
            ->sum('cost'), 2);
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_maintenance_summary',
                    'description' => 'Get maintenance job counts and cost for fleet vehicles (compared to the equivalent previous period, ranked by vehicle), plus equipment work orders/overdue maintenance/cost-per-hour/cost-per-tonne for quarry operations, for a given period.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => ['period' => ['type' => 'string', 'description' => 'e.g. today, this month, last quarter']],
                        'required' => [],
                    ],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        $period = empty($arguments['period']) ? null : $this->periods->parse((string) $arguments['period']);

        return $this->summary($companyId, $period);
    }
}
