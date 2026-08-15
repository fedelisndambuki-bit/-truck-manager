<?php

namespace App\Filament\Resources;

use App\Enums\TruckStatus;
use App\Filament\Resources\TruckResource\Pages;
use App\Models\Truck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class TruckResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'fleet-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $model = Truck::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::getGeneralSection(),
            static::getTechnicalSection(),
            static::getComplianceSection(),
        ]);
    }

    protected static function getGeneralSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('General Information')->schema([
            Forms\Components\TextInput::make('plate_number')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('truck_type')
                ->options(['Trailer' => 'Trailer', 'Lorry' => 'Lorry', 'Pickup' => 'Pickup'])->required(),
            Forms\Components\Select::make('axle_config')
                ->options([
                    '2-axle' => '2 Axles (4-6 Wheels)',
                    '3-axle' => '3 Axles (6-10 Wheels)',
                    '4-axle' => '4 Axles (10-14 Wheels)',
                    '5-axle' => '5 Axles (14-18 Wheels)',
                    '6-axle' => '6 Axles (18-22 Wheels)',
                ])
                ->default('2-axle')
                ->required(),
            Forms\Components\Select::make('tyre_count')
                ->options([
                    4 => '4 Tyres',
                    6 => '6 Tyres',
                    10 => '10 Tyres',
                    12 => '12 Tyres',
                    14 => '14 Tyres',
                    18 => '18 Tyres',
                    22 => '22 Tyres',
                ])
                ->default(6)
                ->required(),
            Forms\Components\Select::make('status')
                ->options(TruckStatus::class)
                ->default(TruckStatus::Available)->required(),
            Forms\Components\TextInput::make('brand'),
            Forms\Components\TextInput::make('model'),
            Forms\Components\TextInput::make('year')->numeric(),
            Forms\Components\FileUpload::make('truck_image')
                ->directory('truck_images')
                ->disk('public')
                ->image()
                ->visibility('public')
                ->imageEditor()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5000),
        ])->columns(3);
    }

    protected static function getTechnicalSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Technical Specifications')->schema([
            Forms\Components\TextInput::make('engine_number'),
            Forms\Components\TextInput::make('chassis_number'),
            Forms\Components\TextInput::make('capacity_tons')->numeric()->suffix('Tons'),
            Forms\Components\TextInput::make('current_mileage')->numeric()->suffix('KM'),
            Forms\Components\TextInput::make('fuel_consumption')->numeric()->label('KM per Litre'),
        ])->columns(3);
    }

    protected static function getComplianceSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Compliance & Legal')->schema([
            Forms\Components\TextInput::make('insurance_provider'),
            Forms\Components\DatePicker::make('insurance_expiry'),
            Forms\Components\DatePicker::make('road_license_expiry'),
            Forms\Components\DatePicker::make('purchase_date'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('plate_number')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('truck_type')->badge(),
            Tables\Columns\TextColumn::make('driver.full_name')->label('Assigned Driver')->placeholder('No Driver'),
            Tables\Columns\TextColumn::make('status')->badge()
                ->color(fn (TruckStatus $state): string => $state->getColor()),
            Tables\Columns\TextColumn::make('current_mileage')->formatStateUsing(fn ($state) => number_format($state).' KM'),
            Tables\Columns\TextColumn::make('road_license_expiry')->date()->label('License Exp'),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'Available' => 'Available',
                'In Transit' => 'In Transit',
                'Under Maintenance' => 'Under Maintenance',
            ]),
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
            'index' => Pages\ListTrucks::route('/'),
            'create' => Pages\CreateTruck::route('/create'),
            'edit' => Pages\EditTruck::route('/{record}/edit'),
        ];
    }
}
