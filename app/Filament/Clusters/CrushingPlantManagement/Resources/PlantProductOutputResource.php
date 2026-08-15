<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource\Pages;
use App\Models\ProductionOutput;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * A plant-focused view/form over the same ProductionOutput table Production
 * Management's own repeater posts to — here shown as its own sidebar entry,
 * linked to a Production Run (crushing_plant_id via production_run_id) rather
 * than a Production Record (production_log_id).
 */
class PlantProductOutputResource extends Resource
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

    protected static ?string $model = ProductionOutput::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationLabel = 'Product Output';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Output')->schema([
                    Forms\Components\Select::make('production_run_id')->label('Production Run')
                        ->relationship('productionRun', 'run_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->run_number ?? ('Run #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('quantity_produced')->numeric()->suffix('t')->required(),
                    Forms\Components\TextInput::make('unit')->default('tonne'),
                    Forms\Components\Select::make('stockpile_id')->label('Destination Stockpile')->relationship('stockpile', 'label')->searchable()->preload(),
                    Forms\Components\TextInput::make('quality_grade'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('production_run_id'))
            ->columns([
                Tables\Columns\TextColumn::make('productionRun.run_number')->label('Run #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('productionRun.crushingPlant.name')->label('Plant')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('quantity_produced')->numeric()->suffix(fn (ProductionOutput $record) => ' ' . ($record->unit ?? 'tonne')),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Destination Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('quality_grade')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('material_id')->label('Product')->relationship('material', 'name'),
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
            'index' => Pages\ListPlantProductOutputs::route('/'),
            'create' => Pages\CreatePlantProductOutput::route('/create'),
            'edit' => Pages\EditPlantProductOutput::route('/{record}/edit'),
        ];
    }
}
