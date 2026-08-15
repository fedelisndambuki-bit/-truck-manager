<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTestMethods extends ListRecords
{
    protected static string $resource = TestMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-document-text',
            'title' => 'Test Methods',
            'subtitle' => 'Maintain standardized test methods used in laboratory analysis.',
        ]);
    }
}
