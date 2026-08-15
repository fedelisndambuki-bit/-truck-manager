<?php

namespace App\Filament\Resources\SiteInspectionResource\Pages;

use App\Filament\Resources\SiteInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSiteInspections extends ListRecords
{
    protected static string $resource = SiteInspectionResource::class;

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
            'title' => 'Site Inspections',
            'subtitle' => 'Track site inspection records and compliance findings.',
        ]);
    }
}
