<?php

namespace App\Filament\Resources\MaterialDeliveryResource\Pages;

use App\Filament\Resources\MaterialDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialDelivery extends EditRecord
{
    protected static string $resource = MaterialDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
