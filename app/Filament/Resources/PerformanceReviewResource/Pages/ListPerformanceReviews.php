<?php

namespace App\Filament\Resources\PerformanceReviewResource\Pages;

use App\Filament\Resources\PerformanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListPerformanceReviews extends ListRecords
{
    protected static string $resource = PerformanceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Review'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-chart-bar-square',
            'title' => 'Performance Reviews',
            'subtitle' => 'Manage staff performance evaluations and ratings.',
        ]);
    }
}
