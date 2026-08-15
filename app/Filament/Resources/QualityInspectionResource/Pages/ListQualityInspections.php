<?php

namespace App\Filament\Resources\QualityInspectionResource\Pages;

use App\Filament\Resources\QualityInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListQualityInspections extends ListRecords
{
    protected static string $resource = QualityInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-check-badge',
            'title' => 'Quality Inspections',
            'subtitle' => 'Track quality inspection records and results.',
        ]);
    }
}
