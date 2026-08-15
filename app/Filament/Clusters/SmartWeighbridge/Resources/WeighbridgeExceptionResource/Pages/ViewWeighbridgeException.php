<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWeighbridgeException extends ViewRecord
{
    protected static string $resource = WeighbridgeExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Exception Details')->schema([
                TextEntry::make('exception_number')->label('Exception #'),
                TextEntry::make('dispatchOrder.dispatch_number')->label('Dispatch #')->placeholder('—'),
                TextEntry::make('exception_type')->badge(),
                TextEntry::make('reason')->columnSpanFull(),
                TextEntry::make('comments')->placeholder('—')->columnSpanFull(),
                TextEntry::make('corrective_action')->placeholder('—')->columnSpanFull(),
                TextEntry::make('preventive_action')->placeholder('—')->columnSpanFull(),
                TextEntry::make('reportedBy.full_name')->label('Reported By')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => WeighbridgeExceptionResource::getStatusColor($state)),
            ])->columns(3),
        ]);
    }
}
