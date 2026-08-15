<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\DispatchApprovalResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\DispatchApprovalResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDispatchApprovals extends ListRecords
{
    protected static string $resource = DispatchApprovalResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Dispatch Approvals',
            'subtitle' => 'Review and approve dispatch orders before vehicles proceed to loading.',
        ]);
    }
}
