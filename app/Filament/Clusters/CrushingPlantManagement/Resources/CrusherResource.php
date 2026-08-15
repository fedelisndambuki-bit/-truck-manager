<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\CrusherResource\Pages;
use App\Models\Crusher;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CrusherResource extends Resource
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

    protected static ?string $model = Crusher::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Crushers';

    protected static ?int $navigationSort = 3;

    public static function getTypeOptions(): array
    {
        return array_combine(Crusher::TYPES, Crusher::TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(Crusher::STATUSES, Crusher::STATUSES);
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
                Forms\Components\Section::make('Crusher Details')->schema([
                    Forms\Components\TextInput::make('crusher_code')->label('Crusher Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions())->required(),
                    Forms\Components\TextInput::make('manufacturer'),
                    Forms\Components\TextInput::make('model'),
                    Forms\Components\TextInput::make('capacity_tph')->label('Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('current_throughput')->numeric()->suffix('t/h'),
                    Forms\Components\TextInput::make('operating_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('wear_status')->placeholder('e.g. Good, Monitor, Replace Soon'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Idle')->required(),
                    Forms\Components\Select::make('equipment_id')->label('Linked Equipment')->relationship('equipment', 'name')->searchable()->preload()
                        ->helperText('Link to an Equipment record to enable Maintenance/Fuel history.'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('crusher_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('productionLine.name')->label('Production Line'),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_throughput')->label('Throughput')->numeric()->suffix(' t/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('wear_status')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::getTypeOptions()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrushers::route('/'),
            'create' => Pages\CreateCrusher::route('/create'),
            'edit' => Pages\EditCrusher::route('/{record}/edit'),
        ];
    }
}
