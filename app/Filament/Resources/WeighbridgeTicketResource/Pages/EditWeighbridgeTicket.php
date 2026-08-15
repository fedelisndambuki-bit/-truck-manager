<?php

namespace App\Filament\Resources\WeighbridgeTicketResource\Pages;

use App\Filament\Resources\WeighbridgeTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeighbridgeTicket extends EditRecord
{
    protected static string $resource = WeighbridgeTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
