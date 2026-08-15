<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\HasModuleAccess;
use Illuminate\Database\Eloquent\Builder;

class NotificationResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'user-management';
    }

    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('target_company_id', auth()->user()->company_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Compose Notification')
                ->description('Send a message to all drivers in your company.')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->placeholder('e.g. Salaries Release Update')
                        ->required(),
                    Forms\Components\Textarea::make('message')
                        ->label('Content')
                        ->placeholder('e.g. Salaries to be released by 2nd of this month')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\Hidden::make('target_company_id')
                        ->default(fn () => auth()->user()->company_id),
                    Forms\Components\Hidden::make('audience')
                        ->default('company'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Publish immediately')
                        ->default(true),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expiry Date (Optional)')
                        ->helperText('Notification will disappear from the app after this date.'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->limit(50),
            Tables\Columns\TextColumn::make('is_active')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            Tables\Columns\TextColumn::make('created_at')->label('Sent On')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('expires_at')
                ->label('Expires')
                ->dateTime()
                ->placeholder('Never'),
        ])
        ->filters([
            Tables\Filters\TernaryFilter::make('is_active')->label('Currently Active'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
