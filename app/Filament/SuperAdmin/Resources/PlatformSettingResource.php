<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PlatformSettingResource\Pages;
use App\Models\PlatformSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlatformSettingResource extends Resource
{
    protected static ?string $model = PlatformSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Settings Master List';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('General Settings')
                ->schema([
                    Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord: true)->disabled(fn ($record) => $record !== null),
                    Forms\Components\Select::make('type')
                        ->label('Value Type')
                        ->options([
                            'string' => 'String',
                            'boolean' => 'Boolean',
                            'integer' => 'Integer',
                            'json' => 'JSON',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('group')
                        ->label('Setting Group')
                        ->options([
                            'general' => 'General',
                            'branding' => 'Branding',
                            'features' => 'Features',
                            'system' => 'System',
                            'contact' => 'Contact',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('value')
                        ->label('Setting Value')
                        ->required()
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('key')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('value')->limit(50)->searchable(),
            Tables\Columns\TextColumn::make('group')->badge()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
        ])->filters([
            Tables\Filters\SelectFilter::make('group')
                ->options([
                    'general' => 'General',
                    'branding' => 'Branding',
                    'features' => 'Features',
                    'system' => 'System',
                    'contact' => 'Contact',
                ]),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformSettings::route('/'),
            'create' => Pages\CreatePlatformSetting::route('/create'),
            'edit' => Pages\EditPlatformSetting::route('/{record}/edit'),
        ];
    }
}
