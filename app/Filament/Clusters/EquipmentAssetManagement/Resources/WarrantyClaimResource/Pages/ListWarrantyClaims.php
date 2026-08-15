<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WarrantyClaimResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WarrantyClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWarrantyClaims extends ListRecords
{
    protected static string $resource = WarrantyClaimResource::class;

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
            'title' => 'Warranty Claims',
            'subtitle' => 'Track and manage warranty claims filed for equipment.',
        ]);
    }
}
