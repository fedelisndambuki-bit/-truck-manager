<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\DispatchOrderResource\Pages;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Dispatch Orders" — the unscoped master list/create/edit/view across the
 * full Draft -> Delivered lifecycle. This is the same `dispatch_orders`
 * table Smart Weighbridge's 7 Resources already operate on; this Resource
 * is the Dispatch Management cluster's own unscoped view of it, linked back
 * to its originating CustomerOrder. Full operational (gate/weighing/loading)
 * detail is not duplicated here — the View action links to Smart
 * Weighbridge's existing 10-tab detail page for that.
 */
class DispatchOrderResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = DispatchManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Dispatch Orders';

    protected static ?int $navigationSort = 2;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Delivered', 'Approved' => 'success',
            'Pending Approval', 'Delayed' => 'warning',
            'Rejected', 'Cancelled' => 'danger',
            'En Route', 'Exited' => 'info',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order & Customer')->schema([
                    Forms\Components\Select::make('customer_order_id')->label('Customer Order')
                        ->relationship('customerOrder', 'order_number')->searchable()->preload(),
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('delivery_address')->label('Delivery Address')->columnSpan(2),
                ])->columns(4),

                Forms\Components\Section::make('Vehicle & Driver')->schema([
                    Forms\Components\TextInput::make('vehicle_registration')->label('Vehicle Registration'),
                    Forms\Components\TextInput::make('driver_name')->label('Driver'),
                    Forms\Components\TextInput::make('driver_phone')->label('Driver Phone')->tel(),
                ])->columns(3),

                Forms\Components\Section::make('Product & Quantity')->schema([
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('product_grade'),
                    Forms\Components\TextInput::make('target_net_weight')->numeric()->suffix('t'),
                    Forms\Components\Select::make('priority')->options(array_combine(DispatchOrder::PRIORITIES, DispatchOrder::PRIORITIES))->default('Normal'),
                    Forms\Components\Select::make('status')->options(array_combine(DispatchOrder::STATUSES, DispatchOrder::STATUSES))->required(),
                ])->columns(3),

                Forms\Components\Section::make('Delivery Tracking')->schema([
                    Forms\Components\DateTimePicker::make('eta')->label('ETA'),
                    Forms\Components\TextInput::make('distance_remaining_km')->label('Distance Remaining')->numeric()->suffix('km'),
                    Forms\Components\DateTimePicker::make('arrived_at')->label('Arrived At'),
                    Forms\Components\Textarea::make('delivery_notes')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customerOrder.order_number')->label('Order #')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('target_net_weight')->label('Target')->suffix(' t'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(DispatchOrder::STATUSES, DispatchOrder::STATUSES)),
                Tables\Filters\SelectFilter::make('priority')->options(array_combine(DispatchOrder::PRIORITIES, DispatchOrder::PRIORITIES)),
            ])
            ->actions([
                Tables\Actions\Action::make('viewFullDetail')
                    ->label('Full Detail')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('gray')
                    ->url(fn (DispatchOrder $record) => VehicleCheckInResource::getUrl('view', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['dispatch_number', 'vehicle_registration', 'driver_name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatchOrders::route('/'),
            'create' => Pages\CreateDispatchOrder::route('/create'),
            'view' => Pages\ViewDispatchOrder::route('/{record}'),
            'edit' => Pages\EditDispatchOrder::route('/{record}/edit'),
        ];
    }
}
