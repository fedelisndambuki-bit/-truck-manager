<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\UserSecurityResource\Pages;
use App\Models\SystemLog;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSecurityResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Security';

    protected static ?string $navigationLabel = 'User Security';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\DateTimePicker::make('locked_until'),
            Forms\Components\Toggle::make('two_factor_enabled'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('company.name')->label('Company')->placeholder('N/A'),
            Tables\Columns\IconColumn::make('two_factor_enabled')->boolean()->label('2FA'),
            Tables\Columns\TextColumn::make('locked_until')->dateTime()->placeholder('Not locked'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('lock_24h')
                ->color('danger')
                ->icon('heroicon-o-lock-closed')
                ->action(function (User $record) {
                    $record->update(['locked_until' => now()->addDay()]);
                    SystemLog::create([
                        'company_id' => $record->company_id,
                        'action' => 'user_locked',
                        'severity' => 'warning',
                        'description' => 'Tanaki Control Room locked user ' . $record->email . ' for 24h.',
                    ]);
                }),
            Tables\Actions\Action::make('unlock')
                ->color('success')
                ->icon('heroicon-o-lock-open')
                ->action(fn (User $record) => $record->update(['locked_until' => null])),
            Tables\Actions\Action::make('force_logout')
                ->color('warning')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->action(fn (User $record) => $record->update(['force_logout_at' => now()])),
            Tables\Actions\Action::make('toggle_2fa')
                ->label('Toggle 2FA')
                ->icon('heroicon-o-finger-print')
                ->action(function (User $record) {
                    $enabled = ! (bool) $record->two_factor_enabled;
                    $record->update([
                        'two_factor_enabled' => $enabled,
                        'two_factor_method' => $enabled ? ($record->two_factor_method ?: 'email') : null,
                    ]);
                }),
            Tables\Actions\Action::make('reset_password')
                ->icon('heroicon-o-key')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (User $record) {
                    $temporaryPassword = Str::password(10)->toString();
                    $record->update([
                        'password' => Hash::make($temporaryPassword),
                        'force_logout_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Temporary Password Generated')
                        ->warning()
                        ->body("{$record->email}: {$temporaryPassword}")
                        ->send();
                }),
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserSecurities::route('/'),
            'edit' => Pages\EditUserSecurity::route('/{record}/edit'),
        ];
    }
}
