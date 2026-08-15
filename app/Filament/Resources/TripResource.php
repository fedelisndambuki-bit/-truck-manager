<?php

namespace App\Filament\Resources;

use App\Enums\TripStatus;
use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers\ExpensesRelationManager;
use App\Filament\Resources\TripResource\RelationManagers\InvoicesRelationManager;
use App\Models\Cargo;
use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use App\Traits\HasModuleAccess;

class TripResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'trip-tracking';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }

    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Fleet Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                static::getAssignmentSection(),
                static::getCargoSection(),
                static::getRouteSection(),
            ])->columnSpanFull(),

            static::getScheduleSection(),
        ]);
    }

    protected static function getAssignmentSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Trip Assignment')
            ->schema([
                Forms\Components\Select::make('truck_id')
                    ->relationship('truck', 'plate_number')
                    ->required()->searchable()->preload(),
                Forms\Components\Select::make('employee_id')
                    ->relationship('driver', 'full_name')
                    ->label('Driver')
                    ->required()->searchable()->preload(),
                Forms\Components\Select::make('status')
                    ->options(TripStatus::class)->default(TripStatus::Scheduled)->required()->native(false),
            ])->columns(3);
    }

    protected static function getCargoSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Client & Cargo Information')
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('cargo_id', null))
                    ->required(),

                Forms\Components\Select::make('cargo_id')
                    ->label('Cargo Item')
                    ->options(function (Forms\Get $get) {
                        $clientId = $get('client_id');
                        if (! $clientId) {
                            return [];
                        }

                        return Cargo::where('client_id', $clientId)->pluck('description', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $cargo = Cargo::find($state);
                        if ($cargo) {
                            $set('cargo_weight', $cargo->weight);
                            $set('cargo_type', $cargo->cargo_type);
                        }
                    }),
            ])->columns(2);
    }

    protected static function getRouteSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Route Details')
            ->schema([
                Forms\Components\TextInput::make('route_name')->required(),
                Forms\Components\TextInput::make('origin')->required(),
                Forms\Components\TextInput::make('destination')->required(),
                Forms\Components\TextInput::make('distance_km')->numeric()->suffix('km'),
                Forms\Components\TextInput::make('cargo_type')->required(),
                Forms\Components\TextInput::make('cargo_weight')->numeric()->suffix('Tons'),
            ])->columns(3);
    }

    protected static function getScheduleSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Schedule & Financials')
            ->schema([
                Forms\Components\DateTimePicker::make('departure_time')->required(),
                Forms\Components\DateTimePicker::make('expected_arrival')->required(),
                Forms\Components\TextInput::make('revenue')->numeric()->prefix(session('currency', 'KES'))->label('Projected Revenue'),
                Forms\Components\TextInput::make('fuel_cost')->numeric()->prefix(session('currency', 'KES')),
                Forms\Components\TextInput::make('trip_expenses')->numeric()->prefix(session('currency', 'KES')),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('status')->badge()
                ->color(fn (TripStatus $state): string => $state->getColor()),
            Tables\Columns\TextColumn::make('assignment.status')
                ->label('Schedule Status')
                ->badge()
                ->color(fn ($state): string => match ($state) {
                    'accepted' => 'success',
                    'declined' => 'danger',
                    'pending' => 'warning',
                    default => 'warning',
                })
                ->formatStateUsing(fn ($state): string => ucfirst($state ?? 'pending')),
            Tables\Columns\TextColumn::make('truck.plate_number')->sortable(),
            Tables\Columns\TextColumn::make('client.company_name')->label('Client')->searchable(),
            Tables\Columns\TextColumn::make('route_name')->description(fn ($record) => "$record->origin → $record->destination"),
            Tables\Columns\TextColumn::make('departure_time')->label('Departure')->dateTime('M d, Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('expected_arrival')->label('Expected Arrival')->dateTime('M d, Y H:i')->sortable(),
            Tables\Columns\TextColumn::make('assignment.decline_reason')
                ->label('Decline Reason')
                ->wrap()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([

                Tables\Filters\SelectFilter::make('status')->options(['Scheduled' => 'Scheduled', 'In Progress' => 'In Progress', 'Completed' => 'Completed']),

            ])->actions([
            Tables\Actions\Action::make('downloadManifest')
                ->label('Manifest')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function (Trip $record) {
                    $pdf = Pdf::loadView('pdf.manifest', ['trip' => $record]);

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "Manifest-Trip-{$record->id}.pdf");
                }),
            Tables\Actions\EditAction::make(),
        ])->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('assignment');
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit'   => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
