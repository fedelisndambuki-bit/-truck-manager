<?php

namespace App\Filament\Resources\TenderInvitationResource\Pages;

use App\Filament\Resources\TenderInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderInvitations extends ListRecords
{
    protected static string $resource = TenderInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Tender'),
        ];
    }
}
