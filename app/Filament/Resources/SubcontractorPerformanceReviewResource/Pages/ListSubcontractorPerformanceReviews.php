<?php

namespace App\Filament\Resources\SubcontractorPerformanceReviewResource\Pages;

use App\Filament\Resources\SubcontractorPerformanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListSubcontractorPerformanceReviews extends ListRecords
{
    protected static string $resource = SubcontractorPerformanceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-star',
            'title' => 'Subcontractor Performance Reviews',
            'subtitle' => 'Track and evaluate subcontractor performance across projects.',
        ]);
    }
}
