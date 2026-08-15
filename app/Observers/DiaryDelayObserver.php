<?php

namespace App\Observers;

use App\Models\DiaryDelay;
use App\Models\DiarySetting;
use App\Notifications\DiaryNotification;
use App\Services\DiaryNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DiaryDelayObserver
{
    public function __construct(private readonly DiaryNotificationRecipients $recipients)
    {
    }

    public function saved(DiaryDelay $delay): void
    {
        if (! in_array($delay->impact, ['High', 'Critical']) || ! ($delay->wasRecentlyCreated || $delay->wasChanged('impact'))) {
            return;
        }

        $diary = $delay->diary;

        if (! $diary || ! DiarySetting::getSetting($diary->company_id, 'notify_high_delay', true)) {
            return;
        }

        $users = $this->recipients->forCompany($diary->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DiaryNotification('high_delay', [
            'diary_id' => $diary->id,
            'diary_number' => $diary->diary_number,
            'site_name' => $diary->site?->name,
        ]));
    }
}
