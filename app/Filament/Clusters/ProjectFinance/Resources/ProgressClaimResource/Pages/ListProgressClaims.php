<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\ProgressClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgressClaims extends ListRecords
{
    protected static string $resource = ProgressClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
