<?php

namespace App\Filament\Resources\UserPasswordResource\Pages;

use App\Filament\Resources\UserPasswordResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserPassword extends CreateRecord
{
    protected static string $resource = UserPasswordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'User password created successfully.';
    }
}
