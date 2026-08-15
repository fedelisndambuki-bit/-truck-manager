<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentDocuments extends ListRecords
{
    protected static string $resource = EquipmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-folder',
            'title' => 'Equipment Documents',
            'subtitle' => 'Store and manage documents related to equipment records.',
        ]);
    }
}
