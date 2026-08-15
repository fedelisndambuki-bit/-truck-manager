<?php

namespace App\Filament\Resources\MaterialConsumptionResource\Pages;

use App\Filament\Resources\MaterialConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialConsumptions extends ListRecords
{
    protected static string $resource = MaterialConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Record Consumption'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-fire',
            'title' => 'Material Consumption',
            'subtitle' => 'Track material usage and consumption across active projects.',
        ]);
    }
}
