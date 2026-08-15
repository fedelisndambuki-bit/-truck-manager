<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource\Pages;
use App\Models\CustomerOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
            'view' => Pages\ViewCustomerOrder::route('/{record}'),
            'edit' => Pages\EditCustomerOrder::route('/{record}/edit'),
        ];
    }
}
