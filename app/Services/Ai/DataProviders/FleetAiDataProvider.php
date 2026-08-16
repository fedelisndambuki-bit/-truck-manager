<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\Truck;
use App\Models\Tyre;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiPeriodParser;

/**
 * Vehicles, drivers, trips, tyres — the core Fleet/trucking module. No
 * dedicated "fleet cost per km" service exists anywhere in the codebase
 * (confirmed during the audit), so aggregates here are computed directly
 * rather than pretending to reuse something that doesn't exist.
 */
class FleetAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected AiComparisonEngine $comparisons, protected AiPeriodParser $periods)
    {
    }

    public function key(): string
    {
        return 'fleet';
    }

    public function label(): string
    {
        return 'Fleet';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return $industry === CompanyIndustry::Trucking->value || $industry === CompanyIndustry::MultiOperation->value;
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $limit = config('ai.max_records_per_provider', 40);

        $trucks = Truck::where('company_id', $companyId)->with('driver')->get(['id', 'plate_number', 'status', 'current_mileage']);
        $tyres = Tyre::where('company_id', $companyId)->get(['status', 'type']);

        $truckRoster = $trucks->take($limit)->map(fn ($t) => [
            'plate' => $t->plate_number,
            'status' => $t->status?->value,
            'driver' => $t->driver?->full_name ?? 'Unassigned',
        ])->values()->all();

        $currentPeriod = $period ?? ['from' => now()->startOfMonth(), 'to' => now(), 'label' => 'this month'];
        $previousPeriod = $this->periods->previousEquivalent($currentPeriod);

        $tripQuery = Trip::where('company_id', $companyId);
        if ($period) {
            $tripQuery->whereBetween('created_at', [$period['from'], $period['to']]);
        } else {
            $tripQuery->whereIn('status', ['In Progress', 'Scheduled']);
        }

        $activeTripRoster = $tripQuery->with('truck.driver')->latest()->take($limit)->get()
            ->map(fn ($trip) => [
                'route' => $trip->route_name ?: trim(($trip->origin ?: '').' -> '.($trip->destination ?: '')),
                'truck' => $trip->truck?->plate_number ?? 'N/A',
                'driver' => $trip->truck?->driver?->full_name ?? 'Unassigned',
                'status' => $trip->status?->value,
            ])->values()->all();

        $currentTripCount = Trip::where('company_id', $companyId)->whereBetween('created_at', [$currentPeriod['from'], $currentPeriod['to']])->count();
        $previousTripCount = Trip::where('company_id', $companyId)->whereBetween('created_at', [$previousPeriod['from'], $previousPeriod['to']])->count();

        return [
            'total_trucks' => $trucks->count(),
            'trucks_available' => $trucks->filter(fn ($t) => $t->status?->value === 'Available')->count(),
            'trucks_in_maintenance' => $trucks->where('status', \App\Enums\TruckStatus::UnderMaintenance)->pluck('plate_number')->values()->all(),
            'truck_driver_roster' => $truckRoster,
            'trip_count' => $tripQuery->count(),
            'trip_count_comparison' => $this->comparisons->compare('trip_count', $currentTripCount, $previousTripCount, 'trips', $currentPeriod['label'], $previousPeriod['label']),
            'trip_roster' => $activeTripRoster,
            'total_drivers' => Driver::where('company_id', $companyId)->count(),
            'licenses_expiring_30d' => Employee::where('company_id', $companyId)
                ->whereNotNull('license_expiry')
                ->whereBetween('license_expiry', [now(), now()->addDays(30)])
                ->pluck('full_name')->values()->all(),
            'tyres_total' => $tyres->count(),
            'tyres_available_spare' => $tyres->where('status', 'Spare')->count(),
            'tyres_installed' => $tyres->where('status', 'Active')->count(),
            'tyres_scrapped' => $tyres->where('status', 'Discarded')->count(),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_vehicle_summary',
                    'description' => 'Get fleet-wide vehicle, driver, trip and tyre aggregate stats.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_vehicle_details',
                    'description' => 'Get details for one specific truck by plate number, including assigned driver, mileage, and its most recent trip.',
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
        if ($name === 'get_vehicle_details') {
            $plate = trim((string) ($arguments['plate'] ?? ''));
            $normalized = str_replace([' ', '-'], '', strtoupper($plate));
            $truck = Truck::where('company_id', $companyId)
                ->where(function ($q) use ($plate, $normalized) {
                    $q->where('plate_number', 'LIKE', "%{$plate}%")->orWhere('plate_number', 'LIKE', "%{$normalized}%");
                })->with('driver')->first();

            if (! $truck) {
                return ['error' => 'No truck found matching that plate.'];
            }

            return [
                'plate' => $truck->plate_number,
                'status' => $truck->status?->value,
                'driver' => $truck->driver?->full_name ?? 'Unassigned',
                'mileage' => $truck->current_mileage,
                'last_trip' => Trip::where('truck_id', $truck->id)->latest()->first()?->route_name ?? 'None',
            ];
        }

        return $this->summary($companyId);
    }
}
