<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\LandingSectionResource\Pages;
use App\Models\LandingSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandingSectionResource extends Resource
{
    protected static ?string $model = LandingSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    protected static ?string $navigationLabel = 'Section Headings';

    // Fixed set of section keys (seeded once) — badge/heading/subtext for
    // sections that don't already have their own content model (Features,
    // Pricing, Testimonials). Editing only: creating/deleting a row would
    // either duplicate a key the blade already looks up, or remove a key it
    // expects to find, so both actions are disabled here.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Identifies which landing page section this controls.'),
                Forms\Components\TextInput::make('badge_text')
                    ->label('Eyebrow Badge Text')
                    ->maxLength(255),
                Forms\Components\TextInput::make('heading')
                    ->maxLength(255)
                    ->helperText('For a two-line heading with a highlighted second line, separate the two parts with a comma.'),
                Forms\Components\Textarea::make('subtext')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->badge(),
                Tables\Columns\TextColumn::make('badge_text'),
                Tables\Columns\TextColumn::make('heading')
                    ->limit(40),
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
                //
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
            'index' => Pages\ListLandingSections::route('/'),
            'edit' => Pages\EditLandingSection::route('/{record}/edit'),
        ];
    }
}
