<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\CustomerDeliveryResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Customer Deliveries" — the internal equivalent of a customer delivery
 * portal: delivery history grouped by customer with on-time stats, visible
 * to staff. No external customer-authenticated portal is built (matching
 * this session's scoping for anything requiring new external auth).
 */
class CustomerDeliveryResource extends Resource
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

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Customer Deliveries';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('customer_id')->whereIn('status', ['Exited', 'En Route', 'Delivered', 'Delayed']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('customer_id')
            ->defaultGroup('customer.company_name')
            ->groups([
                Group::make('customer.company_name')->label('Customer')->collapsible(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('net_weight')->label('Delivered')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('exit_at')->label('Departed')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('arrived_at')->label('Arrived')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\IconColumn::make('on_time')->label('On Time')->boolean()
                    ->state(fn (DispatchOrder $record) => $record->arrived_at && $record->eta ? $record->arrived_at->lte($record->eta) : null),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Exited' => 'Exited', 'En Route' => 'En Route', 'Delivered' => 'Delivered', 'Delayed' => 'Delayed',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerDeliveries::route('/'),
            'view' => Pages\ViewCustomerDelivery::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
