<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionLineResource\Pages;
use App\Models\CrushingPlant;
use App\Models\ProductionLine;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionLineResource extends Resource
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

    protected static ?string $model = ProductionLine::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Production Lines';

    protected static ?int $navigationSort = 2;

    public static function getStatusOptions(): array
    {
        return array_combine(CrushingPlant::STATUSES, CrushingPlant::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Running' => 'success',
            'Idle' => 'gray',
            'Maintenance' => 'warning',
            'Breakdown' => 'danger',
            'Retired' => 'gray',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Production Line Details')->schema([
                    Forms\Components\TextInput::make('line_code')->label('Line Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->label('Line Name')->required(),
                    Forms\Components\Select::make('crushing_plant_id')->label('Associated Plant')->relationship('crushingPlant', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('capacity_tph')->label('Maximum Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('current_capacity_tph')->label('Current Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Idle')->required(),
                    Forms\Components\Select::make('supervisor_id')->label('Assigned Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('line_code')->label('Code')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('name')->label('Line Name')->searchable(),
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant'),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Max Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_capacity_tph')->label('Current Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionLines::route('/'),
            'create' => Pages\CreateProductionLine::route('/create'),
            'edit' => Pages\EditProductionLine::route('/{record}/edit'),
        ];
    }
}
