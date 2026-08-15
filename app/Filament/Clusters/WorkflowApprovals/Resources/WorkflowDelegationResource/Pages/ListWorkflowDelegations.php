<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowDelegations extends ListRecords
{
    protected static string $resource = WorkflowDelegationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Delegation'),
        ];
    }
}
