<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Conveyor;
use App\Models\Crusher;
use App\Models\CrushingPlant;
use App\Models\EnergyReading;
use App\Models\Maintenance;
use App\Models\PlantQualityTest;
use App\Models\PlantSetting;
use App\Models\ProductionRun;
use App\Models\Screen;
use App\Notifications\PlantNotification;
use App\Services\PlantNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendPlantAlerts extends Command
{
    protected $signature = 'plant:send-alerts';

    protected $description = 'Notify relevant users about stopped plants, crusher/screen/conveyor breakdowns, due maintenance, below-target production, high energy usage, failed quality tests and pending approvals';

    public function handle(PlantNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyPlantStopped($companyId, $users);
            $sent += $this->notifyCrusherBreakdown($companyId, $users);
            $sent += $this->notifyScreenFailure($companyId, $users);
            $sent += $this->notifyConveyorFailure($companyId, $users);
            $sent += $this->notifyMaintenanceDue($companyId, $users);
            $sent += $this->notifyProductionBelowTarget($companyId, $users);
            $sent += $this->notifyEnergyUsageHigh($companyId, $users);
            $sent += $this->notifyQualityTestFailed($companyId, $users);
            $sent += $this->notifyApprovalPending($companyId, $users);
        });

        $this->info("Plant alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyPlantStopped(int $companyId, $users): int
    {
        $plants = CrushingPlant::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Breakdown')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($plants as $plant) {
            Notification::send($users, new PlantNotification('plant_stopped', ['reference' => $plant->plant_code ?? $plant->name]));
        }

        return $plants->count();
    }

    private function notifyCrusherBreakdown(int $companyId, $users): int
    {
        $crushers = Crusher::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Breakdown')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($crushers as $crusher) {
            Notification::send($users, new PlantNotification('crusher_breakdown', ['reference' => $crusher->crusher_code]));
        }

        return $crushers->count();
    }

    private function notifyScreenFailure(int $companyId, $users): int
    {
        $screens = Screen::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Breakdown')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($screens as $screen) {
            Notification::send($users, new PlantNotification('screen_failure', ['reference' => $screen->name]));
        }

        return $screens->count();
    }

    private function notifyConveyorFailure(int $companyId, $users): int
    {
        $conveyors = Conveyor::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Breakdown')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($conveyors as $conveyor) {
            Notification::send($users, new PlantNotification('conveyor_failure', ['reference' => $conveyor->name]));
        }

        return $conveyors->count();
    }

    private function notifyMaintenanceDue(int $companyId, $users): int
    {
        $days = (int) PlantSetting::getSetting($companyId, 'maintenance_due_reminder_days', 7);

        $records = Maintenance::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('equipment_id')
            ->whereNotNull('next_maintenance_date')
            ->whereBetween('next_maintenance_date', [now(), now()->addDays($days)])
            ->with('equipment')
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new PlantNotification('maintenance_due', ['reference' => $record->equipment?->name ?? ('Equipment #' . $record->equipment_id)]));
        }

        return $records->count();
    }

    private function notifyProductionBelowTarget(int $companyId, $users): int
    {
        $threshold = (float) PlantSetting::getSetting($companyId, 'production_target_shortfall_threshold', 80);

        $runs = ProductionRun::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('status', ['Approved', 'Completed'])
            ->where('updated_at', '>=', now()->subHour())
            ->get()
            ->filter(fn (ProductionRun $run) => $run->yieldPercent() < $threshold);

        foreach ($runs as $run) {
            Notification::send($users, new PlantNotification('production_below_target', ['reference' => $run->run_number]));
        }

        return $runs->count();
    }

    private function notifyEnergyUsageHigh(int $companyId, $users): int
    {
        $threshold = (float) PlantSetting::getSetting($companyId, 'energy_usage_high_threshold_kwh', 5000);

        $readings = EnergyReading::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereDate('date', today())
            ->where('electricity_kwh', '>', $threshold)
            ->with('crushingPlant')
            ->get();

        foreach ($readings as $reading) {
            Notification::send($users, new PlantNotification('energy_usage_high', ['reference' => $reading->crushingPlant?->name ?? ('Plant #' . $reading->crushing_plant_id)]));
        }

        return $readings->count();
    }

    private function notifyQualityTestFailed(int $companyId, $users): int
    {
        $tests = PlantQualityTest::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('pass_fail', false)
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($tests as $test) {
            Notification::send($users, new PlantNotification('quality_test_failed', ['reference' => $test->sample_number]));
        }

        return $tests->count();
    }

    private function notifyApprovalPending(int $companyId, $users): int
    {
        $runs = ProductionRun::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Submitted')
            ->where('updated_at', '<=', now()->subHours(2))
            ->get();

        foreach ($runs as $run) {
            Notification::send($users, new PlantNotification('approval_pending', ['reference' => $run->run_number]));
        }

        return $runs->count();
    }
}
