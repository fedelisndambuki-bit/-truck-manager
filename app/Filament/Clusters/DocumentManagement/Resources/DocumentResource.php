<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource\Pages;
use App\Models\EdmsDocument;
use App\Services\Construction\DocumentManagementService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class DocumentResource extends Resource
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

    protected static ?string $model = EdmsDocument::class;

    protected static ?string $cluster = DocumentManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Document Library';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('status', '!=', 'Archived');
    }

    public static function getCategoryOptions(): array
    {
        return [
            'Architectural Drawings' => 'Architectural Drawings',
            'Structural Drawings' => 'Structural Drawings',
            'MEP Drawings' => 'MEP Drawings',
            'Civil Drawings' => 'Civil Drawings',
            'Contracts' => 'Contracts',
            'BOQs' => 'BOQs',
            'Method Statements' => 'Method Statements',
            'Inspection Reports' => 'Inspection Reports',
            'Quality Reports' => 'Quality Reports',
            'Safety Plans' => 'Safety Plans',
            'Permits' => 'Permits',
            'Meeting Minutes' => 'Meeting Minutes',
            'Invoices' => 'Invoices',
            'Certificates' => 'Certificates',
            'Equipment Manuals' => 'Equipment Manuals',
            'Supplier Documents' => 'Supplier Documents',
            'Subcontractor Documents' => 'Subcontractor Documents',
            'Training Records' => 'Training Records',
            'Site Photos' => 'Site Photos',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Submitted', 'Under Review' => 'warning',
            'Rejected' => 'danger',
            'Revision Required' => 'danger',
            'Archived' => 'gray',
            default => 'gray',
        };
    }

    public static function previewTypeFor(?string $extension): string
    {
        $extension = strtolower((string) $extension);

        return match (true) {
            $extension === 'pdf' => 'pdf',
            in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => 'image',
            in_array($extension, ['mp4', 'mov', 'avi', 'webm']) => 'video',
            in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']) => 'office',
            in_array($extension, ['dwg', 'dxf']) => 'cad',
            default => 'other',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('category')
                        ->options(static::getCategoryOptions())
                        ->searchable()->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('folder_id')
                        ->label('Folder')
                        ->relationship('folder', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('department'),
                    Forms\Components\Select::make('owner_staff_id')
                        ->label('Owner')
                        ->relationship('owner', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                        'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\TagsInput::make('tags')->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),
                Forms\Components\Section::make('File')->schema([
                    Forms\Components\FileUpload::make('file_path')
                        ->label('File')
                        ->disk('public')
                        ->directory(fn ($get) => 'edms/' . str($get('category') ?: 'general')->slug())
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                return;
                            }
                            $name = method_exists($state, 'getClientOriginalName') ? $state->getClientOriginalName() : (string) $state;
                            $ext = pathinfo($name, PATHINFO_EXTENSION);
                            $set('file_type', $ext);
                            $set('preview_type', static::previewTypeFor($ext));
                            if (method_exists($state, 'getSize')) {
                                $set('file_size', $state->getSize());
                            }
                        }),
                    Forms\Components\Hidden::make('file_type'),
                    Forms\Components\Hidden::make('preview_type'),
                    Forms\Components\Hidden::make('file_size'),
                ]),
                Forms\Components\Section::make('Retention & Review')->schema([
                    Forms\Components\DatePicker::make('review_date'),
                    Forms\Components\TextInput::make('retention_period_days')->numeric()->suffix('days'),
                    Forms\Components\DatePicker::make('expiry_date'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->label('Doc #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('category')->badge()->color('info')->toggleable(),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\TextColumn::make('file_size_for_humans')->label('Size')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('owner.full_name')->label('Owner')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => static::getStatusColor($state)),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(static::getCategoryOptions()),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')->options([
                    'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Revision Required' => 'Revision Required',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->visible(fn (EdmsDocument $record) => $record->status === 'Draft')
                    ->form([
                        Forms\Components\CheckboxList::make('approvers')
                            ->options([
                                'Site Engineer' => 'Site Engineer', 'Project Manager' => 'Project Manager',
                                'Construction Manager' => 'Construction Manager', 'Consultant' => 'Consultant',
                                'Client Representative' => 'Client Representative', 'Managing Director' => 'Managing Director',
                            ])
                            ->default(['Project Manager'])
                            ->required(),
                    ])
                    ->action(function (EdmsDocument $record, array $data) {
                        app(DocumentManagementService::class)->startApprovalWorkflow($record, $data['approvers']);
                    }),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (EdmsDocument $record) => in_array($record->status, ['Submitted', 'Under Review']) && Auth::user()?->can('documents.approve'))
                    ->requiresConfirmation()
                    ->action(function (EdmsDocument $record) {
                        $step = $record->pendingApproval();
                        if ($step) {
                            $step->approve();
                        }
                        app(DocumentManagementService::class)->logAudit($record, 'approved');
                    }),
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (EdmsDocument $record) => filled($record->file_path)
                        && (Auth::user()?->canOnProject($record->project, 'can_download_documents') ?? false))
                    ->action(function (EdmsDocument $record) {
                        app(DocumentManagementService::class)->logAudit($record, 'downloaded');

                        return response()->streamDownload(
                            fn () => print(\Illuminate\Support\Facades\Storage::disk($record->disk ?: 'public')->get($record->file_path)),
                            basename($record->file_path)
                        );
                    }),
                Tables\Actions\Action::make('share')
                    ->label('Share')
                    ->icon('heroicon-o-share')
                    ->color('gray')
                    ->visible(fn () => Auth::user()?->can('documents.share'))
                    ->form([
                        Forms\Components\Select::make('shared_with_type')->options([
                            'internal_staff' => 'Internal Staff', 'client' => 'Client', 'consultant' => 'Consultant',
                            'architect' => 'Architect', 'engineer' => 'Engineer', 'subcontractor' => 'Subcontractor',
                            'supplier' => 'Supplier', 'external_auditor' => 'External Auditor',
                        ])->required(),
                        Forms\Components\TextInput::make('shared_with_name')->label('Name'),
                        Forms\Components\TextInput::make('shared_with_email')->label('Email')->email(),
                        Forms\Components\Select::make('access_level')->options(['view' => 'View', 'download' => 'Download'])->default('view'),
                    ])
                    ->action(fn (EdmsDocument $record, array $data) => app(DocumentManagementService::class)->shareDocument($record, $data)),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
