<?php

namespace App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Clusters\Procurement\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Goods Receiving',
            'subtitle' => 'Inspect and approve delivered goods before crediting them to warehouse stock.',
        ]);
    }
}
