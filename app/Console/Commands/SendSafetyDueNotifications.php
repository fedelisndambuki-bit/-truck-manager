<?php

namespace App\Console\Commands;

use App\Models\ComplianceRecord;
use App\Models\CorrectiveAction;
use App\Models\Company;
use App\Models\EmergencyItem;
use App\Models\PpeIssuance;
use App\Models\SafetyInspection;
use App\Models\SafetySetting;
use App\Models\TrainingRecord;
use App\Notifications\SafetyNotification;
use App\Services\SafetyNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendSafetyDueNotifications extends Command
{
    protected $signature = 'safety:send-due-notifications';

    protected $description = 'Notify relevant users about due/overdue/expiring safety items: inspections, corrective actions, training, PPE, drills, compliance';

    public function handle(SafetyNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyInspectionsDue($companyId, $users);
            $sent += $this->notifyOverdueCorrectiveActions($companyId, $users);
            $sent += $this->notifyExpiringTraining($companyId, $users);
            $sent += $this->notifyPpeDue($companyId, $users);
            $sent += $this->notifyDrillsDue($companyId, $users);
            $sent += $this->notifyExpiringCompliance($companyId, $users);
        });

        $this->info("Safety due-notification sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyInspectionsDue(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_inspection_due', true)) {
            return 0;
        }

        $days = (int) SafetySetting::getSetting($companyId, 'inspection_due_reminder_days', 7);

        $inspections = SafetyInspection::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Draft')
            ->whereDate('date', '<=', now()->addDays($days))
            ->with('site')
            ->get();

        foreach ($inspections as $inspection) {
            Notification::send($users, new SafetyNotification('inspection_due', [
                'reference' => $inspection->inspection_number,
                'site_name' => $inspection->site?->name,
            ]));
        }

        return $inspections->count();
    }

    private function notifyOverdueCorrectiveActions(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_corrective_action_overdue', true)) {
            return 0;
        }

        $actions = CorrectiveAction::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereDate('due_date', '<', now())
            ->whereNotIn('status', ['Completed', 'Verified', 'Closed'])
            ->get();

        foreach ($actions as $action) {
            Notification::send($users, new SafetyNotification('corrective_action_overdue', [
                'reference' => $action->action_number,
            ]));
        }

        return $actions->count();
    }

    private function notifyExpiringTraining(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_training_expiring', true)) {
            return 0;
        }

        $days = (int) SafetySetting::getSetting($companyId, 'training_expiry_reminder_days', 30);

        $records = TrainingRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('training_type', \App\Filament\Resources\SafetyTrainingResource::SAFETY_TYPES)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->with('staff')
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new SafetyNotification('training_expiring', [
                'reference' => $record->staff?->full_name . ' — ' . $record->training_type,
            ]));
        }

        return $records->count();
    }

    private function notifyPpeDue(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_ppe_replacement_due', true)) {
            return 0;
        }

        $days = (int) SafetySetting::getSetting($companyId, 'ppe_reminder_days', 14);

        $items = PpeIssuance::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', '!=', 'Returned')
            ->whereBetween('replacement_due_date', [now(), now()->addDays($days)])
            ->with('staff')
            ->get();

        foreach ($items as $item) {
            Notification::send($users, new SafetyNotification('ppe_replacement_due', [
                'reference' => $item->staff?->full_name . ' — ' . $item->item,
            ]));
        }

        return $items->count();
    }

    private function notifyDrillsDue(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_emergency_drill_due', true)) {
            return 0;
        }

        $items = EmergencyItem::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('type', 'Drill')
            ->whereDate('next_check_date', '<=', now())
            ->with('site')
            ->get();

        foreach ($items as $item) {
            Notification::send($users, new SafetyNotification('emergency_drill_due', [
                'reference' => $item->name,
                'site_name' => $item->site?->name,
            ]));
        }

        return $items->count();
    }

    private function notifyExpiringCompliance(int $companyId, $users): int
    {
        if (! SafetySetting::getSetting($companyId, 'notify_compliance_expiring', true)) {
            return 0;
        }

        $days = (int) SafetySetting::getSetting($companyId, 'compliance_reminder_days', 30);

        $records = ComplianceRecord::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->with('site')
            ->get();

        foreach ($records as $record) {
            Notification::send($users, new SafetyNotification('compliance_expiring', [
                'reference' => $record->title,
                'site_name' => $record->site?->name,
            ]));
        }

        return $records->count();
    }
}
