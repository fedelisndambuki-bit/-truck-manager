<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLabEquipment extends ListRecords
{
    protected static string $resource = LabEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cog-6-tooth',
            'title' => 'Lab Equipment',
            'subtitle' => 'Manage laboratory equipment inventory and maintenance records.',
        ]);
    }
}
