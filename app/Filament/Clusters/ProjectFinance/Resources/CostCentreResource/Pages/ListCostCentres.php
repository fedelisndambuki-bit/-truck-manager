<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCostCentres extends ListRecords
{
    protected static string $resource = CostCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-office',
            'title' => 'Cost Centres',
            'subtitle' => 'Organize project costs by cost centre for reporting and control.',
        ]);
    }
}
