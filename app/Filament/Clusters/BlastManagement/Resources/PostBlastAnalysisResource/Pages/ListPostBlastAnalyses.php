<?php

namespace App\Filament\Clusters\BlastManagement\Resources\PostBlastAnalysisResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\PostBlastAnalysisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPostBlastAnalyses extends ListRecords
{
    protected static string $resource = PostBlastAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-magnifying-glass',
            'title' => 'Post-Blast Analyses',
            'subtitle' => 'Review post-blast performance and outcomes against plan.',
        ]);
    }
}
