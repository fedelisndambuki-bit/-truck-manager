<?php

namespace App\Filament\Clusters\Finance\Resources\ProfitCentreResource\Pages;

use App\Filament\Clusters\Finance\Resources\ProfitCentreResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProfitCentre extends EditRecord
{
    protected static string $resource = ProfitCentreResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
