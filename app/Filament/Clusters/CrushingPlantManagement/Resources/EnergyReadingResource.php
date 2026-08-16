<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\EnergyReadingResource\Pages;
use App\Models\EnergyReading;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnergyReadingResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = CrushingPlantManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'plant.view';
    }

    protected static ?string $model = EnergyReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Energy Monitoring';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Energy Reading')->schema([
                    Forms\Components\Select::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name')->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\TextInput::make('electricity_kwh')->label('Electricity Consumption')->numeric()->suffix('kWh'),
                    Forms\Components\TextInput::make('diesel_litres')->label('Diesel Consumption')->numeric()->suffix('L'),
                    Forms\Components\TextInput::make('energy_cost')->numeric()->prefix(fn () => session('currency', 'KES')),
                    Forms\Components\TextInput::make('peak_usage_kw')->label('Peak Usage')->numeric()->suffix('kW'),
                    Forms\Components\TextInput::make('power_factor')->numeric()->step(0.01),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant')->searchable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('electricity_kwh')->label('Electricity')->numeric()->suffix(' kWh')->placeholder('—'),
                Tables\Columns\TextColumn::make('diesel_litres')->label('Diesel')->numeric()->suffix(' L')->placeholder('—'),
                Tables\Columns\TextColumn::make('energy_cost')->label('Cost')->numeric(),
                Tables\Columns\TextColumn::make('cost_per_tonne')
                    ->label('Cost per Tonne')
                    ->state(fn (EnergyReading $record) => $record->costPerTonne() ?? '—'),
                Tables\Columns\TextColumn::make('power_factor')->placeholder('—'),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['crushingPlant']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnergyReadings::route('/'),
            'create' => Pages\CreateEnergyReading::route('/create'),
            'edit' => Pages\EditEnergyReading::route('/{record}/edit'),
        ];
    }
}
