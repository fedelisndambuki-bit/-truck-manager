<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTrips extends BaseWidget
{
    protected static ?int $sort = 2; // Position below the stats

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Trip::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('truck.plate_number')->label('Truck'),
                Tables\Columns\TextColumn::make('client.company_name')->label('Client'),
                Tables\Columns\TextColumn::make('route_name'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'In Progress' => 'warning',
                        'Completed' => 'success',
                        'Scheduled' => 'gray',
                        default => 'danger',
                    }),
            ]);
    }
}
