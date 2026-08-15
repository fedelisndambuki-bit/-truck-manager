<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;

class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'System Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('Platform-wide')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'gray' => 'info',
                        'warning' => 'warning',
                        'danger' => 'error',
                        'success' => 'success',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->description)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('severity')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'success' => 'Success',
                    ]),
                SelectFilter::make('company_id')
                    ->label('Filter by Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('System Log Details')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('action')->disabled(),
                                Forms\Components\TextInput::make('severity')->disabled(),
                                Forms\Components\TextInput::make('company.name')
                                    ->label('Company')
                                    ->placeholder('Platform-wide')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('created_at')
                                    ->label('Timestamp')
                                    ->disabled(),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull()
                                    ->rows(5)
                                    ->disabled(),
                            ])
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemLogs::route('/'),
        ];
    }
}
