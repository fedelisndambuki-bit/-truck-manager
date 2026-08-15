<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListApprovalRequests extends ListRecords
{
    protected static string $resource = ApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
