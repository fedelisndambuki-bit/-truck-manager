<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWeighbridges extends ListRecords
{
    protected static string $resource = WeighbridgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-scale',
            'title' => 'Weighbridges',
            'subtitle' => 'Configure and manage weighbridge stations.',
        ]);
    }
}
