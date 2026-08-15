<?php

namespace App\Console\Commands;

use App\Models\BlastCompliance;
use App\Models\BlastEnvironmentalReading;
use App\Models\BlastExecution;
use App\Models\BlastPlan;
use App\Models\BlastSetting;
use App\Models\Company;
use App\Models\ExplosiveIssue;
use App\Notifications\BlastNotification;
use App\Services\BlastNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendBlastAlerts extends Command
{
    protected $signature = 'blast:send-alerts';

    protected $description = 'Notify relevant users about scheduled/approved/delayed/completed blasts, expiring permits, low explosive stock, exceeded environmental thresholds and pending post-blast analysis';

    public function handle(BlastNotificationRecipients $recipients): void
    {
        $sent = 0;

        Company::query()->where('industry', 'quarry')->each(function (Company $company) use ($recipients, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyBlastScheduled($companyId, $users);
            $sent += $this->notifyBlastApproved($companyId, $users);
            $sent += $this->notifyPermitExpiring($companyId, $users);
            $sent += $this->notifyExplosiveStockLow($companyId, $users);
            $sent += $this->notifyBlastDelayed($companyId, $users);
            $sent += $this->notifyEnvironmentalThresholdExceeded($companyId, $users);
            $sent += $this->notifyBlastCompleted($companyId, $users);
            $sent += $this->notifyPostBlastAnalysisPending($companyId, $users);
        });

        $this->info("Blast alert sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyBlastScheduled(int $companyId, $users): int
    {
        $plans = BlastPlan::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('scheduled_date')
            ->whereDate('scheduled_date', today())
            ->get();

        foreach ($plans as $plan) {
            Notification::send($users, new BlastNotification('blast_scheduled', ['reference' => $plan->blast_number]));
        }

        return $plans->count();
    }

    private function notifyBlastApproved(int $companyId, $users): int
    {
        $plans = BlastPlan::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Approved')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($plans as $plan) {
            Notification::send($users, new BlastNotification('blast_approved', ['reference' => $plan->blast_number]));
        }

        return $plans->count();
    }

    private function notifyPermitExpiring(int $companyId, $users): int
    {
        $days = (int) BlastSetting::getSetting($companyId, 'permit_expiry_reminder_days', 30);

        $compliances = BlastCompliance::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('permit_expiry')
            ->whereBetween('permit_expiry', [now(), now()->addDays($days)])
            ->with('blastPlan')
            ->get();

        foreach ($compliances as $compliance) {
            Notification::send($users, new BlastNotification('permit_expiring', ['reference' => $compliance->blastPlan?->blast_number ?? $compliance->permit_number]));
        }

        return $compliances->count();
    }

    private function notifyExplosiveStockLow(int $companyId, $users): int
    {
        $threshold = (float) BlastSetting::getSetting($companyId, 'low_explosive_stock_threshold', 50);

        $issues = ExplosiveIssue::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('remaining_quantity', '<', $threshold)
            ->where('remaining_quantity', '>', 0)
            ->get();

        foreach ($issues as $issue) {
            Notification::send($users, new BlastNotification('explosive_stock_low', ['reference' => $issue->explosive_type]));
        }

        return $issues->count();
    }

    private function notifyBlastDelayed(int $companyId, $users): int
    {
        $executions = BlastExecution::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('blast_outcome', 'Delayed')
            ->where('updated_at', '>=', now()->subDay())
            ->with('blastPlan')
            ->get();

        foreach ($executions as $execution) {
            Notification::send($users, new BlastNotification('blast_delayed', ['reference' => $execution->blastPlan?->blast_number]));
        }

        return $executions->count();
    }

    private function notifyEnvironmentalThresholdExceeded(int $companyId, $users): int
    {
        $readings = BlastEnvironmentalReading::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('compliance_status', 'Exceeded')
            ->where('created_at', '>=', now()->subDay())
            ->with('blastPlan')
            ->get();

        foreach ($readings as $reading) {
            Notification::send($users, new BlastNotification('environmental_threshold_exceeded', ['reference' => $reading->blastPlan?->blast_number]));
        }

        return $readings->count();
    }

    private function notifyBlastCompleted(int $companyId, $users): int
    {
        $plans = BlastPlan::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Executed')
            ->where('updated_at', '>=', now()->subHour())
            ->get();

        foreach ($plans as $plan) {
            Notification::send($users, new BlastNotification('blast_completed', ['reference' => $plan->blast_number]));
        }

        return $plans->count();
    }

    private function notifyPostBlastAnalysisPending(int $companyId, $users): int
    {
        $plans = BlastPlan::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('status', 'Executed')
            ->whereDoesntHave('postBlastAnalysis')
            ->whereHas('execution', fn ($q) => $q->whereNotNull('actual_date')->where('actual_date', '<=', now()->subDay()))
            ->get();

        foreach ($plans as $plan) {
            Notification::send($users, new BlastNotification('post_blast_analysis_pending', ['reference' => $plan->blast_number]));
        }

        return $plans->count();
    }
}
