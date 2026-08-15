<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\ProductGradeResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\ProductGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListProductGrades extends ListRecords
{
    protected static string $resource = ProductGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-swatch',
            'title' => 'Product Grades',
            'subtitle' => 'Manage product grade definitions used across stockpiles.',
        ]);
    }
}
