<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlastDrillHoles extends ListRecords
{
    protected static string $resource = BlastDrillHoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cog-8-tooth',
            'title' => 'Blast Drill Holes',
            'subtitle' => 'Record and manage drill hole data for blast planning and execution.',
        ]);
    }
}
