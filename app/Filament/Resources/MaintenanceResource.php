<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaintenanceResource\Pages;
use App\Models\Maintenance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Models\Part;
use Filament\Forms\Get;
use Filament\Forms\Set;

use App\Traits\HasModuleAccess;

class MaintenanceResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'maintenance';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking', 'quarry'];
    }

    protected static ?string $model = Maintenance::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Fleet Management';

    protected static ?int $navigationSort = 2; // Places it below Trucks (if Trucks is 1)

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Service Details')->schema([
                Forms\Components\Select::make('truck_id')
                    ->relationship('truck', 'plate_number')
                    ->required()->preload()->searchable(),
                Forms\Components\Select::make('maintenance_type')
                    ->options(['Service' => 'Service', 'Repair' => 'Repair', 'Inspection' => 'Inspection'])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(['Pending' => 'Pending', 'Completed' => 'Completed'])
                    ->default('Pending')->required(),
                Forms\Components\DatePicker::make('maintenance_date')->required(),
                Forms\Components\TextInput::make('service_provider')->required(),
            ])->columns(3),

            Forms\Components\Section::make('Metrics & Costs')->schema([
                Forms\Components\TextInput::make('cost')
                    ->numeric()
                    ->prefix(session('currency', 'KES'))
                    ->required()
                    ->helperText('This is the total cost including labor and parts.'),
                Forms\Components\TextInput::make('mileage_at_service')->numeric()->label('Current Mileage')->required(),
                Forms\Components\TextInput::make('next_service_mileage')->numeric()->label('Next Service at (KM)'),
                Forms\Components\DatePicker::make('next_maintenance_date')->label('Next Service Date'),
                Forms\Components\FileUpload::make('invoice_upload')->directory('invoices')->visibility('public'),
            ])->columns(2),

            Forms\Components\Section::make('Parts Used')->schema([
                Forms\Components\Repeater::make('partUsages')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('part_id')
                            ->relationship('part', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $part = Part::find($state);
                                if ($part) {
                                    $set('unit_price', $part->unit_price);
                                }
                            })
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Set $set, Get $get) => $set('total_price', $state * $get('unit_price'))),
                        Forms\Components\TextInput::make('unit_price')
                            ->numeric()
                            ->prefix(session('currency', 'KES'))
                            ->required()
                            ->readOnly(),
                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->prefix(session('currency', 'KES'))
                            ->required()
                            ->readOnly(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->addActionLabel('Add Part')
                    ->afterStateUpdated(function (Set $set, Get $get) {
                        $parts = $get('partUsages') ?? [];
                        $total = collect($parts)->sum('total_price');
                        // We can't easily auto-set the main 'cost' field here because it might include labor
                        // but we could at least provide a helper or just let the user fill it.
                    }),
            ]),

            Forms\Components\Textarea::make('description')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('truck.plate_number')->sortable()->searchable(),
            Tables\Columns\IconColumn::make('maintenance_type')
                ->options([
                    'heroicon-s-cog' => 'Service',
                    'heroicon-s-wrench' => 'Repair',
                    'heroicon-s-eye' => 'Inspection',
                ])->label('Type'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'Pending' => 'warning',
                    'Completed' => 'success',
                }),
            Tables\Columns\TextColumn::make('cost')->money(),
            Tables\Columns\TextColumn::make('next_service_mileage')
                ->label('Next Service')
                ->description(fn ($record) => 'Due at '.number_format($record->next_service_mileage).' KM'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Pending' => 'Pending', 'Completed' => 'Completed']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMaintenances::route('/'),
            'create' => Pages\CreateMaintenance::route('/create'),
            'edit' => Pages\EditMaintenance::route('/{record}/edit'),
        ];
    }
}
