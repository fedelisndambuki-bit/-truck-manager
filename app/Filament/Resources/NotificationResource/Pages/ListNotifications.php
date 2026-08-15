<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-bell-alert',
            'title' => 'Notifications',
            'subtitle' => 'Compose and send announcements to drivers in your company.',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Notification'),
        ];
    }
}
