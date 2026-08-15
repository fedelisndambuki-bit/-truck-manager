<?php

namespace App\Filament\Resources\WeighbridgeTicketResource\Pages;

use App\Filament\Resources\WeighbridgeTicketResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWeighbridgeTicket extends ViewRecord
{
    protected static string $resource = WeighbridgeTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Ticket Details')->schema([
                TextEntry::make('ticket_number')->label('Ticket #'),
                TextEntry::make('extractionSite.name')->label('Site'),
                TextEntry::make('direction')->badge(),
                TextEntry::make('status')->badge()->color(fn (string $state): string => WeighbridgeTicketResource::getStatusColor($state)),
                TextEntry::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                TextEntry::make('driver_name')->label('Driver')->placeholder('—'),
                TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                TextEntry::make('material.name')->label('Material')->placeholder('—'),
            ])->columns(2),
            Section::make('Weighing')->schema([
                TextEntry::make('gross_weight')->suffix(' kg')->placeholder('—'),
                TextEntry::make('tare_weight')->suffix(' kg')->placeholder('—'),
                TextEntry::make('net_weight')->suffix(' kg')->placeholder('—'),
                TextEntry::make('gate_in_at')->dateTime()->placeholder('—'),
                TextEntry::make('gate_out_at')->dateTime()->placeholder('—'),
                TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
            ])->columns(3),
        ]);
    }
}
