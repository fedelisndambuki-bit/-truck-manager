<?php

namespace App\Observers;

use App\Models\DailySiteDiary;
use App\Models\DiarySetting;
use App\Notifications\DiaryNotification;
use App\Services\DiaryNotificationRecipients;
use Illuminate\Support\Facades\Notification;

class DailySiteDiaryObserver
{
    public function __construct(private readonly DiaryNotificationRecipients $recipients)
    {
    }

    public function saved(DailySiteDiary $diary): void
    {
        $companyId = $diary->company_id;
        $isNewOrChanged = fn (string $field) => $diary->wasRecentlyCreated || $diary->wasChanged($field);

        if (DiarySetting::getSetting($companyId, 'notify_severe_weather', true) && $isNewOrChanged('weather_impact') && $diary->weather_impact === 'Severe') {
            $this->notify($diary, 'severe_weather');
        }

        if (DiarySetting::getSetting($companyId, 'notify_equipment_breakdown', true) && $isNewOrChanged('equipment_breakdown_count') && $diary->equipment_breakdown_count > 0) {
            $this->notify($diary, 'equipment_breakdown');
        }

        if (DiarySetting::getSetting($companyId, 'notify_material_shortage', true) && $isNewOrChanged('material_shortages_count') && $diary->material_shortages_count > 0) {
            $this->notify($diary, 'material_shortage');
        }
    }

    private function notify(DailySiteDiary $diary, string $type): void
    {
        $users = $this->recipients->forCompany($diary->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new DiaryNotification($type, [
            'diary_id' => $diary->id,
            'diary_number' => $diary->diary_number,
            'site_name' => $diary->site?->name,
        ]));
    }
}
