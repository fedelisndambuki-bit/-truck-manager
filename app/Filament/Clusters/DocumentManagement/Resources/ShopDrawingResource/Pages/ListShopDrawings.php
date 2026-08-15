<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ShopDrawingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListShopDrawings extends ListRecords
{
    protected static string $resource = ShopDrawingResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-2x2',
            'title' => 'Shop Drawings',
            'subtitle' => 'Track shop drawings through consultant and client approval stages.',
        ]);
    }
}
