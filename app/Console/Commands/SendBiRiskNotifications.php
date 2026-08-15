<?php

namespace App\Console\Commands;

use App\Models\BiSetting;
use App\Models\Company;
use App\Models\Project;
use App\Notifications\BiNotification;
use App\Services\BiNotificationRecipients;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ProjectFinanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendBiRiskNotifications extends Command
{
    protected $signature = 'bi:send-risk-notifications';

    protected $description = 'Notify relevant users about budget risk, project delays, rising safety risk and KPI threshold breaches — the same deterministic rule checks used by AI Insights';

    public function handle(BiNotificationRecipients $recipients, ProjectFinanceService $finance, BusinessIntelligenceService $bi): void
    {
        $sent = 0;

        Company::query()->where('industry', 'construction')->each(function (Company $company) use ($recipients, $finance, $bi, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyBudgetRisk($companyId, $users, $finance);
            $sent += $this->notifyProjectDelay($companyId, $users, $finance);
            $sent += $this->notifySafetyRisk($companyId, $users, $bi);
            $sent += $this->notifyKpiThresholds($companyId, $users, $bi);
        });

        $this->info("BI risk-notification sweep complete. Sent {$sent} notification(s).");
    }

    private function notifyBudgetRisk(int $companyId, $users, ProjectFinanceService $finance): int
    {
        if (! BiSetting::getSetting($companyId, 'notify_budget_risk', true)) {
            return 0;
        }

        $threshold = (float) BiSetting::getSetting($companyId, 'budget_overrun_threshold', 0.9);
        $count = 0;

        Project::withoutGlobalScope('company')->where('company_id', $companyId)->where('status', 'Active')->each(function (Project $project) use ($finance, $threshold, $users, &$count) {
            $cpi = $finance->costPerformanceIndex($project);

            if ($cpi >= $threshold) {
                return;
            }

            Notification::send($users, new BiNotification('budget_risk', [
                'reference' => 'CPI ' . number_format($cpi, 2),
                'project_name' => $project->name,
            ]));

            $count++;
        });

        return $count;
    }

    private function notifyProjectDelay(int $companyId, $users, ProjectFinanceService $finance): int
    {
        if (! BiSetting::getSetting($companyId, 'notify_project_delay', true)) {
            return 0;
        }

        $threshold = (float) BiSetting::getSetting($companyId, 'schedule_slippage_threshold', 0.9);
        $count = 0;

        Project::withoutGlobalScope('company')->where('company_id', $companyId)->where('status', 'Active')->each(function (Project $project) use ($finance, $threshold, $users, &$count) {
            $spi = $finance->schedulePerformanceIndex($project);

            if ($spi >= $threshold && $project->status !== 'Delayed') {
                return;
            }

            Notification::send($users, new BiNotification('project_delay', [
                'reference' => 'SPI ' . number_format($spi, 2),
                'project_name' => $project->name,
            ]));

            $count++;
        });

        return $count;
    }

    private function notifySafetyRisk(int $companyId, $users, BusinessIntelligenceService $bi): int
    {
        if (! BiSetting::getSetting($companyId, 'notify_safety_risk', true)) {
            return 0;
        }

        $threshold = (float) BiSetting::getSetting($companyId, 'safety_score_alert_threshold', 70);
        $score = $bi->safetyScore($companyId);

        if ($score >= $threshold) {
            return 0;
        }

        Notification::send($users, new BiNotification('safety_risk', [
            'reference' => 'Safety score ' . number_format($score, 1),
        ]));

        return 1;
    }

    private function notifyKpiThresholds(int $companyId, $users, BusinessIntelligenceService $bi): int
    {
        if (! BiSetting::getSetting($companyId, 'notify_kpi_threshold_exceeded', true)) {
            return 0;
        }

        $threshold = (float) BiSetting::getSetting($companyId, 'quality_score_alert_threshold', 80);
        $score = $bi->qualityScore($companyId);

        if ($score >= $threshold || $score <= 0) {
            return 0;
        }

        Notification::send($users, new BiNotification('kpi_threshold_exceeded', [
            'reference' => 'Quality score ' . number_format($score, 1) . '%',
        ]));

        return 1;
    }
}
