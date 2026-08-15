<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductionRecords extends ListRecords
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-list',
            'title' => 'Production Records',
            'subtitle' => 'Log daily production output, yields, and equipment performance.',
        ]);
    }
}
