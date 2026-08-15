<?php

namespace App\Filament\Widgets\Quarry;

use App\Filament\Resources\WeighbridgeTicketResource;
use App\Models\WeighbridgeTicket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class WeighbridgeQueueWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Weighbridge / Dispatch Queue';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                WeighbridgeTicket::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->with(['customer', 'material'])
                    ->whereDate('created_at', today())
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->label('Ticket #'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                Tables\Columns\TextColumn::make('direction')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => WeighbridgeTicketResource::getStatusColor($state)),
            ])
            ->paginated(false);
    }
}
