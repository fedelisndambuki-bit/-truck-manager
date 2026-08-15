<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipment extends ViewRecord
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Identification')->schema([
                TextEntry::make('equipment_code')->label('Equipment Number'),
                TextEntry::make('asset_number')->placeholder('—'),
                TextEntry::make('name'),
                TextEntry::make('equipment_type')->label('Type'),
                TextEntry::make('category.name')->label('Category')->placeholder('—'),
                TextEntry::make('manufacturer')->placeholder('—'),
                TextEntry::make('brand')->placeholder('—'),
                TextEntry::make('model')->placeholder('—'),
                TextEntry::make('year')->placeholder('—'),
                TextEntry::make('serial_number')->placeholder('—'),
                TextEntry::make('engine_number')->placeholder('—'),
                TextEntry::make('vin')->label('VIN')->placeholder('—'),
                TextEntry::make('registration_number')->placeholder('—'),
            ])->columns(4),

            Section::make('Organization & Location')->schema([
                TextEntry::make('department.name')->label('Department')->placeholder('—'),
                TextEntry::make('extractionSite.name')->label('Quarry / Site')->placeholder('—'),
                TextEntry::make('store.name')->label('Home Store / Depot')->placeholder('—'),
                TextEntry::make('current_location')->placeholder('—'),
            ])->columns(4),

            Section::make('Procurement & Financial')->schema([
                TextEntry::make('purchase_date')->date()->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supplier')->placeholder('—'),
                TextEntry::make('purchase_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('replacement_cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('current_value')->label('Current Book Value')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('useful_life_years')->label('Useful Life (Years)')->placeholder('—'),
                TextEntry::make('residual_value')->money(session('currency', 'KES'))->placeholder('—'),
            ])->columns(4),

            Section::make('Status & Compliance')->schema([
                TextEntry::make('status')->badge()->color(fn (string $state): string => EquipmentResource::getStatusColor($state)),
                TextEntry::make('insurance_expiry')->date()->placeholder('—'),
                TextEntry::make('warranty_expiry')->date()->placeholder('—'),
                TextEntry::make('next_calibration_date')->date()->placeholder('—'),
                TextEntry::make('engine_hours')->suffix(' hrs')->placeholder('—'),
                TextEntry::make('fuel_level')->suffix('%')->placeholder('—'),
            ])->columns(4),
        ]);
    }
}
