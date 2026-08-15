<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseRequestResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
