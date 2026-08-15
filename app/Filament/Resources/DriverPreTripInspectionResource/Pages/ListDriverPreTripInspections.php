<?php

namespace App\Filament\Resources\DriverPreTripInspectionResource\Pages;

use App\Filament\Resources\DriverPreTripInspectionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDriverPreTripInspections extends ListRecords
{
    protected static string $resource = DriverPreTripInspectionResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Pre-Trip Inspections',
            'subtitle' => 'Review driver pre-trip vehicle inspection reports.',
        ]);
    }
}
