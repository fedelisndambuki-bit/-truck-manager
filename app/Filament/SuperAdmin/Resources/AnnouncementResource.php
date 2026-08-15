<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Announcements';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Textarea::make('message')->required()->columnSpanFull(),
            Forms\Components\Select::make('audience')->options([
                'all' => 'All Companies',
                'company' => 'Specific Company',
            ])->required()->default('all')->live(),
            Forms\Components\Select::make('target_company_id')
                ->label('Target Company')
                ->relationship('targetCompany', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("Company #{$record->id}"))
                ->searchable()
                ->preload()
                ->visible(fn (Forms\Get $get) => $get('audience') === 'company'),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\DateTimePicker::make('active_from'),
            Forms\Components\DateTimePicker::make('expires_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('audience')->badge(),
            Tables\Columns\TextColumn::make('targetCompany.name')->label('Company')->placeholder('All'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('active_from')->dateTime()->placeholder('-'),
            Tables\Columns\TextColumn::make('expires_at')->dateTime()->placeholder('-'),
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
