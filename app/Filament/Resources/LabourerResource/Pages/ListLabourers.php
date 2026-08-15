<?php

namespace App\Filament\Resources\LabourerResource\Pages;

use App\Filament\Resources\LabourerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListLabourers extends ListRecords
{
    protected static string $resource = LabourerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-user-group',
            'title' => 'Labourers',
            'subtitle' => 'Manage labourer records and work assignments.',
        ]);
    }
}
