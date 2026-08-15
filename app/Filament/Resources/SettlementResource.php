<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Widgets\SettlementFilterWidget;
use App\Filament\Resources\SettlementResource\Widgets\SettlementStatsWidget;
use App\Filament\Resources\SettlementResource\Pages;
use App\Models\Settlement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class SettlementResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'payroll';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = Settlement::class;

    protected static ?string $navigationGroup = 'Financials';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes'; // Updated for money theme

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Settlement Details')
                    ->schema([
                        Forms\Components\TextInput::make('settlement_id')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'CHK-'.now()->format('ymdHis'))
                            ->maxLength(255)
                            ->disabledOn('edit'), // Disable editing settlement_id after creation

                        Forms\Components\Select::make('truck_id')
                            ->relationship('truck', 'plate_number')
                            ->label('Truck (plate number)')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('employee_id')
                            ->label('Driver')
                            ->relationship('driver', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('settlement_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('pay_period_start')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('pay_period_end')
                            ->required()
                            ->default(now()->addWeeks(2)),

                        Forms\Components\TextInput::make('trucks') // Corresponds to string column 'trucks'
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 2 trucks'),

                        Forms\Components\TextInput::make('trips') // Corresponds to string column 'trips'
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., 5 trips'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'PENDING' => 'Pending (Unpaid)',
                                'PAID' => 'Paid (Cleared)',
                                'PROCESSING' => 'Processing',
                            ])
                            ->required()
                            ->default('PENDING')
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Financials')
                    ->description('Enter the net amount for the settlement.')
                    ->schema([
                        Forms\Components\TextInput::make('net_amount')
                            ->numeric()
                            ->prefix(session('currency', 'KES'))
                            ->required()
                            ->default(0),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('settlement_id')
                    ->label('Settlement ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('truck.plate_number')
                    ->label('Truck')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.full_name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('settlement_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'PAID' => 'success',
                        'PROCESSING' => 'info',
                    }),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->money('KES')
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->striped();
    }

    public static function getWidgets(): array
    {
        return [
            SettlementStatsWidget::class,
            SettlementFilterWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user  = auth()->user();
        if ($user) {
            $query->where('company_id', $user->company_id);
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'create' => Pages\CreateSettlement::route('/create'),
            'edit' => Pages\EditSettlement::route('/{record}/edit'),
        ];
    }
}
