<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Widgets;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CertificateResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource;
use Filament\Widgets\Widget;

class LabQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Collect Sample', 'icon' => 'heroicon-o-beaker', 'url' => LabSampleResource::getUrl('create')],
            ['label' => 'New Sampling Request', 'icon' => 'heroicon-o-clipboard-document-list', 'url' => SamplingRequestResource::getUrl('create')],
            ['label' => 'Record Test', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => LabTestResource::getUrl('create')],
            ['label' => 'Place Quality Hold', 'icon' => 'heroicon-o-lock-closed', 'url' => QualityHoldResource::getUrl('create')],
            ['label' => 'Raise NCR', 'icon' => 'heroicon-o-exclamation-triangle', 'url' => NonConformanceResource::getUrl('create')],
            ['label' => 'Generate Certificate', 'icon' => 'heroicon-o-document-check', 'url' => CertificateResource::getUrl('create')],
            ['label' => 'Generate Report', 'icon' => 'heroicon-o-document-arrow-down', 'url' => \App\Filament\Clusters\LaboratoryQualityControl\Pages\Reports::getUrl()],
        ];
    }
}
