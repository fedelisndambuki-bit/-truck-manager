<?php

namespace App\Filament\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected static string $view = 'filament.resources.goods-received-note-resource.pages.list-goods-received-notes';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log GRN'),
        ];
    }
}
