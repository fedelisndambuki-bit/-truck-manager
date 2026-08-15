<?php

namespace App\Filament\Resources\CorrectiveActionResource\Pages;

use App\Filament\Resources\CorrectiveActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCorrectiveActions extends ListRecords
{
    protected static string $resource = CorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-wrench-screwdriver',
            'title' => 'Corrective Actions',
            'subtitle' => 'Manage corrective actions raised from incidents, inspections, and audits.',
        ]);
    }
}
