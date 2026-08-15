<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DispatchExceptionResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DispatchExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispatchExceptions extends ListRecords
{
    protected static string $resource = DispatchExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
