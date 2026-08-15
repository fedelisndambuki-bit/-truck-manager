<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages;

use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListApprovalRequests extends ListRecords
{
    protected static string $resource = ApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-inbox-stack',
            'title' => 'Approval Requests',
            'subtitle' => 'Review and action items awaiting approval across every workflow.',
        ]);
    }
}
