<?php

namespace App\Filament\Resources\WeighbridgeTicketResource\Pages;

use App\Filament\Resources\WeighbridgeTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeighbridgeTickets extends ListRecords
{
    protected static string $resource = WeighbridgeTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
