<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentInspectionResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentInspections extends ListRecords
{
    protected static string $resource = EquipmentInspectionResource::class;

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
            'title' => 'Equipment Inspections',
            'subtitle' => 'Record and track equipment inspection results and compliance.',
        ]);
    }
}
