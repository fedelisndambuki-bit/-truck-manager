<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\PitResource\Pages;
use App\Models\Pit;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PitResource extends Resource
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

    protected static ?string $model = Pit::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-circle';

    protected static ?string $navigationLabel = 'Pits';

    protected static ?int $navigationSort = 4;

    public static function getSafetyStatusOptions(): array
    {
        return array_combine(Pit::SAFETY_STATUSES, Pit::SAFETY_STATUSES);
    }

    public static function getSafetyStatusColor(string $state): string
    {
        return match ($state) {
            'Safe' => 'success',
            'Caution' => 'warning',
            'Restricted' => 'danger',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['extractionSite']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pit Details')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('depth')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('bench_level'),
                    Forms\Components\TextInput::make('material_type'),
                    Forms\Components\TextInput::make('reserve_estimate')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('current_production')->numeric()->suffix('t')->disabled()->dehydrated(false)->placeholder('Computed from approved production'),
                    Forms\Components\Select::make('safety_status')->options(self::getSafetyStatusOptions())->default('Safe')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('depth')->numeric()->suffix(' m')->placeholder('—'),
                Tables\Columns\TextColumn::make('bench_level')->placeholder('—'),
                Tables\Columns\TextColumn::make('reserve_estimate')->numeric()->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('current_production')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('safety_status')->badge()->color(fn (string $state): string => self::getSafetyStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('safety_status')->options(self::getSafetyStatusOptions()),
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
            'index' => Pages\ListPits::route('/'),
            'create' => Pages\CreatePit::route('/create'),
            'edit' => Pages\EditPit::route('/{record}/edit'),
        ];
    }
}
