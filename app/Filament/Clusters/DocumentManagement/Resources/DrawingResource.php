<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\DrawingResource\Pages;
use App\Models\Drawing;
use App\Services\Construction\DocumentManagementService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DrawingResource extends Resource
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

    protected static ?string $model = Drawing::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Drawings';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'Drawing');
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Submitted', 'Under Review' => 'warning',
            'Rejected', 'Revision Required' => 'danger',
            'Archived' => 'gray',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('type')->default('Drawing'),
                Forms\Components\Section::make('Drawing Details')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('discipline')->options([
                        'Architectural' => 'Architectural', 'Structural' => 'Structural',
                        'MEP' => 'MEP', 'Civil' => 'Civil',
                    ])->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                        'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('File')->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/drawings')
                        ->openable()->downloadable()->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('drawing_number')->label('Drawing #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('discipline')->badge()->color('info'),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\IconColumn::make('is_superseded')->label('Superseded')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('discipline')->options([
                    'Architectural' => 'Architectural', 'Structural' => 'Structural', 'MEP' => 'MEP', 'Civil' => 'Civil',
                ]),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('newRevision')
                    ->label('New Revision')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Forms\Components\FileUpload::make('file_path')->disk('public')->directory('edms/drawings')->required(),
                        Forms\Components\Textarea::make('change_notes'),
                    ])
                    ->action(fn (Drawing $record, array $data) => app(DocumentManagementService::class)->createVersion($record, $data)),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Drawing $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('documents.approve'))
                    ->requiresConfirmation()
                    ->action(function (Drawing $record) {
                        $step = $record->pendingApproval();
                        if ($step) {
                            $step->approve();
                        } else {
                            $record->update(['status' => 'Approved']);
                        }
                        app(DocumentManagementService::class)->logAudit($record, 'approved');
                    }),
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
            'index' => Pages\ListDrawings::route('/'),
            'create' => Pages\CreateDrawing::route('/create'),
            'edit' => Pages\EditDrawing::route('/{record}/edit'),
        ];
    }
}
