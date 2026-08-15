<?php

namespace App\Observers;

use App\Filament\Clusters\ProjectFinance\Resources\ChangeOrderResource;
use App\Models\ChangeOrder;
use App\Models\ProjectFinanceSetting;
use App\Notifications\FinanceNotification;
use App\Services\FinanceNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class ChangeOrderObserver
{
    public function __construct(private readonly FinanceNotificationRecipients $recipients)
    {
    }

    public function saved(ChangeOrder $changeOrder): void
    {
        if ($changeOrder->status !== 'Submitted' || ! $changeOrder->wasChanged('status')) {
            return;
        }

        if (! ProjectFinanceSetting::getSetting($changeOrder->company_id, 'notify_variation_submitted', true)) {
            return;
        }

        $users = $this->recipients->forCompany($changeOrder->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new FinanceNotification('variation_submitted', [
            'reference' => $changeOrder->co_number,
            'project_name' => $changeOrder->project?->name,
            'url' => ChangeOrderResource::getUrl('edit', ['record' => $changeOrder->id]),
        ]));
    }
}
