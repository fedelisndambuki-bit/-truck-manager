<?php

namespace App\Filament\Resources\EmergencyItemResource\Pages;

use App\Filament\Resources\EmergencyItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListEmergencyItems extends ListRecords
{
    protected static string $resource = EmergencyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-shield-exclamation',
            'title' => 'Emergency Items',
            'subtitle' => 'Track emergency equipment and supplies across sites and vehicles.',
        ]);
    }
}
