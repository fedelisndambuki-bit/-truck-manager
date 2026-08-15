<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\AssetRegisterResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\AssetRegisterResource;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetRegister extends ViewRecord
{
    protected static string $resource = AssetRegisterResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Asset Identification')->schema([
                TextEntry::make('equipment_code')->label('Equipment Number'),
                TextEntry::make('asset_number')->placeholder('—'),
                TextEntry::make('name'),
                TextEntry::make('category.name')->label('Category')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => EquipmentResource::getStatusColor($state)),
            ])->columns(4),

            Section::make('Cost Basis & Valuation')->schema([
                TextEntry::make('purchase_date')->date()->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supplier')->placeholder('—'),
                TextEntry::make('purchase_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('replacement_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('current_value')->label('Current Book Value')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('useful_life_years')->label('Useful Life (Years)')->placeholder('—'),
                TextEntry::make('residual_value')->money(session('currency', 'KES'))->placeholder('—'),
            ])->columns(4),
        ]);
    }
}
