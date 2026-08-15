<?php

namespace App\Filament\Resources\TenderInvitationResource\Pages;

use App\Filament\Resources\TenderInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenderInvitation extends EditRecord
{
    protected static string $resource = TenderInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
