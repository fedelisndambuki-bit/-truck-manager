<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionTargets extends ListRecords
{
    protected static string $resource = ProductionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-flag',
            'title' => 'Production Targets',
            'subtitle' => 'Set and track production targets against actual output.',
        ]);
    }
}
