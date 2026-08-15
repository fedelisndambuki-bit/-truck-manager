<?php

namespace App\Filament\Resources\UserPasswordResource\Pages;

use App\Filament\Resources\UserPasswordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListUserPasswords extends ListRecords
{
    protected static string $resource = UserPasswordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create Password'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-key',
            'title' => 'User Passwords',
            'subtitle' => 'Create and manage login credentials for employee user accounts.',
        ]);
    }
}
