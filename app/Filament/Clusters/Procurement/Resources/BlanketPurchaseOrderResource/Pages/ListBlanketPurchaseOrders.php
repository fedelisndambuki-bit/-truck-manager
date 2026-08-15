<?php

namespace App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource\Pages;

use App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListBlanketPurchaseOrders extends ListRecords
{
    protected static string $resource = BlanketPurchaseOrderResource::class;

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
            'title' => 'Blanket Purchase Orders',
            'subtitle' => 'Manage standing purchase agreements with pre-set limits released against over time.',
        ]);
    }
}
