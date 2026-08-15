<?php

namespace App\Filament\Resources\MaterialDeliveryResource\Pages;

use App\Filament\Resources\MaterialDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialDeliveries extends ListRecords
{
    protected static string $resource = MaterialDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Delivery'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-truck',
            'title' => 'Material Deliveries',
            'subtitle' => 'Track incoming material deliveries from suppliers.',
        ]);
    }
}
