<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionPlanResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionPlans extends ListRecords
{
    protected static string $resource = ProductionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Production Planning',
            'subtitle' => 'Plan and schedule extraction and crushing operations across your quarries.',
        ]);
    }
}
