<?php

namespace App\Filament\Resources\DisciplinaryCaseResource\Pages;

use App\Filament\Resources\DisciplinaryCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDisciplinaryCases extends ListRecords
{
    protected static string $resource = DisciplinaryCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Disciplinary Cases',
            'subtitle' => 'Record and track employee disciplinary cases and outcomes.',
        ]);
    }
}
