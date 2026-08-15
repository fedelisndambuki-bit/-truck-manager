<?php

namespace App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource\Pages;

use App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlanketPurchaseOrder extends EditRecord
{
    protected static string $resource = BlanketPurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
