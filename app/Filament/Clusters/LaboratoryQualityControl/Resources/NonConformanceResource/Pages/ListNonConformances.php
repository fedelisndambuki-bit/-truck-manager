<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListNonConformances extends ListRecords
{
    protected static string $resource = NonConformanceResource::class;

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
            'title' => 'Non-Conformances',
            'subtitle' => 'Log and resolve quality non-conformance incidents.',
        ]);
    }
}
