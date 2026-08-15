<?php

namespace App\Filament\Clusters\Finance\Resources\ProfitCentreResource\Pages;

use App\Filament\Clusters\Finance\Resources\ProfitCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProfitCentres extends ListRecords
{
    protected static string $resource = ProfitCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
