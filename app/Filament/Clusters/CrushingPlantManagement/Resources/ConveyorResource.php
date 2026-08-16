<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource\Pages;
use App\Models\Conveyor;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConveyorResource extends Resource
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

    protected static ?string $model = Conveyor::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Conveyors';

    protected static ?int $navigationSort = 5;

    public static function getStatusOptions(): array
    {
        return array_combine(Conveyor::STATUSES, Conveyor::STATUSES);
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
                Forms\Components\Section::make('Conveyor Details')->schema([
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->label('Conveyor Name')->required(),
                    Forms\Components\TextInput::make('length_m')->label('Length')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('capacity_tph')->label('Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('motor_power_kw')->label('Motor Power')->numeric()->suffix('kW'),
                    Forms\Components\TextInput::make('speed_mps')->label('Speed')->numeric()->suffix('m/s'),
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
                Tables\Columns\TextColumn::make('name')->label('Conveyor Name')->searchable(),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Production Line'),
                Tables\Columns\TextColumn::make('length_m')->label('Length')->numeric()->suffix(' m')->placeholder('—'),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('motor_power_kw')->label('Motor Power')->numeric()->suffix(' kW')->placeholder('—'),
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
            'index' => Pages\ListConveyors::route('/'),
            'create' => Pages\CreateConveyor::route('/create'),
            'edit' => Pages\EditConveyor::route('/{record}/edit'),
        ];
    }
}
