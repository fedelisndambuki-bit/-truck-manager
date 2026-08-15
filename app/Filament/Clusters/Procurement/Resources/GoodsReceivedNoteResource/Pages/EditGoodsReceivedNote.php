<?php

namespace App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceivedNote extends EditRecord
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
