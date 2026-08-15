<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityReleaseResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityReleaseResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityReleases extends ListRecords
{
    protected static string $resource = QualityReleaseResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-lock-open',
            'title' => 'Quality Releases',
            'subtitle' => 'Manage the release of held items following quality clearance.',
        ]);
    }
}
