<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowDefinitions extends ListRecords
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Workflow'),
        ];
    }
}
