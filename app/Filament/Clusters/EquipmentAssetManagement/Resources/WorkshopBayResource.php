<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopBayResource\Pages;
use App\Models\WorkshopBay;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopBayResource extends Resource
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

    protected static ?string $model = WorkshopBay::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Workshop Bays';

    protected static ?int $navigationSort = 14;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['workshop']);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Available' => 'success', 'Occupied' => 'warning', 'Out of Service' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('workshop_id')->relationship('workshop', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('bay_number')->required(),
                Forms\Components\Select::make('status')->options(array_combine(WorkshopBay::STATUSES, WorkshopBay::STATUSES))->default('Available')->required(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('workshop.name')->label('Workshop')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bay_number')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('workshop_id')->label('Workshop')->relationship('workshop', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(WorkshopBay::STATUSES, WorkshopBay::STATUSES)),
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
            'index' => Pages\ListWorkshopBays::route('/'),
            'create' => Pages\CreateWorkshopBay::route('/create'),
            'edit' => Pages\EditWorkshopBay::route('/{record}/edit'),
        ];
    }
}
