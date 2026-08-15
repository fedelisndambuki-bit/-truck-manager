<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTransmittals extends ListRecords
{
    protected static string $resource = TransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-paper-airplane',
            'title' => 'Transmittals',
            'subtitle' => 'Track document transmittals sent and received between project parties.',
        ]);
    }
}
