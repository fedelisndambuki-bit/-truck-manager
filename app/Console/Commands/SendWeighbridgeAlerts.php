<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DispatchOrder;
use App\Models\LoadingInstruction;
use App\Models\Stockpile;
use App\Models\Weighbridge;
use App\Models\WeighbridgeSetting;
use App\Notifications\WeighbridgeNotification;
use App\Services\WeighbridgeNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendWeighbridgeAlerts extends Command
{
    protected $signature = 'weighbridge:send-alerts';

    protected $description = 'Notify relevant users about truck check-ins, long waits, loading progress, reloads, weight tolerance breaches, dispatch approvals, vehicle exits, low stock and offline scales';

    public function handle(WeighbridgeNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyTruckCheckedIn($companyId, $users);
            $sent += $this->notifyTruckWaitingTooLong($companyId, $users);
            $sent += $this->notifyLoadingStarted($companyId, $users);
            $sent += $this->notifyLoadingCompleted($companyId, $users);
            $sent += $this->notifyTruckReturnedForReload($companyId, $users);
            $sent += $this->notifyWeightOutsideTolerance($companyId, $users);
            $sent += $this->notifyDispatchApproved($companyId, $users);
            $sent += $this->notifyVehicleExited($companyId, $users);
            $sent += $this->notifyStockRunningLow($companyId, $users);
            $sent += $this->notifyScaleOffline($companyId, $users);
        });

        $this->info("Weighbridge alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyTruckCheckedIn(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Checked In')
            ->where('gate_in_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('truck_checked_in', ['reference' => $order->vehicle_registration]));
        }

        return $orders->count();
    }

    private function notifyTruckWaitingTooLong(int $companyId, $users): int
    {
        $threshold = (int) WeighbridgeSetting::getSetting($companyId, 'queue_delay_threshold_minutes', 45);

        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Checked In')
            ->whereNotNull('gate_in_at')
            ->where('gate_in_at', '<=', now()->subMinutes($threshold))
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('truck_waiting_too_long', ['reference' => $order->vehicle_registration]));
        }

        return $orders->count();
    }

    private function notifyLoadingStarted(int $companyId, $users): int
    {
        $instructions = LoadingInstruction::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Loading')
            ->where('loading_started_at', '>=', now()->subHour())
            ->with('dispatchOrder')
            ->get();

        foreach ($instructions as $instruction) {
            Notification::send($users, new WeighbridgeNotification('loading_started', ['reference' => $instruction->dispatchOrder?->vehicle_registration ?? $instruction->loading_number]));
        }

        return $instructions->count();
    }

    private function notifyLoadingCompleted(int $companyId, $users): int
    {
        $instructions = LoadingInstruction::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Completed')
            ->where('loading_completed_at', '>=', now()->subHour())
            ->with('dispatchOrder')
            ->get();

        foreach ($instructions as $instruction) {
            Notification::send($users, new WeighbridgeNotification('loading_completed', ['reference' => $instruction->dispatchOrder?->vehicle_registration ?? $instruction->loading_number]));
        }

        return $instructions->count();
    }

    private function notifyTruckReturnedForReload(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('weight_status', 'Underweight')
            ->where('status', 'Loading')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('truck_returned_for_reload', ['reference' => $order->vehicle_registration]));
        }

        return $orders->count();
    }

    private function notifyWeightOutsideTolerance(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('weight_status', ['Overweight', 'Critical'])
            ->where('gross_weighed_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('weight_outside_tolerance', ['reference' => $order->vehicle_registration]));
        }

        return $orders->count();
    }

    private function notifyDispatchApproved(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Approved')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('dispatch_approved', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyVehicleExited(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Exited')
            ->where('exit_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new WeighbridgeNotification('vehicle_exited', ['reference' => $order->vehicle_registration]));
        }

        return $orders->count();
    }

    private function notifyStockRunningLow(int $companyId, $users): int
    {
        $stockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->where('quantity_available', '>', 0)
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($stockpiles as $stockpile) {
            Notification::send($users, new WeighbridgeNotification('stock_running_low', ['reference' => $stockpile->stockpile_code ?? $stockpile->label]));
        }

        return $stockpiles->count();
    }

    private function notifyScaleOffline(int $companyId, $users): int
    {
        $weighbridges = Weighbridge::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Offline')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($weighbridges as $weighbridge) {
            Notification::send($users, new WeighbridgeNotification('scale_offline', ['reference' => $weighbridge->name]));
        }

        return $weighbridges->count();
    }
}
