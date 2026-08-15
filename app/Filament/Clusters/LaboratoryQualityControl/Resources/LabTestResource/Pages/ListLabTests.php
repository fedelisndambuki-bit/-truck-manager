<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLabTests extends ListRecords
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Lab Tests',
            'subtitle' => 'Record and monitor laboratory test results and outcomes.',
        ]);
    }
}
