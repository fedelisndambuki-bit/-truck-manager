<?php

namespace App\Filament\Resources\GoodsReceivedNoteResource\Pages;

use App\Filament\Resources\GoodsReceivedNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListGoodsReceivedNotes extends ListRecords
{
    protected static string $resource = GoodsReceivedNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log GRN'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Goods Received Notes',
            'subtitle' => 'Record and verify goods received against purchase orders.',
        ]);
    }
}
