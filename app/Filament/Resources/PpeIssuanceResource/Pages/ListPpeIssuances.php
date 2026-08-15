<?php

namespace App\Filament\Resources\PpeIssuanceResource\Pages;

use App\Filament\Resources\PpeIssuanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPpeIssuances extends ListRecords
{
    protected static string $resource = PpeIssuanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Issue PPE'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-shield-check',
            'title' => 'PPE Issuance',
            'subtitle' => 'Track personal protective equipment issued to staff.',
        ]);
    }
}
