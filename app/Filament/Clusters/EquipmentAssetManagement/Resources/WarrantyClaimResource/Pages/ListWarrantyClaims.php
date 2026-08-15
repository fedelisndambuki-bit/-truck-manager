<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WarrantyClaimResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WarrantyClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarrantyClaims extends ListRecords
{
    protected static string $resource = WarrantyClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
