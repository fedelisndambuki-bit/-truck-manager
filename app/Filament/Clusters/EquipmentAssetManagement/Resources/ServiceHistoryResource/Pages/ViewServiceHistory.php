<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\ServiceHistoryResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\ServiceHistoryResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceHistory extends ViewRecord
{
    protected static string $resource = ServiceHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Add Follow-up'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Service Record')->schema([
                TextEntry::make('equipment.name')->label('Equipment'),
                TextEntry::make('maintenance_type')->label('Type')->badge(),
                TextEntry::make('maintenance_date')->date(),
                TextEntry::make('technician.full_name')->label('Technician')->placeholder('—'),
                TextEntry::make('workOrder.work_order_number')->label('Work Order')->placeholder('—'),
            ])->columns(3),

            Section::make('Cost & Downtime')->schema([
                TextEntry::make('cost')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('downtime_hours')->suffix(' hrs')->placeholder('—'),
                TextEntry::make('warranty_covered')->label('Warranty Covered')->badge()->formatStateUsing(fn (bool $state) => $state ? 'Yes' : 'No')->color(fn (bool $state) => $state ? 'success' : 'gray'),
            ])->columns(3),

            Section::make('Details')->schema([
                TextEntry::make('description')->columnSpanFull()->placeholder('—'),
                TextEntry::make('recommendations')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }
}
