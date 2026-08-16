<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource\Pages;
use App\Models\StockReservation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockReservationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = StockpileManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'stockpile.view';
    }

    protected static ?string $model = StockReservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationLabel = 'Reservations';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['stockpile.material', 'customer']);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(StockReservation::STATUSES, StockReservation::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Reserved' => 'warning',
            'Converted to Dispatch' => 'success',
            'Released' => 'gray',
            'Expired' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reservation Details')->schema([
                    Forms\Components\TextInput::make('reservation_number')->label('Reservation #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('customer_id')->label('Customer')->relationship('customer', 'company_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('order_number')->label('Order Number'),
                    Forms\Components\TextInput::make('quantity_reserved')->numeric()->suffix('t')->required(),
                    Forms\Components\DatePicker::make('reservation_date')->default(now())->required(),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\Select::make('reserved_by_id')->label('Reserved By')->relationship('reservedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Reserved')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reservation_number')->label('Reservation #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stockpile.material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('order_number')->label('Order #')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity_reserved')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('reservation_date')->date('d M Y'),
                Tables\Columns\TextColumn::make('expiry_date')->date('d M Y')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-lock-open')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (StockReservation $record) => $record->status === 'Reserved')
                    ->action(function (StockReservation $record) {
                        $record->release();
                        Notification::make()->title('Reservation released')->success()->send();
                    }),
                Tables\Actions\Action::make('convertToDispatch')
                    ->label('Convert to Dispatch')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StockReservation $record) => $record->status === 'Reserved')
                    ->action(function (StockReservation $record) {
                        $record->convertToDispatch();
                        Notification::make()->title('Reservation converted to dispatch — stock drawn down')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockReservations::route('/'),
            'create' => Pages\CreateStockReservation::route('/create'),
            'edit' => Pages\EditStockReservation::route('/{record}/edit'),
        ];
    }
}
