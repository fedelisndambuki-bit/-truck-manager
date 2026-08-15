<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DriverChatMessageResource\Pages;
use App\Models\Driver;
use App\Models\DriverCompanyChatMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Traits\HasModuleAccess;

class DriverChatMessageResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'driver-management';
    }

    public static function getRequiredIndustries(): array
    {
        return ['trucking'];
    }
    protected static ?string $model = DriverCompanyChatMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Driver Submissions';

    protected static ?string $navigationLabel = 'Chat';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        $count = DriverCompanyChatMessage::query()
            ->when(! $user->hasRole('Super Admin'), fn ($q) => $q->where('company_id', $user->company_id))
            ->where('sender_type', 'driver')
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('driver_id')
                ->label('Driver')
                ->options(function () {
                    $user = auth()->user();
                    return Driver::query()
                        ->when($user && ! $user->hasRole('Super Admin'), fn ($q) => $q->where('company_id', $user->company_id))
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (Driver $driver) => [
                            $driver->id => $driver->user->name ?? ('Driver #' . $driver->id),
                        ])
                        ->toArray();
                })
                ->searchable()
                ->required(),
            Forms\Components\Textarea::make('message')
                ->rows(4)
                ->maxLength(2000),
            Forms\Components\FileUpload::make('voice_note_path')
                ->label('Voice Note')
                ->disk('public')
                ->directory('driver/chat-voice-notes')
                ->acceptedFileTypes([
                    'audio/aac',
                    'audio/amr',
                    'audio/mp4',
                    'audio/mpeg',
                    'audio/ogg',
                    'audio/wav',
                    'audio/x-wav',
                ]),
            Forms\Components\FileUpload::make('attachment_path')
                ->label('Attachment')
                ->disk('public')
                ->directory('driver/chat-attachments'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('driver.user.name')
                    ->label('Driver')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sender_type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'driver' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('message')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\IconColumn::make('voice_note_path')
                    ->label('Voice')
                    ->boolean()
                    ->getStateUsing(fn (DriverCompanyChatMessage $record): bool => filled($record->voice_note_path)),
                Tables\Columns\IconColumn::make('attachment_path')
                    ->label('File')
                    ->boolean()
                    ->getStateUsing(fn (DriverCompanyChatMessage $record): bool => filled($record->attachment_path)),
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->getStateUsing(fn (DriverCompanyChatMessage $record): bool => $record->read_at !== null),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sender_type')
                    ->options([
                        'driver' => 'Driver',
                        'company' => 'Company',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->rows(3)
                            ->maxLength(2000),
                        Forms\Components\FileUpload::make('voice_note_path')
                            ->label('Voice Note')
                            ->disk('public')
                            ->directory('driver/chat-voice-notes')
                            ->acceptedFileTypes([
                                'audio/aac',
                                'audio/amr',
                                'audio/mp4',
                                'audio/mpeg',
                                'audio/ogg',
                                'audio/wav',
                                'audio/x-wav',
                            ]),
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Attachment')
                            ->disk('public')
                            ->directory('driver/chat-attachments'),
                    ])
                    ->action(function (DriverCompanyChatMessage $record, array $data): void {
                        $message = trim((string) ($data['message'] ?? ''));
                        if ($message === '') {
                            $message = filled($data['voice_note_path'] ?? null)
                                ? '[Voice Note]'
                                : '[Attachment]';
                        }

                        DriverCompanyChatMessage::create([
                            'company_id' => $record->company_id,
                            'driver_id' => $record->driver_id,
                            'sender_type' => 'company',
                            'sender_user_id' => auth()->id(),
                            'message' => $message,
                            'voice_note_path' => $data['voice_note_path'] ?? null,
                            'attachment_path' => $data['attachment_path'] ?? null,
                            'attachment_name' => filled($data['attachment_path'] ?? null)
                                ? basename((string) $data['attachment_path'])
                                : null,
                        ]);
                    }),
                Tables\Actions\Action::make('markOpened')
                    ->label('Mark Opened')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn (DriverCompanyChatMessage $record): bool => $record->sender_type === 'driver' && $record->read_at === null)
                    ->action(function (DriverCompanyChatMessage $record): void {
                        $record->update(['read_at' => now()]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverChatMessages::route('/'),
            'create' => Pages\CreateDriverChatMessage::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }
}
