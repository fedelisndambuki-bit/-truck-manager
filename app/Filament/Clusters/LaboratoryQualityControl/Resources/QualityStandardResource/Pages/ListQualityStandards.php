<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityStandards extends ListRecords
{
    protected static string $resource = QualityStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-shield-check',
            'title' => 'Quality Standards',
            'subtitle' => 'Define and maintain the quality standards used across operations.',
        ]);
    }
}
