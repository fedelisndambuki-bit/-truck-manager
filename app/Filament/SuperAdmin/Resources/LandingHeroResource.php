<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\LandingHeroResource\Pages;
use App\Filament\SuperAdmin\Resources\LandingHeroResource\RelationManagers;
use App\Models\LandingHero;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LandingHeroResource extends Resource
{
    protected static ?string $model = LandingHero::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('badge_text')
                    ->label('Eyebrow Badge Text')
                    ->maxLength(255),
                Forms\Components\TextInput::make('headline')
                    ->required()
                    ->maxLength(255)
                    ->helperText('For a two-line headline with a highlighted second line, separate the two parts with a comma.'),
                Forms\Components\TextInput::make('subheadline')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('hero_image')
                    ->image()
                    ->directory('landing-heroes'),
                Forms\Components\TextInput::make('primary_button_text')
                    ->maxLength(255),
                Forms\Components\TextInput::make('primary_button_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('secondary_button_text')
                    ->maxLength(255),
                Forms\Components\TextInput::make('secondary_button_link')
                    ->maxLength(255),
                Forms\Components\TextInput::make('background_style')
                    ->maxLength(255),
                Forms\Components\Toggle::make('status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('headline')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subheadline')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('hero_image'),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingHeroes::route('/'),
            'create' => Pages\CreateLandingHero::route('/create'),
            'edit' => Pages\EditLandingHero::route('/{record}/edit'),
        ];
    }
}
