<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ProductionStockTransfer;
use App\Models\Stockpile;
use App\Models\StockpileAdjustment;
use App\Models\StockpilePhysicalCount;
use App\Models\StockpileSetting;
use App\Models\StockReservation;
use App\Notifications\StockpileNotification;
use App\Services\StockpileNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendStockpileAlerts extends Command
{
    protected $signature = 'stockpile:send-alerts';

    protected $description = 'Notify relevant users about stock below minimum, full stockpiles, expiring reservations, large adjustment requests, pending quality approvals, inventory variances, completed transfers and physical counts due';

    public function handle(StockpileNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyStockBelowMinimum($companyId, $users);
            $sent += $this->notifyStockpileFull($companyId, $users);
            $sent += $this->notifyReservationExpiring($companyId, $users);
            $sent += $this->notifyLargeAdjustmentRequested($companyId, $users);
            $sent += $this->notifyQualityApprovalPending($companyId, $users);
            $sent += $this->notifyInventoryVarianceDetected($companyId, $users);
            $sent += $this->notifyTransferCompleted($companyId, $users);
            $sent += $this->notifyPhysicalCountDue($companyId, $users);
        });

        $this->info("Stockpile alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyStockBelowMinimum(int $companyId, $users): int
    {
        $stockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->where('quantity_available', '>', 0)
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($stockpiles as $stockpile) {
            Notification::send($users, new StockpileNotification('stock_below_minimum', ['reference' => $stockpile->stockpile_code ?? $stockpile->label]));
        }

        return $stockpiles->count();
    }

    private function notifyStockpileFull(int $companyId, $users): int
    {
        $stockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('max_level')
            ->whereColumn('quantity_available', '>=', 'max_level')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($stockpiles as $stockpile) {
            Notification::send($users, new StockpileNotification('stockpile_full', ['reference' => $stockpile->stockpile_code ?? $stockpile->label]));
        }

        return $stockpiles->count();
    }

    private function notifyReservationExpiring(int $companyId, $users): int
    {
        $days = (int) StockpileSetting::getSetting($companyId, 'reservation_expiry_reminder_days', 3);

        $reservations = StockReservation::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Reserved')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();

        foreach ($reservations as $reservation) {
            Notification::send($users, new StockpileNotification('reservation_expiring', ['reference' => $reservation->reservation_number]));
        }

        return $reservations->count();
    }

    private function notifyLargeAdjustmentRequested(int $companyId, $users): int
    {
        $threshold = (float) StockpileSetting::getSetting($companyId, 'large_adjustment_threshold', 50);

        $adjustments = StockpileAdjustment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Submitted')
            ->where('updated_at', '>=', now()->subHour())
            ->get()
            ->filter(fn (StockpileAdjustment $a) => abs((float) $a->quantity) >= $threshold);

        foreach ($adjustments as $adjustment) {
            Notification::send($users, new StockpileNotification('large_adjustment_requested', ['reference' => $adjustment->adjustment_number]));
        }

        return $adjustments->count();
    }

    private function notifyQualityApprovalPending(int $companyId, $users): int
    {
        $stockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('quality_status', 'Pending Test')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($stockpiles as $stockpile) {
            Notification::send($users, new StockpileNotification('quality_approval_pending', ['reference' => $stockpile->stockpile_code ?? $stockpile->label]));
        }

        return $stockpiles->count();
    }

    private function notifyInventoryVarianceDetected(int $companyId, $users): int
    {
        $counts = StockpilePhysicalCount::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Approved')
            ->where('updated_at', '>=', now()->subHour())
            ->get()
            ->filter(fn (StockpilePhysicalCount $c) => abs($c->variancePercent()) >= 5);

        foreach ($counts as $count) {
            Notification::send($users, new StockpileNotification('inventory_variance_detected', ['reference' => $count->count_number]));
        }

        return $counts->count();
    }

    private function notifyTransferCompleted(int $companyId, $users): int
    {
        $transfers = ProductionStockTransfer::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Completed')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($transfers as $transfer) {
            Notification::send($users, new StockpileNotification('transfer_completed', ['reference' => $transfer->transfer_number]));
        }

        return $transfers->count();
    }

    private function notifyPhysicalCountDue(int $companyId, $users): int
    {
        $days = (int) StockpileSetting::getSetting($companyId, 'physical_count_due_days', 90);

        // Stockpiles with no StockpilePhysicalCount at all, or none within the reminder window.
        $dueStockpiles = Stockpile::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->get()
            ->filter(function (Stockpile $stockpile) use ($days) {
                $lastCount = StockpilePhysicalCount::withoutGlobalScope('company')
                    ->where('stockpile_id', $stockpile->id)
                    ->latest('count_date')
                    ->first();

                return ! $lastCount || $lastCount->count_date->lt(now()->subDays($days));
            });

        foreach ($dueStockpiles as $stockpile) {
            Notification::send($users, new StockpileNotification('physical_count_due', ['reference' => $stockpile->stockpile_code ?? $stockpile->label]));
        }

        return $dueStockpiles->count();
    }
}
