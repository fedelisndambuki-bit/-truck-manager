<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource\Pages;
use App\Models\PlantOperation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlantOperationResource extends Resource
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

    protected static ?string $model = PlantOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Plant Operations';

    protected static ?int $navigationSort = 7;

    public static function getShiftOptions(): array
    {
        return array_combine(PlantOperation::SHIFTS, PlantOperation::SHIFTS);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Operation Details')->schema([
                    Forms\Components\Select::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name', fn ($query, Forms\Get $get) => $query->where('crushing_plant_id', $get('crushing_plant_id')))->searchable()->preload(),
                    Forms\Components\Select::make('shift')->options(self::getShiftOptions())->default('Day')->required(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('material_id')->label('Feed Material')->relationship('material', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('feed_quantity')->numeric()->suffix('t'),
                    Forms\Components\DateTimePicker::make('start_time'),
                    Forms\Components\DateTimePicker::make('end_time'),
                    Forms\Components\TextInput::make('downtime_minutes')->numeric()->suffix('min')->default(0),
                    Forms\Components\TextInput::make('weather'),
                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant')->searchable(),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Line')->placeholder('—'),
                Tables\Columns\TextColumn::make('shift')->badge(),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Feed Material')->placeholder('—'),
                Tables\Columns\TextColumn::make('feed_quantity')->numeric()->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('operating_hours')
                    ->label('Operating Hours')
                    ->state(fn (PlantOperation $record): string => number_format($record->operatingHours(), 1) . ' hrs'),
                Tables\Columns\TextColumn::make('downtime_minutes')->label('Downtime')->numeric()->suffix(' min'),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('shift')->options(self::getShiftOptions()),
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
            'index' => Pages\ListPlantOperations::route('/'),
            'create' => Pages\CreatePlantOperation::route('/create'),
            'edit' => Pages\EditPlantOperation::route('/{record}/edit'),
        ];
    }
}
