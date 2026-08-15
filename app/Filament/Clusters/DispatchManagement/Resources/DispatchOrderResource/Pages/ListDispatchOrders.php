<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DispatchOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DispatchOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDispatchOrders extends ListRecords
{
    protected static string $resource = DispatchOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-truck',
            'title' => 'Dispatch Orders',
            'subtitle' => 'Coordinate and dispatch orders to trucks and drivers.',
        ]);
    }
}
