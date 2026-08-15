<?php

namespace App\Filament\Resources\ExtractionSiteResource\Pages;

use App\Filament\Resources\ExtractionSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListExtractionSites extends ListRecords
{
    protected static string $resource = ExtractionSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-map',
            'title' => 'Extraction Sites',
            'subtitle' => 'Manage quarry extraction sites and monitor their estimated material reserves.',
        ]);
    }
}
