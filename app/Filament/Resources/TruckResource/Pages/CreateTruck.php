<?php

namespace App\Filament\Resources\TruckResource\Pages;

use App\Filament\Resources\TruckResource;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTruck extends CreateRecord
{
    protected static string $resource = TruckResource::class;

    protected function beforeCreate(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->company) {
            return;
        }

        $limits = app(PlanLimitService::class);

        if (! $limits->canCreateTruck($user->company)) {
            Notification::make()
                ->title('Truck limit reached')
                ->danger()
                ->body('Your current plan has reached the maximum number of trucks.')
                ->send();

            $this->halt();
        }
    }

    // This redirects you back to the list after saving
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
