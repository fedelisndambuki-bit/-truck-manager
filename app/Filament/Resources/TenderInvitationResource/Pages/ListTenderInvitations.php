<?php

namespace App\Filament\Resources\TenderInvitationResource\Pages;

use App\Filament\Resources\TenderInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderInvitations extends ListRecords
{
    protected static string $resource = TenderInvitationResource::class;

    protected static string $view = 'filament.resources.tender-invitation-resource.pages.list-tender-invitations';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Tender'),
        ];
    }
}
