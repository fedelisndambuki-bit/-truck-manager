<?php

namespace App\Filament\Resources\MaterialWastageResource\Pages;

use App\Filament\Resources\MaterialWastageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialWastages extends ListRecords
{
    protected static string $resource = MaterialWastageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Wastage'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-trash',
            'title' => 'Material Wastage',
            'subtitle' => 'Track and log wasted or damaged materials across warehouses.',
        ]);
    }
}
