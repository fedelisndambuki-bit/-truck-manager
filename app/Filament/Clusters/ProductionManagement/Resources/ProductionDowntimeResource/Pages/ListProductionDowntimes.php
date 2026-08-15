<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionDowntimes extends ListRecords
{
    protected static string $resource = ProductionDowntimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-pause-circle',
            'title' => 'Production Downtime',
            'subtitle' => 'Record and review equipment downtime, root causes, and corrective actions.',
        ]);
    }
}
