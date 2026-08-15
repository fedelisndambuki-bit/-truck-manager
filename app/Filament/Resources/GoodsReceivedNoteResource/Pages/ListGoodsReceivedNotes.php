<?php

namespace App\Filament\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log GRN'),
        ];
    }
}
