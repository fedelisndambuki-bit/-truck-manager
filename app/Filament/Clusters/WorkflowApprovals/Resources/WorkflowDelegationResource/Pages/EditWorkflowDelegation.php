<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowDelegation extends EditRecord
{
    protected static string $resource = WorkflowDelegationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
