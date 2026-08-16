<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource\Pages;
use App\Models\EdmsDocument;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QualityDocumentResource extends Resource
{
    use HasModuleAccess;

    protected const CATEGORIES = ['Inspection Reports', 'Quality Reports'];

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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Quality Documents';

    protected static ?int $navigationSort = 13;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('category', static::CATEGORIES)->with(['project', 'site']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('category')->options([
                        'Inspection Reports' => 'Inspection Reports', 'Quality Reports' => 'Quality Reports',
                    ])->default('Inspection Reports')->required(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('site_id')
                        ->relationship('site', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Approved' => 'Approved',
                        'Rejected' => 'Rejected', 'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/quality')
                        ->openable()->downloadable()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->label('Doc #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(35),
                Tables\Columns\TextColumn::make('category')->badge()->color('info'),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => DocumentResource::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options([
                    'Inspection Reports' => 'Inspection Reports', 'Quality Reports' => 'Quality Reports',
                ]),
                Tables\Filters\SelectFilter::make('project_id')->label('Project')->relationship('project', 'name'),
            ])
            ->actions([
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
            'index' => Pages\ListQualityDocuments::route('/'),
            'create' => Pages\CreateQualityDocument::route('/create'),
            'edit' => Pages\EditQualityDocument::route('/{record}/edit'),
        ];
    }
}
