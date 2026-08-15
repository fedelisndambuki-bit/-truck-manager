<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityCorrectiveActions extends ListRecords
{
    protected static string $resource = QualityCorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-wrench-screwdriver',
            'title' => 'Corrective Actions',
            'subtitle' => 'Manage corrective and preventive actions for quality issues.',
        ]);
    }
}
