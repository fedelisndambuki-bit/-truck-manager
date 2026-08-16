<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\FeederResource\Pages;
use App\Models\Feeder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeederResource extends Resource
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

    protected static ?string $model = Feeder::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Feeders';

    protected static ?int $navigationSort = 6;

    public static function getTypeOptions(): array
    {
        return array_combine(Feeder::TYPES, Feeder::TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(Feeder::STATUSES, Feeder::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Running' => 'success',
            'Idle' => 'gray',
            'Maintenance' => 'warning',
            'Breakdown' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Feeder Details')->schema([
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->label('Feeder Name')->required(),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions()),
                    Forms\Components\TextInput::make('capacity_tph')->label('Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('feed_rate')->numeric()->suffix('t/h'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Idle')->required(),
                    Forms\Components\Select::make('equipment_id')->label('Linked Equipment')->relationship('equipment', 'name')->searchable()->preload(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Feeder Name')->searchable(),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Production Line'),
                Tables\Columns\TextColumn::make('type')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('feed_rate')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('production_line_id')->label('Line')->relationship('productionLine', 'name'),
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
        return parent::getEloquentQuery()->with(['productionLine']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeders::route('/'),
            'create' => Pages\CreateFeeder::route('/create'),
            'edit' => Pages\EditFeeder::route('/{record}/edit'),
        ];
    }
}
