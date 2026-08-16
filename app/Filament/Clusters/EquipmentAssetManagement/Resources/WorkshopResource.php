<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopResource\Pages;
use App\Models\Workshop;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopResource extends Resource
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

    protected static ?string $model = Workshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Workshops';

    protected static ?int $navigationSort = 13;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['extractionSite']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Workshop')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry / Site')->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('capacity_bays')->numeric()->default(0),
                    Forms\Components\Select::make('status')->options(array_combine(Workshop::STATUSES, Workshop::STATUSES))->default('Active')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry')->placeholder('—'),
                Tables\Columns\TextColumn::make('location')->placeholder('—'),
                Tables\Columns\TextColumn::make('bays_count')->label('Bays')->counts('bays'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
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
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }
}
