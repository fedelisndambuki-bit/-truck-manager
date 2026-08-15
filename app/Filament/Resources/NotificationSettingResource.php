<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationSettingResource\Pages;
use App\Filament\Resources\NotificationSettingResource\RelationManagers;
use App\Models\NotificationSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model; // Add this line
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Traits\HasModuleAccess;

class NotificationSettingResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'user-management';
    }
    protected static ?string $model = NotificationSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
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
                Forms\Components\Toggle::make('email_notifications')
                    ->required(),
                Forms\Components\Toggle::make('sms_alerts')
                    ->required(),
                Forms\Components\Toggle::make('trip_completion_alerts')
                    ->required(),
                Forms\Components\Toggle::make('settlement_generated_alerts')
                    ->required(),
                Forms\Components\Toggle::make('payment_status_alerts')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('email_notifications')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sms_alerts')
                    ->boolean(),
                Tables\Columns\IconColumn::make('trip_completion_alerts')
                    ->boolean(),
                Tables\Columns\IconColumn::make('settlement_generated_alerts')
                    ->boolean(),
                Tables\Columns\IconColumn::make('payment_status_alerts')
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
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ManageNotificationSettings::route('/'), // Custom page
            'edit' => Pages\EditNotificationSetting::route('/{record}/edit'),
        ];
    }
}
