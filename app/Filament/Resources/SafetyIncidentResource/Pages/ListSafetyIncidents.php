<?php

namespace App\Filament\Resources\SafetyIncidentResource\Pages;

use App\Filament\Resources\SafetyIncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyIncidents extends ListRecords
{
    protected static string $resource = SafetyIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Safety Incidents',
            'subtitle' => 'Report and investigate safety incidents across the fleet.',
        ]);
    }
}
