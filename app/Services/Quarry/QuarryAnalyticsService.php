<?php

namespace App\Services\Quarry;

use App\Models\Client;
use App\Models\CrushingPlant;
use App\Models\CrushingPlantLog;
use App\Models\Equipment;
use App\Models\ExtractionSite;
use App\Models\FuelLog;
use App\Models\FuelStock;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\ProductionDowntime;
use App\Models\ProductionLine;
use App\Models\DispatchOrder;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\ProductionRun;
use App\Models\SafetyIncident;
use App\Models\Stockpile;
use App\Models\WeighbridgeTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Orchestration layer for the Quarry Executive/Operations/Commercial/
 * Equipment/Safety/Financial dashboards — mirrors
 * Services\Construction\BusinessIntelligenceService's shape (explicit
 * companyId param + query-per-metric) so Super Admin cross-company filtering
 * keeps working the same way it does everywhere else in the app.
 */
class QuarryAnalyticsService
{
    protected function sites(?int $companyId)
    {
        return ExtractionSite::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Production ───────────────────────────────────────────────────────────

    public function productionToday(?int $companyId, ?int $extractionSiteId = null): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($extractionSiteId, fn ($q) => $q->where('extraction_site_id', $extractionSiteId))
            ->whereDate('date', today())
            ->sum('quantity_tonnes');
    }

