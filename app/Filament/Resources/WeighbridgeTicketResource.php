<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeighbridgeTicketResource\Pages;
use App\Models\WeighbridgeTicket;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WeighbridgeTicketResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = WeighbridgeTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Weighbridge / Dispatch';

    protected static ?string $navigationGroup = 'Quarry';

    public static function getDirectionOptions(): array
    {
        return array_combine(WeighbridgeTicket::DIRECTIONS, WeighbridgeTicket::DIRECTIONS);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(WeighbridgeTicket::STATUSES, WeighbridgeTicket::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Weighed' => 'success',
            'Pending' => 'warning',
            'Rejected' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Details')->schema([
                    Forms\Components\TextInput::make('ticket_number')->label('Ticket #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('extraction_site_id')
                        ->label('Quarry Site')
                        ->relationship('extractionSite', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('direction')->options(self::getDirectionOptions())->default('Outbound')->required(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Pending')->required(),
                    Forms\Components\TextInput::make('vehicle_registration')->label('Vehicle Registration'),
                    Forms\Components\TextInput::make('driver_name')->label('Driver Name'),
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'company_name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('direction') === 'Outbound'),
                ])->columns(2),

                Forms\Components\Section::make('Weighing')->schema([
                    Forms\Components\TextInput::make('gross_weight')->numeric()->suffix('kg')->live(onBlur: true),
                    Forms\Components\TextInput::make('tare_weight')->numeric()->suffix('kg')->live(onBlur: true),
                    Forms\Components\TextInput::make('net_weight')->numeric()->suffix('kg')->disabled()->dehydrated(false)
                        ->helperText('Calculated automatically as gross − tare.'),
                    Forms\Components\Toggle::make('is_manual_override')->label('Manual Weight Override'),
                    Forms\Components\DateTimePicker::make('gate_in_at')->label('Gate In'),
                    Forms\Components\DateTimePicker::make('gate_out_at')->label('Gate Out'),
                    Forms\Components\Select::make('operator_id')
                        ->label('Weighbridge Operator')
                        ->relationship('operator', 'full_name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->label('Ticket #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Site')->toggleable(),
                Tables\Columns\TextColumn::make('direction')->badge()->color(fn (string $state): string => $state === 'Outbound' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->placeholder('—'),
                Tables\Columns\TextColumn::make('net_weight')->label('Net Wt')->numeric()->suffix(' kg')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('direction')->options(self::getDirectionOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Site')->relationship('extractionSite', 'name'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeighbridgeTickets::route('/'),
            'create' => Pages\CreateWeighbridgeTicket::route('/create'),
            'view' => Pages\ViewWeighbridgeTicket::route('/{record}'),
            'edit' => Pages\EditWeighbridgeTicket::route('/{record}/edit'),
        ];
    }
}
