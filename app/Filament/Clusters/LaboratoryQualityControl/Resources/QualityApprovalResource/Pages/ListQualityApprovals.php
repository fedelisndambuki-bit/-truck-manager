<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityApprovalResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityApprovalResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityApprovals extends ListRecords
{
    protected static string $resource = QualityApprovalResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Quality Approvals',
            'subtitle' => 'Review and approve quality control decisions before release.',
        ]);
    }
}
