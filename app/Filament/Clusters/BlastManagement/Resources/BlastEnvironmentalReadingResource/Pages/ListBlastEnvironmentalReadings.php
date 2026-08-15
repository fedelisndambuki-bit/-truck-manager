<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastEnvironmentalReadings extends ListRecords
{
    protected static string $resource = BlastEnvironmentalReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-globe-alt',
            'title' => 'Blast Environmental Readings',
            'subtitle' => 'Monitor environmental readings such as vibration, noise, and dust from blasting activity.',
        ]);
    }
}
