<?php

namespace App\Console\Commands;

use App\Models\Breakdown;
use App\Models\Company;
use App\Models\EamSetting;
use App\Models\Equipment;
use App\Models\EquipmentHealthReading;
use App\Models\Part;
use App\Models\PreventiveMaintenancePlan;
use App\Models\TelematicsReading;
use App\Models\WarrantyClaim;
use App\Notifications\EamNotification;
use App\Services\EamNotificationRecipients;
use App\Services\FuelAnomalyDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendEamAlerts extends Command
{
    protected $signature = 'eam:send-alerts';

    protected $description = 'Notify relevant users about maintenance due, breakdowns reported, overdue inspections, expiring warranties, low spare parts, detected fuel theft, offline equipment, engine faults and due calibrations';

    public function handle(EamNotificationRecipients $recipients, FuelAnomalyDetectionService $fuelAnomaly): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, $fuelAnomaly, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyMaintenanceDue($companyId, $users);
            $sent += $this->notifyBreakdownReported($companyId, $users);
            $sent += $this->notifyInspectionOverdue($companyId, $users);
            $sent += $this->notifyWarrantyExpiring($companyId, $users);
            $sent += $this->notifyLowSpareParts($companyId, $users);
            $sent += $this->notifyFuelTheft($companyId, $users, $fuelAnomaly);
            $sent += $this->notifyEquipmentOffline($companyId, $users);
            $sent += $this->notifyEngineFault($companyId, $users);
            $sent += $this->notifyCalibrationDue($companyId, $users);
        });

        $this->info("EAM alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyMaintenanceDue(int $companyId, $users): int
    {
        $plans = PreventiveMaintenancePlan::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('trigger_type', 'Calendar')
            ->whereNotNull('next_due_value')
            ->where('next_due_value', '<=', now()->timestamp)
            ->with('equipment')
            ->get();

        foreach ($plans as $plan) {
            Notification::send($users, new EamNotification('maintenance_due', ['reference' => $plan->equipment?->name ?? "Plan #{$plan->id}"]));
        }

        return $plans->count();
    }

    private function notifyBreakdownReported(int $companyId, $users): int
    {
        $breakdowns = Breakdown::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHour())
            ->with('equipment')
            ->get();

        foreach ($breakdowns as $breakdown) {
            Notification::send($users, new EamNotification('breakdown_reported', ['reference' => $breakdown->equipment?->name ?? $breakdown->breakdown_number]));
        }

        return $breakdowns->count();
    }

    private function notifyInspectionOverdue(int $companyId, $users): int
    {
        $days = (int) EamSetting::getSetting($companyId, 'inspection_overdue_days', 7);

        $overdue = Equipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'Disposed')
            ->whereDoesntHave('inspections', fn ($q) => $q->where('date', '>=', now()->subDays($days)))
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($overdue as $equipment) {
            Notification::send($users, new EamNotification('inspection_overdue', ['reference' => $equipment->name]));
        }

        return $overdue->count();
    }

    private function notifyWarrantyExpiring(int $companyId, $users): int
    {
        $days = (int) EamSetting::getSetting($companyId, 'warranty_expiring_days', 30);

        $equipment = Equipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [now(), now()->addDays($days)])
            ->get();

        foreach ($equipment as $item) {
            Notification::send($users, new EamNotification('warranty_expiring', ['reference' => $item->name]));
        }

        $claims = WarrantyClaim::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $equipment->count() + $claims;
    }

    private function notifyLowSpareParts(int $companyId, $users): int
    {
        $parts = Part::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('reorder_level')
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($parts as $part) {
            Notification::send($users, new EamNotification('low_spare_parts', ['reference' => "{$part->name} ({$part->part_number})"]));
        }

        return $parts->count();
    }

    private function notifyFuelTheft(int $companyId, $users, FuelAnomalyDetectionService $fuelAnomaly): int
    {
        $anomalies = $fuelAnomaly->detect($companyId, 1);

        foreach ($anomalies as $anomaly) {
            Notification::send($users, new EamNotification('fuel_theft_detected', ['reference' => $anomaly['equipment_name'] ?? "Equipment #{$anomaly['equipment_id']}"]));
        }

        return $anomalies->count();
    }

    private function notifyEquipmentOffline(int $companyId, $users): int
    {
        $hours = (int) EamSetting::getSetting($companyId, 'equipment_offline_hours', 24);

        $equipmentIdsWithHistory = TelematicsReading::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->distinct()
            ->pluck('equipment_id');

        $offline = Equipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('id', $equipmentIdsWithHistory)
            ->whereDoesntHave('telematicsReadings', fn ($q) => $q->where('recorded_at', '>=', now()->subHours($hours)))
            ->where('status', '!=', 'Disposed')
            ->get();

        foreach ($offline as $equipment) {
            Notification::send($users, new EamNotification('equipment_offline', ['reference' => $equipment->name]));
        }

        return $offline->count();
    }

    private function notifyEngineFault(int $companyId, $users): int
    {
        $readings = EquipmentHealthReading::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('error_codes')
            ->where('created_at', '>=', now()->subHour())
            ->with('equipment')
            ->get()
            ->filter(fn (EquipmentHealthReading $r) => ! empty($r->error_codes));

        foreach ($readings as $reading) {
            Notification::send($users, new EamNotification('engine_fault_detected', ['reference' => $reading->equipment?->name ?? "Equipment #{$reading->equipment_id}"]));
        }

        return $readings->count();
    }

    private function notifyCalibrationDue(int $companyId, $users): int
    {
        $days = (int) EamSetting::getSetting($companyId, 'calibration_due_days', 14);

        $equipment = Equipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('next_calibration_date')
            ->whereBetween('next_calibration_date', [now(), now()->addDays($days)])
            ->get();

        foreach ($equipment as $item) {
            Notification::send($users, new EamNotification('calibration_due', ['reference' => $item->name]));
        }

        return $equipment->count();
    }
}
