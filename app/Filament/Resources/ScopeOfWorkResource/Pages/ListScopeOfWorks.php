<?php

namespace App\Filament\Resources\ScopeOfWorkResource\Pages;

use App\Filament\Resources\ScopeOfWorkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListScopeOfWorks extends ListRecords
{
    protected static string $resource = ScopeOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Define Scope'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-list-bullet',
            'title' => 'Scopes of Work',
            'subtitle' => 'Define and manage the scope of work for each contract.',
        ]);
    }
}
