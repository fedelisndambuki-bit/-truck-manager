<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTechnicalSubmittals extends ListRecords
{
    protected static string $resource = TechnicalSubmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-cube',
            'title' => 'Technical Submittals',
            'subtitle' => 'Track material and product submittals awaiting review and approval.',
        ]);
    }
}
