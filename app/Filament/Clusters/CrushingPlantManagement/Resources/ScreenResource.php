<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource\Pages;
use App\Models\Screen;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScreenResource extends Resource
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

    protected static ?string $model = Screen::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Screens';

    protected static ?int $navigationSort = 4;

    public static function getStatusOptions(): array
    {
        return array_combine(Screen::STATUSES, Screen::STATUSES);
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
                Forms\Components\Section::make('Screen Details')->schema([
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->label('Screen Name')->required(),
                    Forms\Components\TextInput::make('decks')->numeric(),
                    Forms\Components\TextInput::make('mesh_sizes')->placeholder('e.g. 40mm / 20mm / 10mm'),
                    Forms\Components\TextInput::make('capacity_tph')->label('Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('current_output')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('operating_hours')->numeric()->suffix('hrs'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Idle')->required(),
                    Forms\Components\Select::make('equipment_id')->label('Linked Equipment')->relationship('equipment', 'name')->searchable()->preload(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Screen Name')->searchable(),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Production Line'),
                Tables\Columns\TextColumn::make('decks')->placeholder('—'),
                Tables\Columns\TextColumn::make('mesh_sizes')->placeholder('—'),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_output')->numeric()->suffix(' t/h')->placeholder('—'),
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
            'index' => Pages\ListScreens::route('/'),
            'create' => Pages\CreateScreen::route('/create'),
            'edit' => Pages\EditScreen::route('/{record}/edit'),
        ];
    }
}
