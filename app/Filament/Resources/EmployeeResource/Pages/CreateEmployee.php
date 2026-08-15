<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Services\PlanLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

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
                ->body('Your current plan has reached the maximum number of drivers.')
                ->send();

            $this->halt();
        }
    }
}
