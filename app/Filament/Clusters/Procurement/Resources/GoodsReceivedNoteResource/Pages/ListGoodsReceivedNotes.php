<?php

namespace App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
