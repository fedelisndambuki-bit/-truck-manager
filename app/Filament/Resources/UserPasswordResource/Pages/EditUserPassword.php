<?php

namespace App\Filament\Resources\UserPasswordResource\Pages;

use App\Filament\Resources\UserPasswordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserPassword extends EditRecord
{
    protected static string $resource = UserPasswordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Delete Password'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'User password updated successfully.';
    }
}