    public function productionWeek(?int $companyId, ?int $extractionSiteId = null): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($extractionSiteId, fn ($q) => $q->where('extraction_site_id', $extractionSiteId))
            ->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('quantity_tonnes');
    }

    public function productionMonth(?int $companyId, ?int $extractionSiteId = null): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($extractionSiteId, fn ($q) => $q->where('extraction_site_id', $extractionSiteId))
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('quantity_tonnes');
    }

    public function targetAchievementPercent(?int $companyId, ?int $extractionSiteId = null): float
    {
        $target = (float) $this->sites($companyId)
            ->when($extractionSiteId, fn ($q) => $q->where('id', $extractionSiteId))
            ->sum('daily_target_tonnes');

        if ($target <= 0) {
            return 0.0;
        }

        return round(($this->productionToday($companyId, $extractionSiteId) / $target) * 100, 1);
    }

    // ── Crushing Plant ───────────────────────────────────────────────────────

    protected function crushingPlants(?int $companyId)
    {
        return CrushingPlant::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function crusherUtilizationPercent(?int $companyId): float
    {
        $capacity = (float) $this->crushingPlants($companyId)->sum('capacity_tph') * 24;

        if ($capacity <= 0) {
            return 0.0;
        }

        // CrushingPlantLog has no live write path in this app (seeder-only) —
        // ProductionRun is the model actually credited by real approvals, so
        // it must be counted too or this always understates utilization.
        $output = (float) CrushingPlantLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('output_tonnes');

        $output += (float) ProductionRun::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Approved', 'Completed'])
            ->whereDate('start_time', today())
            ->sum('output_quantity');

        return round(min(100, ($output / $capacity) * 100), 1);
    }

    public function plantAvailabilityPercent(?int $companyId): float
    {
        $total = $this->crushingPlants($companyId)->count();

        if ($total <= 0) {
            return 0.0;
        }

        $available = $this->crushingPlants($companyId)->whereNotIn('status', ['Breakdown', 'Maintenance'])->count();

        return round(($available / $total) * 100, 1);
    }

    // ── Equipment / Maintenance / Fuel ──────────────────────────────────────

    public function equipmentUtilizationPercent(?int $companyId): float
    {
        $total = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 0.0;
        }

        $inUse = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'In Use')->count();

        return round(($inUse / $total) * 100, 1);
    }

    public function maintenanceDueCount(?int $companyId): int
    {
        return Maintenance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Pending')
            ->whereNotNull('next_maintenance_date')
            ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
            ->count();
    }

    /**
     * Equipment "Out of Service" + Crushing Plants "Breakdown" — the closest
     * real proxy for a breakdown count; there is no dedicated breakdown model
     * in this codebase to defer to.
     */
    public function breakdownsCount(?int $companyId): int
    {
        $equipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Out of Service')->count();
        $plants = $this->crushingPlants($companyId)->where('status', 'Breakdown')->count();

        return $equipment + $plants;
    }

    public function fuelConsumedToday(?int $companyId): float
    {
        return (float) FuelLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('quantity_litres');
    }

    public function fuelStockLitres(?int $companyId): float
    {
        return (float) FuelStock::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('current_level_litres');
    }

    // ── Stockpiles ───────────────────────────────────────────────────────────

    protected function stockpiles(?int $companyId)
    {
        return Stockpile::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function stockAvailableTonnes(?int $companyId): float
    {
        return (float) $this->stockpiles($companyId)->sum('quantity_available');
    }

    public function stockValue(?int $companyId): float
    {
        return round((float) $this->stockpiles($companyId)->get()->sum(fn (Stockpile $s) => $s->value()), 2);
    }

    public function lowStockpileCount(?int $companyId): int
    {
        return $this->stockpiles($companyId)->get()->filter(fn (Stockpile $s) => $s->isLow())->count();
    }

    // ── Dispatch / Weighbridge ───────────────────────────────────────────────

    // DispatchOrder statuses that mean "resolved, no longer waiting on the
    // weighbridge/dispatch queue" — the closest equivalent of
    // WeighbridgeTicket's 2-outcome Weighed/Rejected terminal states.
    protected const DISPATCH_QUEUE_RESOLVED_STATUSES = ['Approved', 'Rejected', 'Exited', 'En Route', 'Delivered', 'Cancelled'];

    protected function tickets(?int $companyId)
    {
        return WeighbridgeTicket::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    protected function dispatchOrders(?int $companyId)
    {
        return DispatchOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    /**
     * WeighbridgeTicket (IoT hardware feed) and DispatchOrder (Smart
     * Weighbridge / Dispatch Management workflow) are two unreconciled live
     * write paths onto the same real-world truck dispatches — counting only
     * one silently misses whichever route a given truck went through.
     */
    public function dispatchesToday(?int $companyId): int
    {
        return $this->tickets($companyId)
            ->where('direction', 'Outbound')
            ->whereDate('created_at', today())
            ->count()
            + $this->dispatchOrders($companyId)->whereDate('created_at', today())->count();
    }

    public function ordersPending(?int $companyId): int
    {
        return $this->tickets($companyId)->where('status', 'Pending')->count()
            + $this->dispatchOrders($companyId)->whereNotIn('status', self::DISPATCH_QUEUE_RESOLVED_STATUSES)->count();
    }

    public function ordersCompleted(?int $companyId): int
    {
        return $this->tickets($companyId)->where('status', 'Weighed')->whereDate('created_at', today())->count()
            + $this->dispatchOrders($companyId)->where('status', 'Approved')->whereDate('gross_weighed_at', today())->count();
    }

    public function avgTruckTurnaroundMinutes(?int $companyId): float
    {
        $tickets = $this->tickets($companyId)
            ->whereDate('created_at', today())
            ->whereNotNull('gate_in_at')
            ->whereNotNull('gate_out_at')
            ->get();

        $orders = $this->dispatchOrders($companyId)
            ->whereDate('created_at', today())
            ->whereNotNull('gate_in_at')
            ->whereNotNull('exit_at')
            ->get();

        $minutes = $tickets->map(fn (WeighbridgeTicket $t) => $t->turnaroundMinutes())
            ->concat($orders->map(fn (DispatchOrder $o) => $o->turnaroundMinutes()))
            ->filter(fn ($m) => $m !== null);

        if ($minutes->isEmpty()) {
            return 0.0;
        }

        return round($minutes->avg(), 1);
    }

    // ── Commercial / Finance ─────────────────────────────────────────────────

    public function revenueToday(?int $companyId): float
    {
        return (float) Payment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Paid')
            ->whereDate('payment_date', today())
            ->sum('amount');
    }

    public function revenueMonth(?int $companyId): float
    {
        return (float) Payment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Paid')
            ->whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');
    }

    public function topCustomers(?int $companyId, int $limit = 5)
    {
        return Client::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withSum(['payments' => fn ($q) => $q->where('status', 'Paid')], 'amount')
            ->orderByDesc('payments_sum_amount')
            ->limit($limit)
            ->get();
    }

    // ── Safety ───────────────────────────────────────────────────────────────

    public function openSafetyIncidents(?int $companyId): int
    {
        return SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('extraction_site_id')
            ->whereIn('status', ['Reported', 'Under Investigation', 'Corrective Action'])
            ->count();
    }

    public function daysWithoutLostTimeInjury(?int $companyId): ?int
    {
        $last = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('extraction_site_id')
            ->where('injury_classification', 'Lost Time Injury')
            ->orderByDesc('date')
            ->first();

        return $last ? (int) Carbon::parse($last->date)->diffInDays(today()) : null;
    }

    // ── Live activity feed ───────────────────────────────────────────────────

    public function recentActivity(?int $companyId, int $limit = 15): array
    {
        $events = collect();

        ProductionLog::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('extractionSite')->latest('created_at')->limit($limit)->get()
            ->each(fn (ProductionLog $log) => $events->push([
                'type' => 'production',
                'icon' => 'heroicon-o-cube',
                'message' => "{$log->quantity_tonnes}t {$log->material_type} logged at {$log->extractionSite?->name}",
                'time' => $log->created_at,
            ]));

        WeighbridgeTicket::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('customer')->latest('created_at')->limit($limit)->get()
            ->each(fn (WeighbridgeTicket $t) => $events->push([
                'type' => 'weighbridge',
                'icon' => 'heroicon-o-truck',
                'message' => "Ticket {$t->ticket_number} ({$t->direction}) — {$t->vehicle_registration}" . ($t->customer ? " for {$t->customer->company_name}" : ''),
                'time' => $t->created_at,
            ]));

        DispatchOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('customer')->latest('created_at')->limit($limit)->get()
            ->each(fn (DispatchOrder $o) => $events->push([
                'type' => 'dispatch',
                'icon' => 'heroicon-o-truck',
                'message' => "Dispatch {$o->dispatch_number} ({$o->status}) — {$o->vehicle_registration}" . ($o->customer ? " for {$o->customer->company_name}" : ''),
                'time' => $o->created_at,
            ]));

        Maintenance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('equipment')->latest('created_at')->limit($limit)->get()
            ->each(fn (Maintenance $m) => $events->push([
                'type' => 'maintenance',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'message' => "{$m->maintenance_type} logged for {$m->equipment?->name}",
                'time' => $m->created_at,
            ]));

        SafetyIncident::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('extraction_site_id')->latest('created_at')->limit($limit)->get()
            ->each(fn (SafetyIncident $i) => $events->push([
                'type' => 'safety',
                'icon' => 'heroicon-o-exclamation-triangle',
                'message' => "Safety incident {$i->incident_number} reported ({$i->severity})",
                'time' => $i->created_at,
            ]));

        return $events->sortByDesc('time')->take($limit)->values()->all();
    }

    // ── Production Management ───────────────────────────────────────────────

    public function feedQuantityToday(?int $companyId): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('feed_quantity');
    }

    public function crusherOutputToday(?int $companyId): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('crusher_throughput');
    }

    public function finishedProductsToday(?int $companyId): float
    {
        return (float) ProductionOutput::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->whereHas('productionLog', fn ($q) => $q->where('status', 'Approved'))
            ->sum('quantity_produced');
    }

    protected function approvedProductionLogs(?int $companyId, ?Carbon $from = null, ?Carbon $to = null)
    {
        return ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->when($from && $to, fn ($q) => $q->whereBetween('date', [$from, $to]));
    }

    public function yieldPercent(?int $companyId, int $days = 30): float
    {
        $logs = $this->approvedProductionLogs($companyId, now()->subDays($days), now())
            ->whereNotNull('feed_quantity')->where('feed_quantity', '>', 0)
            ->whereNotNull('finished_product_quantity')
            ->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        $feed = (float) $logs->sum('feed_quantity');
        $finished = (float) $logs->sum('finished_product_quantity');

        return $feed > 0 ? round(($finished / $feed) * 100, 1) : 0.0;
    }

    public function wastePercent(?int $companyId, int $days = 30): float
    {
        $logs = $this->approvedProductionLogs($companyId, now()->subDays($days), now())
            ->whereNotNull('feed_quantity')->where('feed_quantity', '>', 0)
            ->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        $feed = (float) $logs->sum('feed_quantity');
        $waste = (float) $logs->sum('waste_material');

        return $feed > 0 ? round(($waste / $feed) * 100, 1) : 0.0;
    }

    public function plantUtilizationPercent(?int $companyId): float
    {
        $total = ProductionLine::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 0.0;
        }

        $running = ProductionLine::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Running')->count();

        return round(($running / $total) * 100, 1);
    }

    public function shiftEfficiencyPercent(?int $companyId): float
    {
        $logs = ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->whereNotNull('operating_hours')
            ->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        $operating = (float) $logs->sum('operating_hours');
        $idle = (float) $logs->sum('idle_hours');
        $total = $operating + $idle;

        return $total > 0 ? round(($operating / $total) * 100, 1) : 0.0;
    }

    public function avgProductionRate(?int $companyId): float
    {
        $logs = ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->whereNotNull('operating_hours')->where('operating_hours', '>', 0)
            ->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        $tonnes = (float) $logs->sum('quantity_tonnes');
        $hours = (float) $logs->sum('operating_hours');

        return $hours > 0 ? round($tonnes / $hours, 1) : 0.0;
    }

    public function operatingHoursToday(?int $companyId): float
    {
        return (float) ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('operating_hours');
    }

    public function downtimeHoursToday(?int $companyId): float
    {
        return round((float) ProductionDowntime::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('start_time', today())
            ->sum('duration_minutes') / 60, 1);
    }

    public function downtimeByReason(?int $companyId, int $days = 30): array
    {
        return ProductionDowntime::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('start_time', '>=', now()->subDays($days))
            ->get()
            ->groupBy('reason')
            ->map(fn ($group) => (int) $group->sum('duration_minutes'))
            ->toArray();
    }

    public function productMix(?int $companyId, int $days = 30): array
    {
        return ProductionOutput::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays($days))
            ->whereHas('productionLog', fn ($q) => $q->where('status', 'Approved'))
            ->with('material')
            ->get()
            ->groupBy(fn (ProductionOutput $o) => $o->material?->name ?? 'Unknown')
            ->map(fn ($group) => (float) $group->sum('quantity_produced'))
            ->toArray();
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }
}
