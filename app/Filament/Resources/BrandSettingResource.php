<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandSettingResource\Pages;
use App\Models\BrandSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BrandSettingResource extends Resource
{
    protected static ?string $model = BrandSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Company Profile';

    public static function canCreate(): bool
    {
        // Managed as a single record via ManageBrandSettings
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Information')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Company name')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Brand Logo')
                    ->schema([
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo image')
                            ->directory('brand')
                            ->disk('public')
                            ->image()
                            ->visibility('public')
                            ->nullable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(4096),
                    ]),
                Forms\Components\Section::make('Brand Icon')
                    ->schema([
                        Forms\Components\FileUpload::make('icon_path')
                            ->label('Logo icon (favicon)')
                            ->helperText('Ideally a square image, e.g. 64x64 or 128x128.')
                            ->directory('brand')
                            ->disk('public')
                            ->image()
                            ->visibility('public')
                            ->nullable()
                            ->acceptedFileTypes(['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'])
                            ->maxSize(2048),
                    ]),
            ])
            ->columns(2);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo'),
                Tables\Columns\ImageColumn::make('icon_path')
                    ->label('Icon'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ManageBrandSettings::route('/'),
            'edit' => Pages\EditBrandSetting::route('/{record}/edit'),
        ];
    }
}

