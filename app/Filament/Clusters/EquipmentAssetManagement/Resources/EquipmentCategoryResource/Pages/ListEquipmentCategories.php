<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentCategoryResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEquipmentCategories extends ListRecords
{
    protected static string $resource = EquipmentCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-tag',
            'title' => 'Equipment Categories',
            'subtitle' => 'Organize equipment into categories for easier classification and reporting.',
        ]);
    }
}
