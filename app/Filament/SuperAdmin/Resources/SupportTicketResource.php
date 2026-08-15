<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Support Tickets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("Company #{$record->id}"))
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ("User #{$record->id}"))
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('subject')->required()->maxLength(255),
            Forms\Components\Textarea::make('message')->required()->columnSpanFull(),
            Forms\Components\Select::make('status')->options([
                'open' => 'Open',
                'in_progress' => 'In Progress',
                'resolved' => 'Resolved',
                'closed' => 'Closed',
            ])->required(),
            Forms\Components\Select::make('priority')->options([
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'critical' => 'Critical',
            ])->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('subject')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('company.name')->label('Company')->toggleable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'warning' => 'open',
                'info' => 'in_progress',
                'success' => 'resolved',
                'gray' => 'closed',
            ]),
            Tables\Columns\BadgeColumn::make('priority')->colors([
                'gray' => 'low',
                'info' => 'normal',
                'warning' => 'high',
                'danger' => 'critical',
            ]),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
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
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
