<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Contract;
use App\Models\EquipmentAllocation;
use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MaterialDelivery;
use App\Models\ProgressUpdate;
use App\Models\Site;

class DailySiteDiaryAutoPopulateService
{
    public function __construct(private readonly WeatherService $weatherService)
    {
    }

    public function weather(Site $site): ?array
    {
        if (! $site->gps_lat || ! $site->gps_lng) {
            return null;
        }

        return $this->weatherService->fetchCurrent((float) $site->gps_lat, (float) $site->gps_lng);
    }

    public function workforceSummary(int $siteId, string $date): array
    {
        $attendances = Attendance::query()->where('site_id', $siteId)->where('date', $date)->get();

        return [
            'workers_on_site' => $attendances->where('status', 'Present')->count(),
            'workers_absent' => $attendances->where('status', 'Absent')->count(),
            'workers_late' => $attendances->where('late', true)->count(),
            'workers_overtime' => $attendances->filter(function (Attendance $attendance) {
                $shiftEnd = $attendance->shift?->end_time;

                return $shiftEnd && $attendance->check_out_at && $attendance->check_out_at->format('H:i:s') > $shiftEnd;
            })->count(),
        ];
    }

    public function equipmentSummary(int $siteId, string $date): array
    {
        $allocations = EquipmentAllocation::query()
            ->where('site_id', $siteId)
            ->whereDate('allocated_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('allocated_to')->orWhereDate('allocated_to', '>=', $date);
            })
            ->get();

        $active = $allocations->where('operating_hours_today', '>', 0)->count();
        $idle = $allocations->count() - $active;

        $utilizationSamples = $allocations->filter(fn (EquipmentAllocation $a) => $a->expected_daily_hours > 0)
            ->map(fn (EquipmentAllocation $a) => min(100, ((float) $a->operating_hours_today / (float) $a->expected_daily_hours) * 100));

        return [
            'equipment_active_count' => $active,
            'equipment_idle_count' => max(0, $idle),
            'equipment_breakdown_count' => 0,
            'equipment_utilization_pct' => $utilizationSamples->isNotEmpty() ? round($utilizationSamples->avg(), 2) : null,
        ];
    }

    public function materialSummary(int $siteId, string $date): array
    {
        $deliveries = MaterialDelivery::query()->where('site_id', $siteId)->where('delivered_at', $date)->count();

        $materialIds = MaterialConsumption::query()
            ->where('site_id', $siteId)
            ->where('date', $date)
            ->distinct()
            ->pluck('material_id');

        $shortages = Material::query()
            ->whereIn('id', $materialIds)
            ->whereColumn('quantity_on_hand', '<', 'reorder_level')
            ->count();

        return [
            'material_deliveries_count' => $deliveries,
            'material_shortages_count' => $shortages,
        ];
    }

    public function subcontractorSummary(int $siteId, string $date): array
    {
        $contracts = Contract::query()
            ->where('site_id', $siteId)
            ->where('status', 'Active')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('completion_date')->orWhereDate('completion_date', '>=', $date);
            })
            ->get();

        $progressNotes = ProgressUpdate::query()
            ->whereIn('contract_id', $contracts->pluck('id'))
            ->where('date', $date)
            ->pluck('comments')
            ->filter()
            ->implode("\n");

        return [
            'subcontractors_on_site_count' => $contracts->pluck('subcontractor_id')->unique()->count(),
            'subcontractor_progress_notes' => $progressNotes ?: null,
        ];
    }
}
