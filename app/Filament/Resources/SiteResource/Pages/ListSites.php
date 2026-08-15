<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Filament\Widgets\SiteKpiWidget;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SiteKpiWidget::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-map-pin',
            'title' => 'Sites',
            'subtitle' => 'Manage all construction and quarry sites in your operation.',
        ]);
    }
}
