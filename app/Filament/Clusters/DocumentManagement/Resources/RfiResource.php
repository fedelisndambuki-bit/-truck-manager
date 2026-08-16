<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\RfiResource\Pages;
use App\Models\Rfi;
use App\Services\Construction\DocumentManagementService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RfiResource extends Resource
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

    protected static ?string $model = Rfi::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'RFIs';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'raisedBy', 'assignedTo']);
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Answered', 'Closed' => 'success',
            'Overdue' => 'danger',
            default => 'warning',
        };
    }

    public static function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'Urgent' => 'danger',
            'High' => 'warning',
            'Low' => 'gray',
            default => 'info',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('RFI Details')->schema([
                    Forms\Components\TextInput::make('subject')->required()->columnSpanFull(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('raised_by_id')
                        ->label('Raised By')
                        ->relationship('raisedBy', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('assigned_to_id')
                        ->label('Assigned To')
                        ->relationship('assignedTo', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('priority')->options([
                        'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent',
                    ])->default('Medium')->required(),
                    Forms\Components\DatePicker::make('due_date'),
                    Forms\Components\Select::make('status')->options([
                        'Open' => 'Open', 'Answered' => 'Answered', 'Closed' => 'Closed', 'Overdue' => 'Overdue',
                    ])->default('Open')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\FileUpload::make('attachment_path')
                        ->disk('public')->directory('edms/rfis')
                        ->openable()->downloadable()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rfi_number')->label('RFI #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('raisedBy.full_name')->label('Raised By')->toggleable(),
                Tables\Columns\TextColumn::make('assignedTo.full_name')->label('Assigned To')->toggleable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => static::getPriorityColor($state)),
                Tables\Columns\TextColumn::make('due_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('responses_count')->counts('responses')->label('Responses'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('priority')->options([
                    'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Open' => 'Open', 'Answered' => 'Answered', 'Closed' => 'Closed', 'Overdue' => 'Overdue',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('respond')
                    ->label('Respond')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->visible(fn (Rfi $record) => $record->status === 'Open')
                    ->form([
                        Forms\Components\Select::make('responded_by_id')
                            ->label('Responded By')
                            ->relationship('assignedTo', 'full_name')
                            ->searchable()->preload(),
                        Forms\Components\Textarea::make('response')->required(),
                    ])
                    ->action(function (Rfi $record, array $data) {
                        $record->responses()->create([
                            'company_id' => $record->company_id,
                            'responded_by_id' => $data['responded_by_id'] ?? null,
                            'response' => $data['response'],
                            'responded_at' => now(),
                        ]);
                        app(DocumentManagementService::class)->logAudit($record, 'edited', ['action' => 'response added']);
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(fn (Rfi $record) => $record->status === 'Answered')
                    ->requiresConfirmation()
                    ->action(fn (Rfi $record) => $record->update(['status' => 'Closed'])),
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
            'index' => Pages\ListRfis::route('/'),
            'create' => Pages\CreateRfi::route('/create'),
            'edit' => Pages\EditRfi::route('/{record}/edit'),
        ];
    }
}
