<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWorkflowDelegations extends ListRecords
{
    protected static string $resource = WorkflowDelegationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Delegation'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-group',
            'title' => 'Delegations',
            'subtitle' => 'Delegate approval authority to another user for a set period.',
        ]);
    }
}
