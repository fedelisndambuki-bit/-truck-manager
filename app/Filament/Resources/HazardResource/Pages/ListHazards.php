<?php

namespace App\Filament\Resources\HazardResource\Pages;

use App\Filament\Resources\HazardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListHazards extends ListRecords
{
    protected static string $resource = HazardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-fire',
            'title' => 'Hazards',
            'subtitle' => 'Identify and manage workplace hazards and their mitigation status.',
        ]);
    }
}
