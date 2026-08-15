<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\WorkflowDelegationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWorkflowDelegation extends CreateRecord
{
    protected static string $resource = WorkflowDelegationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()?->company_id;

        return $data;
    }
}
