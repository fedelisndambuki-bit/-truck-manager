<?php

namespace App\Console\Commands;

use App\Models\CashFlowForecast;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectFinanceSetting;
use App\Models\ProjectForecast;
use App\Models\RetentionEntry;
use App\Notifications\FinanceNotification;
use App\Services\Construction\ProjectFinanceService;
use App\Services\FinanceNotificationRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendFinanceDueNotifications extends Command
{
    protected $signature = 'finance:send-due-notifications';

    protected $description = 'Notify relevant users about overdue invoices, retention due for release, high cost variance, cash flow risk and forecast changes';

    public function handle(FinanceNotificationRecipients $recipients, ProjectFinanceService $service): void
    {
        $sent = 0;

        Company::query()->where('industry', 'construction')->each(function (Company $company) use ($recipients, $service, &$sent) {
            $companyId = $company->id;
            $users = $recipients->forCompany($companyId);

            if ($users->isEmpty()) {
                return;
            }

            $sent += $this->notifyOverdueInvoices($companyId, $users);
            $sent += $this->notifyRetentionDue($companyId, $users);
            $sent += $this->notifyCostVarianceHigh($companyId, $users, $service);
            $sent += $this->notifyCashFlowRisk($companyId, $users, $service);
            $sent += $this->notifyForecastChanges($companyId, $users);
        });

        $this->info("Finance due-notification sweep complete. Sent {$sent} notification batch(es).");
    }

    private function notifyOverdueInvoices(int $companyId, $users): int
    {
        if (! ProjectFinanceSetting::getSetting($companyId, 'notify_invoice_overdue', true)) {
            return 0;
        }

        $graceDays = (int) ProjectFinanceSetting::getSetting($companyId, 'invoice_overdue_alert_days', 0);

        $invoices = Invoice::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->whereNotNull('project_id')
            ->whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', now()->subDays($graceDays))
            ->with('project')
            ->get();

        foreach ($invoices as $invoice) {
            Notification::send($users, new FinanceNotification('invoice_overdue', [
                'reference' => $invoice->invoice_number,
                'project_name' => $invoice->project?->name,
            ]));
        }

        return $invoices->count();
    }

    private function notifyRetentionDue(int $companyId, $users): int
    {
        if (! ProjectFinanceSetting::getSetting($companyId, 'notify_retention_due', true)) {
            return 0;
        }

        $days = (int) ProjectFinanceSetting::getSetting($companyId, 'retention_due_alert_days', 30);

        $entries = RetentionEntry::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('release_status', '!=', 'Released')
            ->whereNotNull('dlp_end_date')
            ->whereDate('dlp_end_date', '<=', now()->addDays($days))
            ->with('project')
            ->get();

        foreach ($entries as $entry) {
            Notification::send($users, new FinanceNotification('retention_due', [
                'reference' => session('currency', 'KES') . ' ' . number_format((float) $entry->outstanding, 0),
                'project_name' => $entry->project?->name,
            ]));
        }

        return $entries->count();
    }

    private function notifyCostVarianceHigh(int $companyId, $users, ProjectFinanceService $service): int
    {
        if (! ProjectFinanceSetting::getSetting($companyId, 'notify_cost_variance_high', true)) {
            return 0;
        }

        $threshold = (float) ProjectFinanceSetting::getSetting($companyId, 'cost_variance_alert_threshold_percent', 10);
        $count = 0;

        Project::withoutGlobalScope('company')->where('company_id', $companyId)->each(function (Project $project) use ($service, $threshold, $users, &$count) {
            $bac = $service->budgetAtCompletion($project);

            if ($bac <= 0) {
                return;
            }

            $variancePercent = abs($service->costVariance($project)) / $bac * 100;

            if ($variancePercent < $threshold) {
                return;
            }

            Notification::send($users, new FinanceNotification('cost_variance_high', [
                'reference' => number_format($variancePercent, 1) . '%',
                'project_name' => $project->name,
            ]));

            $count++;
        });

        return $count;
    }

    private function notifyCashFlowRisk(int $companyId, $users, ProjectFinanceService $service): int
    {
        if (! ProjectFinanceSetting::getSetting($companyId, 'notify_cash_flow_risk', true)) {
            return 0;
        }

        $count = 0;

        Project::withoutGlobalScope('company')->where('company_id', $companyId)->each(function (Project $project) use ($service, $users, &$count) {
            if ($service->projectedCashFlow($project) >= 0) {
                return;
            }

            Notification::send($users, new FinanceNotification('cash_flow_risk', [
                'project_name' => $project->name,
            ]));

            $count++;
        });

        return $count;
    }

    private function notifyForecastChanges(int $companyId, $users): int
    {
        if (! ProjectFinanceSetting::getSetting($companyId, 'notify_forecast_changed', true)) {
            return 0;
        }

        $count = 0;

        Project::withoutGlobalScope('company')->where('company_id', $companyId)->each(function (Project $project) use ($users, &$count) {
            $latestTwo = ProjectForecast::withoutGlobalScope('company')
                ->where('project_id', $project->id)
                ->latest('forecast_date')
                ->limit(2)
                ->get();

            if ($latestTwo->count() < 2) {
                return;
            }

            [$latest, $previous] = $latestTwo;
            $previousCost = (float) $previous->forecast_final_cost;

            if ($previousCost <= 0) {
                return;
            }

            $changePercent = abs((float) $latest->forecast_final_cost - $previousCost) / $previousCost * 100;

            if ($changePercent < 5) {
                return;
            }

            Notification::send($users, new FinanceNotification('forecast_changed', [
                'reference' => number_format($changePercent, 1) . '%',
                'project_name' => $project->name,
            ]));

            $count++;
        });

        return $count;
    }
}
