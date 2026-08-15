<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCostCodes extends ListRecords
{
    protected static string $resource = CostCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-hashtag',
            'title' => 'Cost Codes',
            'subtitle' => 'Maintain the standard cost code list used to classify project expenditure.',
        ]);
    }
}
