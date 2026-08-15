<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLabSamples extends ListRecords
{
    protected static string $resource = LabSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-beaker',
            'title' => 'Lab Samples',
            'subtitle' => 'Track laboratory samples through collection, testing, and disposal.',
        ]);
    }
}
