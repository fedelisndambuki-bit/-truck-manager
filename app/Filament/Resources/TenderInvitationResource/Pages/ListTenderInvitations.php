<?php

namespace App\Filament\Resources\TenderInvitationResource\Pages;

use App\Filament\Resources\TenderInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListTenderInvitations extends ListRecords
{
    protected static string $resource = TenderInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Tender'),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-megaphone',
            'title' => 'Tender Invitations',
            'subtitle' => 'Manage tender invitations issued to prospective subcontractors.',
        ]);
    }
}
