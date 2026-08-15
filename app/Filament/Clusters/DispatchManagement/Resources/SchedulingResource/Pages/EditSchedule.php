<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchedule extends EditRecord
{
    protected static string $resource = SchedulingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
