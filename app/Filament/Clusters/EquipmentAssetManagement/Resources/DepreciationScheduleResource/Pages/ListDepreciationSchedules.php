<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDepreciationSchedules extends ListRecords
{
    protected static string $resource = DepreciationScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-trending-down',
            'title' => 'Depreciation Schedules',
            'subtitle' => 'View and manage asset depreciation schedules over time.',
        ]);
    }
}
