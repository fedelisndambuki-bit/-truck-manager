<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityHolds extends ListRecords
{
    protected static string $resource = QualityHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-lock-closed',
            'title' => 'Quality Holds',
            'subtitle' => 'Track items placed on hold pending quality investigation.',
        ]);
    }
}
