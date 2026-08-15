<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-check',
            'title' => 'Purchase Orders',
            'subtitle' => 'Create, approve and track purchase orders issued to suppliers.',
        ]);
    }
}
