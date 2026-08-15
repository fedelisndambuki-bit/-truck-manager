<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastSafetyZones extends ListRecords
{
    protected static string $resource = BlastSafetyZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-shield-exclamation',
            'title' => 'Blast Safety Zones',
            'subtitle' => 'Define and manage safety exclusion zones around blast sites.',
        ]);
    }
}
