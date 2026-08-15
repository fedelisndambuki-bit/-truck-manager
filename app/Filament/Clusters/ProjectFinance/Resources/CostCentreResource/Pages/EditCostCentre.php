<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CostCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCostCentre extends EditRecord
{
    protected static string $resource = CostCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
