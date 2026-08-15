<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\Pages;
use App\Models\CrushingPlant;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CrushingPlantResource extends Resource
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

    protected static ?string $model = CrushingPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Plants';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\Section::make('Plant Details')->schema([
                    Forms\Components\TextInput::make('plant_code')->label('Plant Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->label('Plant Name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('capacity_tph')->label('Capacity')->numeric()->suffix('t/h'),
                    Forms\Components\DatePicker::make('commission_date'),
                    Forms\Components\TextInput::make('manufacturer'),
                    Forms\Components\TextInput::make('model'),
                    Forms\Components\TextInput::make('serial_number'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Idle')->required(),
                    Forms\Components\Select::make('supervisor_id')->label('Supervisor')->relationship('supervisor', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                ])->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Plant Details')->schema([
                TextEntry::make('plant_code')->label('Plant Code')->placeholder('—'),
                TextEntry::make('name')->label('Plant Name'),
                TextEntry::make('extractionSite.name')->label('Quarry'),
                TextEntry::make('location')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                TextEntry::make('capacity_tph')->label('Rated Capacity')->suffix(' t/h')->placeholder('—'),
                TextEntry::make('manufacturer')->placeholder('—'),
                TextEntry::make('model')->placeholder('—'),
                TextEntry::make('serial_number')->placeholder('—'),
                TextEntry::make('commission_date')->date()->placeholder('—'),
                TextEntry::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plant_code')->label('Code')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('name')->label('Plant Name')->searchable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('location')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('capacity_tph')->label('Capacity')->numeric()->suffix(' t/h'),
                Tables\Columns\TextColumn::make('supervisor.full_name')->label('Supervisor')->placeholder('—'),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrushingPlants::route('/'),
            'create' => Pages\CreateCrushingPlant::route('/create'),
            'edit' => Pages\EditCrushingPlant::route('/{record}/edit'),
        ];
    }
}
