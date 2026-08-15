<?php

namespace App\Observers;

use App\Models\Hazard;
use App\Models\SafetySetting;
use App\Notifications\SafetyNotification;
use App\Services\SafetyNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class HazardObserver
{
    public function __construct(private readonly SafetyNotificationRecipients $recipients)
    {
    }

    public function saved(Hazard $hazard): void
    {
        $isNewOrChanged = $hazard->wasRecentlyCreated || $hazard->wasChanged('risk_rating');

        if (! $isNewOrChanged || ! in_array($hazard->risk_rating, ['High', 'Critical'], true)) {
            return;
        }

        if (! SafetySetting::getSetting($hazard->company_id, 'notify_high_risk_hazard', true)) {
            return;
        }

        $users = $this->recipients->forCompany($hazard->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new SafetyNotification('high_risk_hazard', [
            'reference' => $hazard->hazard_number,
            'site_name' => $hazard->site?->name,
            'url' => \App\Filament\Resources\HazardResource::getUrl('edit', ['record' => $hazard->id]),
        ]));
    }
}
