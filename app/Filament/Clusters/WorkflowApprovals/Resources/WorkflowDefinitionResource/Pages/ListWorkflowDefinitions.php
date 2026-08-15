<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWorkflowDefinitions extends ListRecords
{
    protected static string $resource = WorkflowDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Workflow'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-plus',
            'title' => 'Workflow Designer',
            'subtitle' => 'Design and version the approval workflows used across the system.',
        ]);
    }
}
