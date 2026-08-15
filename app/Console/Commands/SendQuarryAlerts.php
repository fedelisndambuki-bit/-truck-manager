<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CrushingPlant;
use App\Models\DispatchOrder;
use App\Models\Equipment;
use App\Models\FuelStock;
use App\Models\Maintenance;
use App\Models\ProductionLog;
use App\Models\ProductionSetting;
use App\Models\ProductionTarget;
use App\Models\Stockpile;
use App\Models\WeighbridgeTicket;
use App\Notifications\QuarryNotification;
use App\Services\QuarryNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendQuarryAlerts extends Command
{
    protected $signature = 'quarry:send-alerts';

    protected $description = 'Notify relevant users about low stockpiles, equipment/plant breakdowns, maintenance due, low fuel, delayed dispatches and production performance';

    public function handle(QuarryNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyLowStockpiles($companyId, $users);
            $sent += $this->notifyEquipmentBreakdowns($companyId, $users);
            $sent += $this->notifyPlantBreakdowns($companyId, $users);
            $sent += $this->notifyMaintenanceDue($companyId, $users);
            $sent += $this->notifyLowFuel($companyId, $users);
            $sent += $this->notifyDelayedDispatches($companyId, $users);
            $sent += $this->notifyProductionBelowTarget($companyId, $users);
            $sent += $this->notifyLowYield($companyId, $users);
            $sent += $this->notifyTargetAchieved($companyId, $users);
            $sent += $this->notifyApprovalPending($companyId, $users);
        });

        $this->info("Quarry alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyLowStockpiles(int $companyId, $users): int
    {
        $stockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->with('material')
            ->get();

        foreach ($stockpiles as $stockpile) {
            Notification::send($users, new QuarryNotification('low_stockpile', [
                'reference' => $stockpile->material?->name ?? 'Stockpile #' . $stockpile->id,
            ]));
        }

        return $stockpiles->count();
    }

    private function notifyEquipmentBreakdowns(int $companyId, $users): int
    {
        $equipment = Equipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Out of Service')
            ->get();

        foreach ($equipment as $item) {
            Notification::send($users, new QuarryNotification('equipment_breakdown', [
                'reference' => $item->name,
            ]));
        }

        return $equipment->count();
    }

    private function notifyPlantBreakdowns(int $companyId, $users): int
    {
        $plants = CrushingPlant::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Breakdown')
            ->get();

        foreach ($plants as $plant) {
            Notification::send($users, new QuarryNotification('plant_breakdown', [
                'reference' => $plant->name,
            ]));
        }

        return $plants->count();
    }

    private function notifyMaintenanceDue(int $companyId, $users): int
    {
        $records = Maintenance::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('equipment_id')
            ->where('status', 'Pending')
            ->whereNotNull('next_maintenance_date')
            ->whereDate('next_maintenance_date', '<=', now()->addDays(7))
            ->with('equipment')
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new QuarryNotification('maintenance_due', [
                'reference' => $record->equipment?->name ?? 'Equipment #' . $record->equipment_id,
            ]));
        }

        return $records->count();
    }

    private function notifyLowFuel(int $companyId, $users): int
    {
        $stocks = FuelStock::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('capacity_litres')
            ->whereRaw('current_level_litres <= capacity_litres * 0.2')
            ->with('extractionSite')
            ->get();

        foreach ($stocks as $stock) {
            Notification::send($users, new QuarryNotification('low_fuel', [
                'reference' => $stock->extractionSite?->name ?? 'Site #' . $stock->extraction_site_id,
            ]));
        }

        return $stocks->count();
    }

    private function notifyDelayedDispatches(int $companyId, $users): int
    {
        $tickets = WeighbridgeTicket::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Pending')
            ->whereNotNull('gate_in_at')
            ->where('gate_in_at', '<=', now()->subMinutes(60))
            ->get();

        foreach ($tickets as $ticket) {
            Notification::send($users, new QuarryNotification('delayed_dispatch', [
                'reference' => $ticket->ticket_number,
            ]));
        }

        // DispatchOrder is the newer, unreconciled parallel write path to
        // WeighbridgeTicket — a truck stuck in its 17-status pipeline for the
        // same 60+ minutes needs the same alert.
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotIn('status', ['Approved', 'Rejected', 'Exited', 'En Route', 'Delivered', 'Cancelled'])
            ->whereNotNull('gate_in_at')
            ->where('gate_in_at', '<=', now()->subMinutes(60))
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new QuarryNotification('delayed_dispatch', [
                'reference' => $order->dispatch_number,
            ]));
        }

        return $tickets->count() + $orders->count();
    }

    private function notifyProductionBelowTarget(int $companyId, $users): int
    {
        $threshold = (int) ProductionSetting::getSetting($companyId, 'target_shortfall_threshold', 80);

        $targets = ProductionTarget::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('period_end', '>=', now()->subDays(1))
            ->where('period_end', '<=', now())
            ->with('extractionSite')
            ->get()
            ->filter(fn (ProductionTarget $target) => $target->achievementPercent() < $threshold);

        foreach ($targets as $target) {
            Notification::send($users, new QuarryNotification('production_below_target', [
                'reference' => $target->extractionSite?->name ?? 'Quarry #' . $target->extraction_site_id,
            ]));
        }

        return $targets->count();
    }

    private function notifyLowYield(int $companyId, $users): int
    {
        $threshold = (int) ProductionSetting::getSetting($companyId, 'low_yield_threshold', 70);

        $records = ProductionLog::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Approved')
            ->whereNotNull('yield_percent')
            ->where('yield_percent', '<', $threshold)
            ->whereDate('date', today())
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new QuarryNotification('low_yield', [
                'reference' => $record->production_number,
            ]));
        }

        return $records->count();
    }

    private function notifyTargetAchieved(int $companyId, $users): int
    {
        $targets = ProductionTarget::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereDate('period_end', today())
            ->with('extractionSite')
            ->get()
            ->filter(fn (ProductionTarget $target) => $target->achievementPercent() >= 100);

        foreach ($targets as $target) {
            Notification::send($users, new QuarryNotification('target_achieved', [
                'reference' => $target->extractionSite?->name ?? 'Quarry #' . $target->extraction_site_id,
            ]));
        }

        return $targets->count();
    }

    private function notifyApprovalPending(int $companyId, $users): int
    {
        $records = ProductionLog::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Submitted')
            ->where('updated_at', '<=', now()->subHours(2))
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new QuarryNotification('approval_pending', [
                'reference' => $record->production_number,
            ]));
        }

        return $records->count();
    }
}
