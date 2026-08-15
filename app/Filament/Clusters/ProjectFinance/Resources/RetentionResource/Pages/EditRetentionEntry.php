<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\RetentionResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\RetentionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetentionEntry extends EditRecord
{
    protected static string $resource = RetentionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
