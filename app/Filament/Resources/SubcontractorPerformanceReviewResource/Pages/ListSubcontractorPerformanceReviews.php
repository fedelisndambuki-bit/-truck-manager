<?php

namespace App\Filament\Resources\SubcontractorPerformanceReviewResource\Pages;

use App\Filament\Resources\SubcontractorPerformanceReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSubcontractorPerformanceReviews extends ListRecords
{
    protected static string $resource = SubcontractorPerformanceReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
