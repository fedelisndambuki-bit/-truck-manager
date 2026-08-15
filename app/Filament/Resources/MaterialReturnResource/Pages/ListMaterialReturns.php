<?php

namespace App\Filament\Resources\MaterialReturnResource\Pages;

use App\Filament\Resources\MaterialReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListMaterialReturns extends ListRecords
{
    protected static string $resource = MaterialReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Return'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrow-uturn-left',
            'title' => 'Material Returns',
            'subtitle' => 'Manage materials returned to stock from sites and projects.',
        ]);
    }
}
