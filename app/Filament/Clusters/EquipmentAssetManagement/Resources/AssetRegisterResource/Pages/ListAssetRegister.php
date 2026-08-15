<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\AssetRegisterResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\AssetRegisterResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListAssetRegister extends ListRecords
{
    protected static string $resource = AssetRegisterResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-banknotes',
            'title' => 'Asset Register',
            'subtitle' => 'Track the full register of company assets and their financial details.',
        ]);
    }
}
