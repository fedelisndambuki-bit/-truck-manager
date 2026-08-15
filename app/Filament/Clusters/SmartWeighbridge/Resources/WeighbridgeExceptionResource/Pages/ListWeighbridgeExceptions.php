<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListWeighbridgeExceptions extends ListRecords
{
    protected static string $resource = WeighbridgeExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Weighbridge Exceptions',
            'subtitle' => 'Log and resolve exceptions encountered during weighbridge operations.',
        ]);
    }
}
