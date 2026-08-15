<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\BoqItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoqItem extends EditRecord
{
    protected static string $resource = BoqItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
