<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Company;
use App\Models\LabEquipment;
use App\Models\LabSample;
use App\Models\LabSetting;
use App\Models\LabTest;
use App\Models\QualityHold;
use App\Notifications\LabNotification;
use App\Services\LabNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendLabAlerts extends Command
{
    protected $signature = 'lab:send-alerts';

    protected $description = 'Notify relevant users about sample receipt, testing due, test failures, quality holds/releases, calibration due, certificate generation, and pending quality approvals';

    public function handle(LabNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifySampleReceived($companyId, $users);
            $sent += $this->notifyTestingDue($companyId, $users);
            $sent += $this->notifyTestFailed($companyId, $users);
            $sent += $this->notifyQualityHold($companyId, $users);
            $sent += $this->notifyQualityReleased($companyId, $users);
            $sent += $this->notifyCalibrationDue($companyId, $users);
            $sent += $this->notifyCertificateGenerated($companyId, $users);
            $sent += $this->notifyApprovalPending($companyId, $users);
        });

        $this->info("Lab alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifySampleReceived(int $companyId, $users): int
    {
        $samples = LabSample::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Received')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($samples as $sample) {
            Notification::send($users, new LabNotification('sample_received', ['reference' => $sample->sample_number]));
        }

        return $samples->count();
    }

    private function notifyTestingDue(int $companyId, $users): int
    {
        $requests = \App\Models\SamplingRequest::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereIn('status', ['Assigned', 'In Progress'])
            ->whereDate('due_date', '<=', today())
            ->get();

        foreach ($requests as $request) {
            Notification::send($users, new LabNotification('testing_due', ['reference' => $request->request_number]));
        }

        return $requests->count();
    }

    private function notifyTestFailed(int $companyId, $users): int
    {
        $tests = LabTest::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('result', 'Fail')
            ->where('updated_at', '>=', now()->subHour())
            ->with('sample')
            ->get();

        foreach ($tests as $test) {
            Notification::send($users, new LabNotification('test_failed', ['reference' => $test->sample?->sample_number ?? $test->test_number]));
        }

        return $tests->count();
    }

    private function notifyQualityHold(int $companyId, $users): int
    {
        $holds = QualityHold::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Active')
            ->where('held_at', '>=', now()->subHour())
            ->get();

        foreach ($holds as $hold) {
            Notification::send($users, new LabNotification('quality_hold', ['reference' => $hold->hold_number]));
        }

        return $holds->count();
    }

    private function notifyQualityReleased(int $companyId, $users): int
    {
        $holds = QualityHold::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Released')
            ->where('released_at', '>=', now()->subHour())
            ->get();

        foreach ($holds as $hold) {
            Notification::send($users, new LabNotification('quality_released', ['reference' => $hold->hold_number]));
        }

        return $holds->count();
    }

    private function notifyCalibrationDue(int $companyId, $users): int
    {
        $reminderDays = (int) LabSetting::getSetting($companyId, 'calibration_reminder_days', 14);

        $equipment = LabEquipment::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('calibration_due_date')
            ->whereDate('calibration_due_date', '<=', now()->addDays($reminderDays))
            ->where('status', '!=', 'Out of Service')
            ->get();

        foreach ($equipment as $item) {
            Notification::send($users, new LabNotification('calibration_due', ['reference' => $item->name]));
        }

        return $equipment->count();
    }

    private function notifyCertificateGenerated(int $companyId, $users): int
    {
        $certificates = Certificate::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Issued')
            ->where('issued_at', '>=', now()->subHour())
            ->get();

        foreach ($certificates as $certificate) {
            Notification::send($users, new LabNotification('certificate_generated', ['reference' => $certificate->certificate_number]));
        }

        return $certificates->count();
    }

    private function notifyApprovalPending(int $companyId, $users): int
    {
        $samples = LabSample::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereHas('approvalRequests', function ($q) {
                $q->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled'])
                    ->where('created_at', '>=', now()->subHour());
            })
            ->get();

        foreach ($samples as $sample) {
            Notification::send($users, new LabNotification('approval_pending', ['reference' => $sample->sample_number]));
        }

        return $samples->count();
    }
}
