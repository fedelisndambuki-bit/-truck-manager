<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource\Pages;
use App\Models\QuarryFace;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuarryFaceResource extends Resource
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

    protected static ?string $model = QuarryFace::class;

    protected static ?string $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $navigationLabel = 'Quarry Faces';

    protected static ?int $navigationSort = 3;

    public static function getStatusOptions(): array
    {
        return array_combine(QuarryFace::STATUSES, QuarryFace::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success',
            'Inactive' => 'gray',
            'Closed' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Face Details')->schema([
                    Forms\Components\TextInput::make('face_code')->required(),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('material_type'),
                    Forms\Components\TextInput::make('estimated_reserve')->numeric()->suffix('t'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Active')->required(),
                    Forms\Components\Textarea::make('safety_notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('face_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('material_type')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('estimated_reserve')->numeric()->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
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
            'index' => Pages\ListQuarryFaces::route('/'),
            'create' => Pages\CreateQuarryFace::route('/create'),
            'edit' => Pages\EditQuarryFace::route('/{record}/edit'),
        ];
    }
}
