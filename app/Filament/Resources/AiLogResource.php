<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiLogResource\Pages;
use App\Models\AiLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms;

use App\Traits\HasModuleAccess;

class AiLogResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'ai-insights';
    }

    protected static ?string $model = AiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'AI Logs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('question')->disabled(),
            Forms\Components\Textarea::make('response')->rows(12)->disabled(),
            Forms\Components\KeyValue::make('fleet_data')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('response_time_ms')->label('Response Time (ms)')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')->label('User')->sortable(),
            Tables\Columns\TextColumn::make('question')->limit(60)->searchable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'success' => 'success',
                'error' => 'danger',
            ]),
            Tables\Columns\TextColumn::make('response_time_ms')->label('ms')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])->filters([])->actions([
            Tables\Actions\ViewAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiLogs::route('/'),
            'view' => Pages\ViewAiLog::route('/{record}'),
        ];
    }
}

