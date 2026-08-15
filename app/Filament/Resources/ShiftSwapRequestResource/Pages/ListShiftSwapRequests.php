<?php

namespace App\Filament\Resources\ShiftSwapRequestResource\Pages;

use App\Filament\Resources\ShiftSwapRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListShiftSwapRequests extends ListRecords
{
    protected static string $resource = ShiftSwapRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-arrows-right-left',
            'title' => 'Shift Swap Requests',
            'subtitle' => 'Review and approve employee requests to swap shifts.',
        ]);
    }
}
