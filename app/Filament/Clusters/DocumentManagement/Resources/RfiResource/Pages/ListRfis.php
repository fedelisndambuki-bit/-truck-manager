<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\RfiResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\RfiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListRfis extends ListRecords
{
    protected static string $resource = RfiResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-question-mark-circle',
            'title' => 'RFIs',
            'subtitle' => 'Track requests for information and their responses across the project.',
        ]);
    }
}
