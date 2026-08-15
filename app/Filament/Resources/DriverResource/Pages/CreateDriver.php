<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }

    protected function beforeCreate(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->company) {
            return;
        }

        $limits = app(PlanLimitService::class);

        if (! $limits->canCreateDriver($user->company)) {
            Notification::make()
                ->title('Driver limit reached')
                ->danger()
                ->body('Your current plan has reached the maximum number of driver accounts.')
                ->send();

            $this->halt();
        }
    }
}
