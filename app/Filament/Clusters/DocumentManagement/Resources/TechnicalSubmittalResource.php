<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource\Pages;
use App\Models\TechnicalSubmittal;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TechnicalSubmittalResource extends Resource
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

    protected static ?string $model = TechnicalSubmittal::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Technical Submittals';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['project', 'subcontractor']);
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
                Forms\Components\Section::make('Submittal Details')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('type')->options([
                        'Material' => 'Material Submittal', 'Equipment' => 'Equipment Submittal',
                        'Specification' => 'Technical Specification', 'Sample' => 'Sample',
                    ])->default('Material')->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('subcontractor_id')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                        'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/technical-submittals')
                        ->openable()->downloadable()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submittal_number')->label('Submittal #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'Material' => 'Material', 'Equipment' => 'Equipment', 'Specification' => 'Specification', 'Sample' => 'Sample',
                ]),
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
                    ->visible(fn (TechnicalSubmittal $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('documents.approve'))
                    ->requiresConfirmation()
                    ->action(fn (TechnicalSubmittal $record) => $record->update(['status' => 'Approved'])),
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
            'index' => Pages\ListTechnicalSubmittals::route('/'),
            'create' => Pages\CreateTechnicalSubmittal::route('/create'),
            'edit' => Pages\EditTechnicalSubmittal::route('/{record}/edit'),
        ];
    }
}
