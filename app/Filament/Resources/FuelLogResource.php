<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FuelLogResource\Pages;
use App\Models\FuelLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class FuelLogResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'fuel-logs';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking', 'quarry'];
    }

    protected static ?string $model = FuelLog::class;

    // Use a flame icon to visually represent fuel (closest available Heroicon)
    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 3; // Places it below Maintenance

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Refueling Details')->schema([
                Forms\Components\Select::make('truck_id')
                    ->relationship('truck', 'plate_number')
                    ->required()->preload()->searchable(),
                Forms\Components\Select::make('employee_id')
                    ->relationship('driver', 'full_name')
                    ->label('Driver')
                    ->required()->preload()->searchable(),
                Forms\Components\TextInput::make('fuel_station')->required(),
                Forms\Components\DatePicker::make('date')->default(now())->required(),
            ])->columns(2),

            Forms\Components\Section::make('Cost & Quantity')->schema([
                Forms\Components\TextInput::make('quantity_litres')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => $set('total_cost', $state * $get('cost_per_litre')))
                    ->required(),
                Forms\Components\TextInput::make('cost_per_litre')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => $set('total_cost', $state * $get('quantity_litres')))
                    ->required(),
                Forms\Components\TextInput::make('total_cost')
                    ->numeric()
                    ->prefix(session('currency', 'KES'))
                    ->readOnly() // Auto-calculated
                    ->required(),
                Forms\Components\TextInput::make('mileage_at_fueling')
                    ->numeric()
                    ->label('Mileage (KM)')
                    ->required(),
                Forms\Components\FileUpload::make('receipt_upload')
                    ->directory('fuel-receipts')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date')->date()->sortable(),
            Tables\Columns\TextColumn::make('truck.plate_number')->searchable(),
            Tables\Columns\TextColumn::make('driver.full_name')->label('Driver'),
            Tables\Columns\TextColumn::make('quantity_litres')->suffix(' L'),
            Tables\Columns\TextColumn::make('total_cost')->money()->sortable(),
            Tables\Columns\TextColumn::make('fuel_station')->description(fn ($record) => 'At '.$record->mileage_at_fueling.' KM'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('truck_id')
                    ->relationship('truck', 'plate_number')
                    ->label('Filter by Truck'),
            ])->striped();
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
            'index' => Pages\ListFuelLogs::route('/'),
            'create' => Pages\CreateFuelLog::route('/create'),
            'edit' => Pages\EditFuelLog::route('/{record}/edit'),
        ];
    }
}
