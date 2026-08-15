<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCustomerOrders extends ListRecords
{
    protected static string $resource = CustomerOrderResource::class;

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
            'title' => 'Customer Orders',
            'subtitle' => 'Manage incoming customer orders awaiting dispatch.',
        ]);
    }
}
