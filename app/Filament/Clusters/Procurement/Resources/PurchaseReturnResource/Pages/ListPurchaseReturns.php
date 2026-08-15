<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchaseReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPurchaseReturns extends ListRecords
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-uturn-left',
            'title' => 'Returns',
            'subtitle' => 'Manage goods returned to suppliers and their replacement or credit status.',
        ]);
    }
}
