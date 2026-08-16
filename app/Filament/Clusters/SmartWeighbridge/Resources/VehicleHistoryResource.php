<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleHistoryResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * 6th Resource over DispatchOrder — complete vehicle trip history, grouped
 * by vehicle registration.
 */
class VehicleHistoryResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = SmartWeighbridge::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Vehicle History';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'vehicle-history';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('vehicle_registration')
            ->columns([
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('net_weight')->label('Net Weight')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('turnaround')
                    ->label('Turnaround')
                    ->state(fn (DispatchOrder $record) => $record->turnaroundMinutes() !== null ? $record->turnaroundMinutes() . ' min' : '—'),
                Tables\Columns\TextColumn::make('variance_percent')->label('Weight Accuracy')
                    ->state(fn (DispatchOrder $record) => $record->variance_percent !== null ? (100 - abs((float) $record->variance_percent)) . '%' : '—'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
            ])
            ->groups([
                Tables\Grouping\Group::make('vehicle_registration')->label('Vehicle'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')->label('Customer')->relationship('customer', 'company_name'),
                Tables\Filters\SelectFilter::make('material_id')->label('Product')->relationship('material', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist(fn (Infolist $infolist) => VehicleCheckInResource::infolist($infolist)),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'material']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleHistory::route('/'),
        ];
    }
}
