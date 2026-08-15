<?php

namespace App\Filament\Resources\NotificationSettingResource\Pages;

use App\Filament\Resources\NotificationSettingResource;
use App\Models\NotificationSetting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords; // ManageRecords is a good base for single record redirect
use Illuminate\Contracts\View\View;

class ManageNotificationSettings extends ManageRecords
{
    protected static string $resource = NotificationSettingResource::class;

    public function mount(): void
    {
        $record = NotificationSetting::first();

        if (!$record) {
            $record = NotificationSetting::create([
                'email_notifications' => false,
                'sms_alerts' => false,
                'trip_completion_alerts' => false,
                'settlement_generated_alerts' => false,
                'payment_status_alerts' => false,
            ]);
        }

        $this->redirect(NotificationSettingResource::getUrl('edit', ['record' => $record->getKey()]));
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-bell',
            'title' => 'Notification Settings',
            'subtitle' => 'Configure which alerts and notifications your company receives.',
        ]);
    }
}