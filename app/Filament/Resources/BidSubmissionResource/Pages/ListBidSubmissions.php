<?php

namespace App\Filament\Resources\BidSubmissionResource\Pages;

use App\Filament\Resources\BidSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBidSubmissions extends ListRecords
{
    protected static string $resource = BidSubmissionResource::class;

    protected static string $view = 'filament.resources.bid-submission-resource.pages.list-bid-submissions';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Bid'),
        ];
    }
}
