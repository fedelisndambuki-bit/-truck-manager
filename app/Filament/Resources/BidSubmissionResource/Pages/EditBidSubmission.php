<?php

namespace App\Filament\Resources\BidSubmissionResource\Pages;

use App\Filament\Resources\BidSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBidSubmission extends EditRecord
{
    protected static string $resource = BidSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
