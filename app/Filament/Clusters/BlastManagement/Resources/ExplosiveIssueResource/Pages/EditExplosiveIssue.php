<?php

namespace App\Filament\Clusters\BlastManagement\Resources\ExplosiveIssueResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\ExplosiveIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExplosiveIssue extends EditRecord
{
    protected static string $resource = ExplosiveIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
