<?php

namespace App\Filament\Resources\SafetyInspectionResource\Pages;

use App\Filament\Resources\SafetyInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSafetyInspections extends ListRecords
{
    protected static string $resource = SafetyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-clipboard-document-check',
            'title' => 'Safety Inspections',
            'subtitle' => 'Schedule and record safety inspections across sites and vehicles.',
        ]);
    }
}
