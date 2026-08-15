<?php

namespace App\Filament\Clusters\Procurement\Resources\SupplierContractResource\Pages;

use App\Filament\Clusters\Procurement\Resources\SupplierContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSupplierContracts extends ListRecords
{
    protected static string $resource = SupplierContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Contracts',
            'subtitle' => 'Manage supplier contracts, service levels and renewal dates.',
        ]);
    }
}
