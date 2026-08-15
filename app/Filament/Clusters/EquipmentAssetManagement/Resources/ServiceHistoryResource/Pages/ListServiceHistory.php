<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\ServiceHistoryResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\ServiceHistoryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListServiceHistory extends ListRecords
{
    protected static string $resource = ServiceHistoryResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Service History',
            'subtitle' => 'Review the complete service and maintenance history for equipment.',
        ]);
    }
}
