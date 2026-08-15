<?php

namespace App\Filament\Resources\PerformanceReviewResource\Pages;

use App\Filament\Resources\PerformanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerformanceReviews extends ListRecords
{
    protected static string $resource = PerformanceReviewResource::class;

    protected static string $view = 'filament.resources.performance-review-resource.pages.list-performance-reviews';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Review'),
        ];
    }
}
