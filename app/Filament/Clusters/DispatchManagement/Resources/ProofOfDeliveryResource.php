<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource\Pages;
use App\Models\ProofOfDelivery;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Delivery Confirmation" — the first dedicated CRUD Resource for
 * ProofOfDelivery. Smart Weighbridge only ever creates one (via the
 * Vehicle Exit action); this is where staff can list/search/manage them.
 */
class ProofOfDeliveryResource extends Resource
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

    protected static ?string $model = ProofOfDelivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Delivery Confirmation';

    protected static ?int $navigationSort = 8;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['dispatchOrder.customer']);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Delivered' => 'success',
            'Partial Delivery' => 'warning',
            'Rejected', 'Returned' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Delivery')->schema([
                    Forms\Components\Select::make('dispatch_order_id')->label('Dispatch Order')
                        ->relationship('dispatchOrder', 'dispatch_number')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('receiver_name')->label('Received By'),
                    Forms\Components\DateTimePicker::make('delivery_time')->default(now()),
                    Forms\Components\Select::make('status')->options(array_combine(ProofOfDelivery::STATUSES, ProofOfDelivery::STATUSES))->default('Delivered')->required(),
                ])->columns(2),

                Forms\Components\Section::make('Quantity')->schema([
                    Forms\Components\TextInput::make('delivered_quantity')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('rejected_quantity')->numeric()->suffix('t'),
                ])->columns(2),

                Forms\Components\Section::make('Location & Signatures')->schema([
                    Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
                    Forms\Components\FileUpload::make('customer_signature_path')->label('Customer Signature')->image()->directory('proof-of-delivery'),
                    Forms\Components\FileUpload::make('driver_signature_path')->label('Driver Signature')->image()->directory('proof-of-delivery'),
                ])->columns(2),

                Forms\Components\Section::make('Notes')->schema([
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('delivery_time', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatchOrder.dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dispatchOrder.customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('receiver_name')->label('Received By')->placeholder('—'),
                Tables\Columns\TextColumn::make('delivered_quantity')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('rejected_quantity')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('delivery_time')->dateTime('d M Y H:i')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(ProofOfDelivery::STATUSES, ProofOfDelivery::STATUSES)),
            ])
            ->actions([
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
        return ['receiver_name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProofOfDeliveries::route('/'),
            'create' => Pages\CreateProofOfDelivery::route('/create'),
            'edit' => Pages\EditProofOfDelivery::route('/{record}/edit'),
        ];
    }
}
