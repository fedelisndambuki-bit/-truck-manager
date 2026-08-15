<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgressClaim extends EditRecord
{
    protected static string $resource = ProgressClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
