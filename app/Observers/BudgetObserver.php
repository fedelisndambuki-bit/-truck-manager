<?php

namespace App\Observers;

use App\Filament\Clusters\ProjectFinance\Resources\BudgetResource;
use App\Models\ProjectBudget;
use App\Models\ProjectFinanceSetting;
use App\Notifications\FinanceNotification;
use App\Services\FinanceNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class BudgetObserver
{
    public function __construct(private readonly FinanceNotificationRecipients $recipients)
    {
    }

    public function saved(ProjectBudget $budget): void
    {
        if (! $budget->wasChanged(['actual_cost', 'approved_budget', 'revised_budget'])) {
            return;
        }

        if (! ProjectFinanceSetting::getSetting($budget->company_id, 'notify_budget_exceeded', true)) {
            return;
        }

        $effectiveBudget = (float) ($budget->revised_budget > 0 ? $budget->revised_budget : $budget->approved_budget);

        if ($effectiveBudget <= 0 || (float) $budget->actual_cost <= $effectiveBudget) {
            return;
        }

        $users = $this->recipients->forCompany($budget->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new FinanceNotification('budget_exceeded', [
            'reference' => $budget->budget_number,
            'project_name' => $budget->project?->name,
            'url' => BudgetResource::getUrl('edit', ['record' => $budget->id]),
        ]));
    }
}
