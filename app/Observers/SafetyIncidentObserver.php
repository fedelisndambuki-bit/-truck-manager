<?php

namespace App\Observers;

use App\Models\SafetyIncident;
use App\Models\SafetySetting;
use App\Notifications\SafetyNotification;
use App\Services\SafetyNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class SafetyIncidentObserver
{
    public function __construct(private readonly SafetyNotificationRecipients $recipients)
    {
    }

    public function created(SafetyIncident $incident): void
    {
        if (! SafetySetting::getSetting($incident->company_id, 'notify_incident_reported', true)) {
            return;
        }

        $users = $this->recipients->forCompany($incident->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new SafetyNotification('incident_reported', [
            'reference' => $incident->incident_number,
            'site_name' => $incident->site?->name,
            'url' => \App\Filament\Resources\SafetyIncidentResource::getUrl('edit', ['record' => $incident->id]),
        ]));
    }
}
