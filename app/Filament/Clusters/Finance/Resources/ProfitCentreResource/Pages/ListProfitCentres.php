<?php

namespace App\Filament\Clusters\Finance\Resources\ProfitCentreResource\Pages;

use App\Filament\Clusters\Finance\Resources\ProfitCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProfitCentres extends ListRecords
{
    protected static string $resource = ProfitCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chart-pie',
            'title' => 'Profit Centres',
            'subtitle' => 'Track profitability across business units, regions and projects.',
        ]);
    }
}
