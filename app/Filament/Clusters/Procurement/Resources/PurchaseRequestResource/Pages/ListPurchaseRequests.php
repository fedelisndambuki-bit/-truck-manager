<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseRequestResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPurchaseRequests extends ListRecords
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Requisition'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-plus',
            'title' => 'Purchase Requisitions',
            'subtitle' => 'Review and route purchase requests raised across departments.',
        ]);
    }
}
