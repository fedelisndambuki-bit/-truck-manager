<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopDrawing extends EditRecord
{
    protected static string $resource = ShopDrawingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
