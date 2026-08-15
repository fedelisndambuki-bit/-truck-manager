<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCrushingPlant extends ViewRecord
{
    protected static string $resource = CrushingPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Plant Details')->schema([
                TextEntry::make('plant_code')->label('Plant Code')->placeholder('—'),
                TextEntry::make('name')->label('Plant Name'),
                TextEntry::make('extractionSite.name')->label('Quarry'),
                TextEntry::make('location')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => CrushingPlantResource::getStatusColor($state)),
                TextEntry::make('capacity_tph')->label('Rated Capacity')->suffix(' t/h')->placeholder('—'),
                TextEntry::make('manufacturer')->placeholder('—'),
                TextEntry::make('model')->placeholder('—'),
                TextEntry::make('serial_number')->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
            ])->columns(3),
        ]);
    }
}
