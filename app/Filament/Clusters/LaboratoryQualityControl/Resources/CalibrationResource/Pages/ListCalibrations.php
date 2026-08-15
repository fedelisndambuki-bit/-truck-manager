<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CalibrationResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CalibrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCalibrations extends ListRecords
{
    protected static string $resource = CalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-adjustments-horizontal',
            'title' => 'Calibrations',
            'subtitle' => 'Track equipment calibration schedules and compliance status.',
        ]);
    }
}
