<?php

namespace App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListFragmentationAnalyses extends ListRecords
{
    protected static string $resource = FragmentationAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-squares-plus',
            'title' => 'Fragmentation Analyses',
            'subtitle' => 'Analyze rock fragmentation results from completed blasts.',
        ]);
    }
}
