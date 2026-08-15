<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittals extends ListRecords
{
    protected static string $resource = TransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
