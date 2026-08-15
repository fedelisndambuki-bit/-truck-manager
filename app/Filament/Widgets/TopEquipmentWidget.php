<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TopEquipmentWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top Utilized Machines';

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                Equipment::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->withSum('allocations as total_hours', 'operating_hours_today')
                    ->orderByDesc('total_hours')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('equipment_type')->badge(),
                Tables\Columns\TextColumn::make('total_hours')->label('Total Hours Logged')->default(0),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'Available' => 'success',
                    'In Use' => 'warning',
                    'Under Maintenance' => 'danger',
                    default => 'gray',
                }),
            ]);
    }
}
