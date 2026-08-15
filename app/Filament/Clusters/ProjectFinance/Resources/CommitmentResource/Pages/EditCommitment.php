<?php

namespace App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource\Pages;

use App\Filament\Clusters\ProjectFinance\Resources\CommitmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommitment extends EditRecord
{
    protected static string $resource = CommitmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
