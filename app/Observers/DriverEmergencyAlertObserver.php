<?php

namespace App\Observers;

use App\Models\DriverEmergencyAlert;
use App\Notifications\DriverNotification;
use App\Services\DriverNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DriverEmergencyAlertObserver
{
    public function __construct(private readonly DriverNotificationRecipients $recipients)
    {
    }

    public function created(DriverEmergencyAlert $alert): void
    {
        if ($alert->type !== 'sos') {
            return;
        }

        $driver = $alert->driver;
        $companyId = $driver?->company_id;

        $users = $this->recipients->forCompany($companyId);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DriverNotification('emergency_sos', [
            'driver_name' => $driver?->user?->name ?? 'A driver',
            'location' => ($alert->latitude && $alert->longitude) ? "{$alert->latitude}, {$alert->longitude}" : null,
            'url' => \App\Filament\Resources\DriverEmergencyAlertResource::getUrl('index'),
        ]));
    }
}
