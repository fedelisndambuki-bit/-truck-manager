<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommitments extends ListRecords
{
    protected static string $resource = CommitmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
