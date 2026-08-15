<?php

namespace App\Filament\Resources\BidSubmissionResource\Pages;

use App\Filament\Resources\BidSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBidSubmissions extends ListRecords
{
    protected static string $resource = BidSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Bid'),
        ];
    }
}
