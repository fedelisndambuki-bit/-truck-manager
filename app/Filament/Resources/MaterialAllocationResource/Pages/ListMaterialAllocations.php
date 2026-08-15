<?php

namespace App\Filament\Resources\MaterialAllocationResource\Pages;

use App\Filament\Resources\MaterialAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialAllocations extends ListRecords
{
    protected static string $resource = MaterialAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Allocation'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-plus',
            'title' => 'Material Allocations',
            'subtitle' => 'Allocate materials from stock to projects, sites, and tasks.',
        ]);
    }
}
