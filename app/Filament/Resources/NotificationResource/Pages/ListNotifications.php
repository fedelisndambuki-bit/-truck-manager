<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    protected static string $view = 'filament.resources.notification-resource.pages.list-notifications';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Notification'),
        ];
    }
}
