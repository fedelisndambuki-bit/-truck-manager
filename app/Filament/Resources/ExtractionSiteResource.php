<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtractionSiteResource\Pages;
use App\Models\ExtractionSite;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExtractionSiteResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static ?string $model = ExtractionSite::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Quarry';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site Details')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\Select::make('material_type')
                        ->options([
                            'Granite' => 'Granite',
                            'Limestone' => 'Limestone',
                            'Sand' => 'Sand',
                            'Gravel' => 'Gravel',
                            'Other' => 'Other',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'Active' => 'Active',
                            'Inactive' => 'Inactive',
                            'Depleted' => 'Depleted',
                        ])
                        ->default('Active')
                        ->required(),
                    Forms\Components\TextInput::make('estimated_reserves')
                        ->numeric()
                        ->suffix('tonnes'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('material_type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'gray',
                        'Depleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('estimated_reserves')
                    ->numeric()
                    ->suffix(' t')
                    ->label('Est. Reserves'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Depleted' => 'Depleted',
                ]),
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
            'index' => Pages\ListExtractionSites::route('/'),
            'create' => Pages\CreateExtractionSite::route('/create'),
            'edit' => Pages\EditExtractionSite::route('/{record}/edit'),
        ];
    }
}
