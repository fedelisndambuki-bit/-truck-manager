<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\TransmittalResource\Pages;
use App\Models\Transmittal;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransmittalResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'documents.view';
    }

    protected static ?string $model = Transmittal::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationLabel = 'Transmittals';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'transmittal_number';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transmittal Details')->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('sender_id')
                        ->label('Sender')
                        ->relationship('sender', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('recipient_name')->required(),
                    Forms\Components\TextInput::make('recipient_email')->email(),
                    Forms\Components\Select::make('purpose')->options([
                        'For Approval' => 'For Approval', 'For Review' => 'For Review',
                        'For Information' => 'For Information', 'For Construction' => 'For Construction',
                    ]),
                    Forms\Components\Select::make('delivery_method')->options([
                        'Email' => 'Email', 'Hand Delivery' => 'Hand Delivery', 'Courier' => 'Courier', 'Portal' => 'Portal',
                    ])->default('Email'),
                    Forms\Components\Textarea::make('comments')->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('Documents Included')->schema([
                    Forms\Components\Select::make('documents')
                        ->relationship('documents', 'title')
                        ->multiple()->searchable()->preload(),
                    Forms\Components\Select::make('drawings')
                        ->relationship('drawings', 'title')
                        ->multiple()->searchable()->preload(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transmittal_number')->label('Transmittal #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('recipient_name')->label('Recipient')->searchable(),
                Tables\Columns\TextColumn::make('purpose')->badge()->color('info'),
                Tables\Columns\TextColumn::make('delivery_method')->toggleable(),
                Tables\Columns\TextColumn::make('documents_count')->counts('documents')->label('Items'),
                Tables\Columns\IconColumn::make('acknowledged_at')->label('Acknowledged')->boolean()
                    ->getStateUsing(fn (Transmittal $record) => (bool) $record->acknowledged_at),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('purpose')->options([
                    'For Approval' => 'For Approval', 'For Review' => 'For Review',
                    'For Information' => 'For Information', 'For Construction' => 'For Construction',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('acknowledge')
                    ->label('Acknowledge')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Transmittal $record) => ! $record->acknowledged_at)
                    ->requiresConfirmation()
                    ->action(fn (Transmittal $record) => $record->acknowledge()),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTransmittals::route('/'),
            'create' => Pages\CreateTransmittal::route('/create'),
            'edit' => Pages\EditTransmittal::route('/{record}/edit'),
        ];
    }
}
