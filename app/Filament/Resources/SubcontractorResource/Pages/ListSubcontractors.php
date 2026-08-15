<?php

namespace App\Filament\Resources\SubcontractorResource\Pages;

use App\Filament\Resources\SubcontractorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSubcontractors extends ListRecords
{
    protected static string $resource = SubcontractorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-briefcase',
            'title' => 'Subcontractors',
            'subtitle' => 'Manage your directory of registered subcontractors.',
        ]);
    }
}
