<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Stockpile;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class StockpileOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Stockpile Overview';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                Stockpile::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->with(['material', 'extractionSite'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Material'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Site'),
                Tables\Columns\TextColumn::make('quantity_available')
                    ->label('Available')
                    ->suffix(' t')
                    ->color(fn (Stockpile $record): string => $record->isLow() ? 'danger' : 'success')
                    ->weight(fn (Stockpile $record): string => $record->isLow() ? 'bold' : 'normal'),
            ])
            ->paginated(false);
    }
}
