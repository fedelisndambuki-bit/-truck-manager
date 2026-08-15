<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\FuelLogResource\Pages;
use App\Models\Equipment;
use App\Models\FuelLog;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Fuel Integration — the same FuelLog table used across Dispatch/Fleet,
 * filtered to equipment-linked entries. Fuel transactions/consumption/
 * efficiency/cost read straight from here; anomaly detection is
 * FuelAnomalyDetectionService (surfaced via alerts, not a new table).
 */
class FuelLogResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = FuelLog::class;

    protected static ?string $slug = 'fuel-integration';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Fuel Integration';

    protected static ?int $navigationSort = 18;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->whereNotNull('equipment_id');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Fuel Transaction')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('employee_id')->label('Driver / Operator')->relationship('driver', 'full_name')->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\TextInput::make('quantity_litres')->numeric()->suffix('L')->required(),
                    Forms\Components\TextInput::make('cost_per_litre')->numeric()->prefix(session('currency', 'KES'))->required(),
                    Forms\Components\TextInput::make('total_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('mileage_at_fueling')->label('Meter / Engine Hours Reading')->numeric()->default(0)->required(),
                    Forms\Components\TextInput::make('fuel_station')->required(),
                    Forms\Components\FileUpload::make('receipt_upload')->image()->disk('public')->directory('fuel-receipts'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('quantity_litres')->suffix(' L'),
                Tables\Columns\TextColumn::make('cost_per_litre')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('total_cost')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('fuel_station')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
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
