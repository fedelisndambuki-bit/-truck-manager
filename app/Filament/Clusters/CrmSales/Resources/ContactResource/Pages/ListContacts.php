<?php

namespace App\Filament\Clusters\CrmSales\Resources\ContactResource\Pages;

use App\Filament\Clusters\CrmSales\Resources\ContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-identification',
            'title' => 'Contacts',
            'subtitle' => 'Manage the people you deal with at each client company.',
        ]);
    }
}
