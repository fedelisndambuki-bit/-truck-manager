<?php

namespace App\Filament\Clusters\Procurement\Resources\PurchasePlanResource\Pages;

use App\Filament\Clusters\Procurement\Resources\PurchasePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPurchasePlans extends ListRecords
{
    protected static string $resource = PurchasePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-calendar-days',
            'title' => 'Purchase Planning',
            'subtitle' => 'Plan anticipated procurement needs and estimated costs ahead of the financial year.',
        ]);
    }
}
