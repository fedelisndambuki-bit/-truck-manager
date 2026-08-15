<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DispatchOrder;
use App\Models\ProofOfDelivery;
use App\Notifications\DispatchNotification;
use App\Services\DispatchNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDispatchAlerts extends Command
{
    protected $signature = 'dispatch:send-alerts';

    protected $description = 'Notify relevant users about dispatch scheduling, truck assignment, loading, departure, delays, arrival, and delivery confirmation/failure';

    public function handle(DispatchNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyDispatchScheduled($companyId, $users);
            $sent += $this->notifyTruckAssigned($companyId, $users);
            $sent += $this->notifyTruckLoading($companyId, $users);
            $sent += $this->notifyTruckDeparted($companyId, $users);
            $sent += $this->notifyTruckDelayed($companyId, $users);
            $sent += $this->notifyTruckArrived($companyId, $users);
            $sent += $this->notifyDeliveryCompleted($companyId, $users);
            $sent += $this->notifyDeliveryFailed($companyId, $users);
        });

        $this->info("Dispatch alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyDispatchScheduled(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('status', ['Draft', 'Planned'])
            ->where('created_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('dispatch_scheduled', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyTruckAssigned(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Planned')
            ->whereNotNull('vehicle_registration')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('truck_assigned', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyTruckLoading(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Loading')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('truck_loading', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyTruckDeparted(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Exited')
            ->where('exit_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('truck_departed', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyTruckDelayed(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Delayed')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('truck_delayed', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyTruckArrived(int $companyId, $users): int
    {
        $orders = DispatchOrder::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('arrived_at')
            ->where('arrived_at', '>=', now()->subHour())
            ->get();

        foreach ($orders as $order) {
            Notification::send($users, new DispatchNotification('truck_arrived', ['reference' => $order->dispatch_number]));
        }

        return $orders->count();
    }

    private function notifyDeliveryCompleted(int $companyId, $users): int
    {
        $pods = ProofOfDelivery::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Delivered')
            ->where('created_at', '>=', now()->subHour())
            ->with('dispatchOrder')
            ->get();

        foreach ($pods as $pod) {
            Notification::send($users, new DispatchNotification('delivery_completed', ['reference' => $pod->dispatchOrder?->dispatch_number ?? '#' . $pod->id]));
        }

        return $pods->count();
    }

    private function notifyDeliveryFailed(int $companyId, $users): int
    {
        $pods = ProofOfDelivery::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('status', ['Rejected', 'Returned'])
            ->where('created_at', '>=', now()->subHour())
            ->with('dispatchOrder')
            ->get();

        foreach ($pods as $pod) {
            Notification::send($users, new DispatchNotification('delivery_failed', ['reference' => $pod->dispatchOrder?->dispatch_number ?? '#' . $pod->id]));
        }

        return $pods->count();
    }
}
