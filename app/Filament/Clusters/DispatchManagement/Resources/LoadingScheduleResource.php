<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\LoadingScheduleResource\Pages;
use App\Filament\Clusters\SmartWeighbridge\Resources\LoadingBayResource;
use App\Models\LoadingBay;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Loading Schedule" — a scheduling-focused read view over the same
 * LoadingBay data Smart Weighbridge's LoadingBayResource already manages
 * (bay CRUD itself stays there); this shows queue position, current truck,
 * and real average loading time so planners can see where to route trucks.
 */
class LoadingScheduleResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $model = LoadingBay::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Loading Schedule';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['stockpile']);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Available' => 'success',
            'Busy' => 'warning',
            'Maintenance' => 'gray',
            'Closed' => 'danger',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bay_code')->label('Bay #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('currentTruck')
                    ->label('Current Truck')
                    ->state(fn (LoadingBay $record) => $record->currentTruck()?->vehicle_registration ?? '—'),
                Tables\Columns\TextColumn::make('queue_count')
                    ->label('Queue Length')
                    ->state(fn (LoadingBay $record) => $record->currentQueueCount()),
                Tables\Columns\TextColumn::make('avg_loading_minutes')
                    ->label('Avg Loading Time')
                    ->state(fn (LoadingBay $record) => $record->averageLoadingTimeMinutes() . ' min'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LoadingBay::STATUSES, LoadingBay::STATUSES)),
            ])
            ->actions([
                Tables\Actions\Action::make('manageBay')
                    ->label('Manage')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (LoadingBay $record) => LoadingBayResource::getUrl('edit', ['record' => $record])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoadingSchedules::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
