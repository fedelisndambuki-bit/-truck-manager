<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\Material;
use App\Models\PutAwayTask;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Notifications\MaterialsNotification;
use App\Services\MaterialsNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendMaterialsAlerts extends Command
{
    protected $signature = 'materials:send-alerts';

    protected $description = 'Notify relevant users about low/out-of-stock and reorder-point materials, goods received, pending transfers, pending put-away tasks and expiring stock';

    public function handle(MaterialsNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyLowStock($companyId, $users);
            $sent += $this->notifyOutOfStock($companyId, $users);
            $sent += $this->notifyReorderPoint($companyId, $users);
            $sent += $this->notifyGrnReceived($companyId, $users);
            $sent += $this->notifyTransferPending($companyId, $users);
            $sent += $this->notifyPutAwayPending($companyId, $users);
            $sent += $this->notifyExpiringItems($companyId, $users);
        });

        $this->info("Materials alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyLowStock(int $companyId, $users): int
    {
        $materials = Material::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('min_stock')
            ->where('min_stock', '>', 0)
            ->whereColumn('quantity_on_hand', '<=', 'min_stock')
            ->where('quantity_on_hand', '>', 0)
            ->get();

        foreach ($materials as $material) {
            Notification::send($users, new MaterialsNotification('low_stock', ['reference' => "{$material->name} ({$material->material_code})"]));
        }

        return $materials->count();
    }

    private function notifyOutOfStock(int $companyId, $users): int
    {
        $materials = Material::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('quantity_on_hand', '<=', 0)
            ->get();

        foreach ($materials as $material) {
            Notification::send($users, new MaterialsNotification('out_of_stock', ['reference' => "{$material->name} ({$material->material_code})"]));
        }

        return $materials->count();
    }

    private function notifyReorderPoint(int $companyId, $users): int
    {
        $materials = Material::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('reorder_level')
            ->where('reorder_level', '>', 0)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->get();

        foreach ($materials as $material) {
            Notification::send($users, new MaterialsNotification('reorder_point', ['reference' => "{$material->name} ({$material->material_code})"]));
        }

        return $materials->count();
    }

    private function notifyGrnReceived(int $companyId, $users): int
    {
        $grns = GoodsReceivedNote::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHour())
            ->get();

        foreach ($grns as $grn) {
            Notification::send($users, new MaterialsNotification('grn_received', ['reference' => $grn->grn_number]));
        }

        return $grns->count();
    }

    private function notifyTransferPending(int $companyId, $users): int
    {
        $transfers = StockTransfer::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Pending')
            ->where('created_at', '<=', now()->subDay())
            ->get();

        foreach ($transfers as $transfer) {
            Notification::send($users, new MaterialsNotification('transfer_pending', ['reference' => $transfer->transfer_number]));
        }

        return $transfers->count();
    }

    private function notifyPutAwayPending(int $companyId, $users): int
    {
        $tasks = PutAwayTask::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Pending')
            ->where('created_at', '<=', now()->subHours(4))
            ->get();

        foreach ($tasks as $task) {
            Notification::send($users, new MaterialsNotification('putaway_pending', ['reference' => $task->task_number]));
        }

        return $tasks->count();
    }

    private function notifyExpiringItems(int $companyId, $users): int
    {
        $levels = StockLevel::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->with('material')
            ->get();

        foreach ($levels as $level) {
            Notification::send($users, new MaterialsNotification('expiring_item', ['reference' => $level->material?->name ?? "Material #{$level->material_id}"]));
        }

        return $levels->count();
    }
}
