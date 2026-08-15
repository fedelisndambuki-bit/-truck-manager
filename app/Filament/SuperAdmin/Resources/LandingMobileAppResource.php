<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\LandingMobileAppResource\Pages;
use App\Models\LandingMobileApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LandingMobileAppResource extends Resource
{
    protected static ?string $model = LandingMobileApp::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Landing Page Manager';

    protected static ?string $navigationLabel = 'Mobile App Section';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Content')
                    ->schema([
                        Forms\Components\TextInput::make('badge_text')
                            ->label('Eyebrow Badge Text')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('heading')
                            ->maxLength(255)
                            ->helperText('For a two-line heading with a highlighted second line, separate the two parts with a comma.'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('screenshot')
                            ->image()
                            ->directory('landing-mobile-app'),
                    ])->columns(2),
                Forms\Components\Section::make('Store Links')
                    ->schema([
                        Forms\Components\TextInput::make('play_store_link')
                            ->label('Google Play link')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('app_store_link')
                            ->label('App Store link')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Section::make('Floating Badges')
                    ->description('The two small callouts overlaid on the phone mockup.')
                    ->schema([
                        Forms\Components\TextInput::make('rating_badge_title')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('rating_badge_subtitle')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('payout_badge_title')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('payout_badge_subtitle')
                            ->maxLength(255),
                    ])->columns(2),
                Forms\Components\Toggle::make('status')
                    ->label('Show this section on the landing page')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('heading')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('screenshot'),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
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
            'index' => Pages\ListLandingMobileApps::route('/'),
            'create' => Pages\CreateLandingMobileApp::route('/create'),
            'edit' => Pages\EditLandingMobileApp::route('/{record}/edit'),
        ];
    }
}
