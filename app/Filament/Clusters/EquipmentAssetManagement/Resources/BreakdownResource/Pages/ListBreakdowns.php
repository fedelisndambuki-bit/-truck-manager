<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\BreakdownResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBreakdowns extends ListRecords
{
    protected static string $resource = BreakdownResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Report Breakdown'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Breakdowns',
            'subtitle' => 'Log and monitor equipment breakdown incidents and their resolution.',
        ]);
    }
}
