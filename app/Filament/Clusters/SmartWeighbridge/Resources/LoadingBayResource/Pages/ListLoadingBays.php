<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLoadingBays extends ListRecords
{
    protected static string $resource = LoadingBayResource::class;

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
            'title' => 'Loading Bays',
            'subtitle' => 'Manage loading bay assignments and availability.',
        ]);
    }
}
