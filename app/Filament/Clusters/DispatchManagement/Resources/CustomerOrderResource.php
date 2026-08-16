<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;
use App\Models\CustomerOrder;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Dispatch Planning" — the genuine new upstream entity: customer order
 * capture, stock reservation, and dispatch order creation. This is where
 * the Dispatch Management chain starts, before a truck is ever involved.
 */
class CustomerOrderResource extends Resource
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

    protected static ?string $model = CustomerOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Dispatch Planning';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'material']);
    }

    public static function getPriorityOptions(): array
    {
        return array_combine(CustomerOrder::PRIORITIES, CustomerOrder::PRIORITIES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(CustomerOrder::STATUSES, CustomerOrder::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Completed' => 'success',
            'Reserved', 'Dispatched' => 'info',
            'Cancelled' => 'danger',
            default => 'gray',
        };
    }

    protected static function latestDispatchOrder(CustomerOrder $record): ?DispatchOrder
    {
        return $record->dispatchOrders()->latest('id')->first();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Customer Order')->tabs([
                Tab::make('Overview')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('order_number')->label('Order #'),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('material.name')->label('Product')->placeholder('—'),
                        TextEntry::make('quantity')->suffix(' t'),
                        TextEntry::make('priority')->badge(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                        TextEntry::make('requested_delivery_date')->label('Requested Delivery')->date('d M Y')->placeholder('—'),
                        TextEntry::make('plannedBy.full_name')->label('Planned By')->placeholder('—'),
                    ])->columns(4),
                ]),

                Tab::make('Order Details')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('customer_site')->label('Delivery Site / Address')->placeholder('—')->columnSpan(2),
                        TextEntry::make('sales_contract_number')->label('Sales Contract #')->placeholder('—'),
                        TextEntry::make('quotation_number')->label('Quotation #')->placeholder('—'),
                        TextEntry::make('product_grade')->label('Product Grade')->placeholder('—'),
                        TextEntry::make('unit_price')->label('Unit Price')->money('KES')->placeholder('—'),
                        TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                    ])->columns(3),
                ]),

                Tab::make('Reservation')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('reservation.reservation_number')->label('Reservation #')->placeholder('Not reserved'),
                        TextEntry::make('reservation.quantity_reserved')->label('Quantity Reserved')->suffix(' t')->placeholder('—'),
                        TextEntry::make('reservation.stockpile.label')->label('Stockpile')->placeholder('—'),
                        TextEntry::make('reservation.status')->label('Reservation Status')->badge()->placeholder('—'),
                        TextEntry::make('reservation.reservation_date')->label('Reserved On')->dateTime()->placeholder('—'),
                    ])->columns(3),
                ]),

                Tab::make('Dispatch Orders')->schema([
                    RepeatableEntry::make('dispatchOrders')->hiddenLabel()->schema([
                        TextEntry::make('dispatch_number')->label('Dispatch #'),
                        TextEntry::make('vehicle_registration')->label('Vehicle')->placeholder('Unassigned'),
                        TextEntry::make('net_weight')->label('Net Weight')->suffix(' t')->placeholder('—'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Truck & Driver')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('truck_vehicle')->label('Vehicle')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->vehicle_registration),
                        TextEntry::make('truck_driver')->label('Driver')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->driver_name),
                        TextEntry::make('truck_driver_phone')->label('Driver Phone')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->driver_phone),
                        TextEntry::make('truck_driver_license')->label('Driving Licence')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->driver_license_number),
                    ])->columns(4),
                ]),

                Tab::make('Weighbridge')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('wb_tare')->label('Tare Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->tare_weight),
                        TextEntry::make('wb_gross')->label('Gross Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->gross_weight),
                        TextEntry::make('wb_net')->label('Net Weight')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->net_weight),
                        TextEntry::make('wb_variance')->label('Variance')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->variance),
                        TextEntry::make('wb_status')->label('Weight Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->weight_status),
                    ])->columns(3),
                ]),

                Tab::make('Delivery')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('delivery_eta')->label('ETA')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->eta),
                        TextEntry::make('delivery_distance')->label('Distance Remaining')->suffix(' km')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->distance_remaining_km),
                        TextEntry::make('delivery_arrived')->label('Arrived At')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->arrived_at),
                        TextEntry::make('delivery_status')->label('Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->status),
                        TextEntry::make('delivery_notes')->label('Delivery Notes')->placeholder('—')->columnSpanFull()
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->delivery_notes),
                    ])->columns(4),
                ]),

                Tab::make('Proof of Delivery')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('pod_delivered')->label('Delivered Quantity')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->proofOfDelivery?->delivered_quantity),
                        TextEntry::make('pod_rejected')->label('Rejected Quantity')->suffix(' t')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->proofOfDelivery?->rejected_quantity),
                        TextEntry::make('pod_receiver')->label('Received By')->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->proofOfDelivery?->receiver_name),
                        TextEntry::make('pod_time')->label('Delivery Time')->dateTime()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->proofOfDelivery?->delivery_time),
                        TextEntry::make('pod_status')->label('Status')->badge()->placeholder('—')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->proofOfDelivery?->status),
                    ])->columns(3),
                ]),

                Tab::make('Timeline')->schema([
                    InfolistSection::make()->schema([
                        TextEntry::make('created_at')->label('Order Created')->dateTime(),
                        TextEntry::make('requested_delivery_date')->label('Requested Delivery')->date('d M Y')->placeholder('—'),
                        TextEntry::make('reservation.reservation_date')->label('Stock Reserved')->dateTime()->placeholder('Pending'),
                        TextEntry::make('dispatch_created')->label('Dispatch Order Created')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->created_at),
                        TextEntry::make('dispatch_exit')->label('Truck Departed')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->exit_at),
                        TextEntry::make('dispatch_arrived')->label('Delivered')->dateTime()->placeholder('Pending')
                            ->state(fn (CustomerOrder $record) => self::latestDispatchOrder($record)?->arrived_at),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Order')->schema([
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('customer_site')->label('Delivery Site / Address')->columnSpan(2),
                    Forms\Components\TextInput::make('sales_contract_number')->label('Sales Contract #'),
                    Forms\Components\TextInput::make('quotation_number')->label('Quotation #'),
                    Forms\Components\Select::make('planned_by_id')->label('Planned By')
                        ->relationship('plannedBy', 'full_name')->searchable()->preload(),
                ])->columns(3),

                Forms\Components\Section::make('Product & Quantity')->schema([
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('product_grade')->label('Product Grade'),
                    Forms\Components\TextInput::make('quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\TextInput::make('unit_price')->numeric()->prefix('KES'),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Normal')->required(),
                    Forms\Components\DatePicker::make('requested_delivery_date')->label('Requested Delivery Date'),
                ])->columns(3),

                Forms\Components\Section::make('Status & Notes')->schema([
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                    Forms\Components\Select::make('reservation_id')->label('Stock Reservation')
                        ->relationship('reservation', 'reservation_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->reservation_number ?? ('Reservation #' . $record->id))
                        ->searchable()->preload()->disabled(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('quantity')->suffix(' t')->sortable(),
                Tables\Columns\TextColumn::make('requested_delivery_date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('dispatchOrders_count')->label('Dispatches')->counts('dispatchOrders'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reserveStock')
                    ->label('Reserve Stock')
                    ->icon('heroicon-o-archive-box')
                    ->color('info')
                    ->visible(fn (CustomerOrder $record) => in_array($record->status, ['Draft', 'Confirmed']) && ! $record->reservation_id)
                    ->requiresConfirmation()
                    ->action(function (CustomerOrder $record) {
                        $reservation = $record->reserveStock();

                        if (! $reservation) {
                            Notification::make()->title('No stockpile with sufficient available quantity was found')->danger()->send();

                            return;
                        }

                        Notification::make()->title("Stock reserved — {$reservation->reservation_number}")->success()->send();
                    }),
                Tables\Actions\Action::make('createDispatchOrder')
                    ->label('Create Dispatch Order')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (CustomerOrder $record) => $record->status === 'Reserved')
                    ->requiresConfirmation()
                    ->action(function (CustomerOrder $record) {
                        $order = $record->createDispatchOrder();

                        Notification::make()->title("Dispatch order created — {$order->dispatch_number}")->success()->send();
                    }),
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
        return ['order_number', 'customer_site', 'sales_contract_number', 'quotation_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerOrders::route('/'),
            'create' => Pages\CreateCustomerOrder::route('/create'),
            'edit' => Pages\EditCustomerOrder::route('/{record}/edit'),
        ];
    }
}
