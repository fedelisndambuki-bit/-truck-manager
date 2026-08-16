<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\MethodStatementResource\Pages;
use App\Models\MethodStatement;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MethodStatementResource extends Resource
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

    protected static ?string $model = MethodStatement::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Method Statements';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'riskAssessment']);
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
                Forms\Components\Section::make('Method Statement Details')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('risk_assessment_id')
                        ->label('Related Risk Assessment')
                        ->relationship('riskAssessment', 'assessment_number')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                        'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('related_activities')->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/method-statements')
                        ->openable()->downloadable()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->label('Reference #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('riskAssessment.assessment_number')->label('Risk Assessment')->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MethodStatement $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('documents.approve'))
                    ->requiresConfirmation()
                    ->action(fn (MethodStatement $record) => $record->update(['status' => 'Approved'])),
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
            'index' => Pages\ListMethodStatements::route('/'),
            'create' => Pages\CreateMethodStatement::route('/create'),
            'edit' => Pages\EditMethodStatement::route('/{record}/edit'),
        ];
    }
}
