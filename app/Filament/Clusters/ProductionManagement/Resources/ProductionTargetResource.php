<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource\Pages;
use App\Models\ProductionTarget;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionTargetResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = ProductionManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $model = ProductionTarget::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Production Targets';

    protected static ?int $navigationSort = 11;

    public static function getPeriodTypeOptions(): array
    {
        return array_combine(ProductionTarget::PERIOD_TYPES, ProductionTarget::PERIOD_TYPES);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['extractionSite', 'material']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Target Details')->schema([
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload()->helperText('Leave blank for a site-wide target'),
                    Forms\Components\Select::make('period_type')->options(self::getPeriodTypeOptions())->default('Monthly')->required(),
                    Forms\Components\DatePicker::make('period_start')->required(),
                    Forms\Components\DatePicker::make('period_end')->required(),
                    Forms\Components\TextInput::make('target_quantity')->numeric()->suffix('t')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('Site-wide'),
                Tables\Columns\TextColumn::make('period_type')->badge(),
                Tables\Columns\TextColumn::make('period_start')->date()->label('From'),
                Tables\Columns\TextColumn::make('period_end')->date()->label('To'),
                Tables\Columns\TextColumn::make('target_quantity')->numeric()->suffix(' t')->label('Target'),
                Tables\Columns\TextColumn::make('actual')
                    ->label('Actual')
                    ->state(fn (ProductionTarget $record): string => number_format($record->actualQuantity(), 1) . ' t'),
                Tables\Columns\TextColumn::make('achievement')
                    ->label('Achievement %')
                    ->state(fn (ProductionTarget $record): string => $record->achievementPercent() . '%')
                    ->badge()
                    ->color(fn (ProductionTarget $record): string => $record->achievementPercent() >= 90 ? 'success' : ($record->achievementPercent() >= 60 ? 'warning' : 'danger')),
            ])
            ->defaultSort('period_start', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('period_type')->options(self::getPeriodTypeOptions()),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
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
            'index' => Pages\ListProductionTargets::route('/'),
            'create' => Pages\CreateProductionTarget::route('/create'),
            'edit' => Pages\EditProductionTarget::route('/{record}/edit'),
        ];
    }
}
