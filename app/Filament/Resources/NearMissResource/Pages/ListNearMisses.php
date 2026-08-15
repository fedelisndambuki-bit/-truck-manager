<?php

namespace App\Filament\Resources\NearMissResource\Pages;

use App\Filament\Resources\NearMissResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListNearMisses extends ListRecords
{
    protected static string $resource = NearMissResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-eye',
            'title' => 'Near Misses',
            'subtitle' => 'Log and review near-miss events to prevent future incidents.',
        ]);
    }
}
