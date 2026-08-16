<?php

namespace App\Filament\Clusters\DocumentManagement\Resources;

use App\Filament\Clusters\DocumentManagement;
use App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource\Pages;
use App\Models\EdmsDocument;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BoqDocumentResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'BOQs';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('category', 'BOQs')->with(['project']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('category')->default('BOQs'),
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')->options([
                        'Draft' => 'Draft', 'Submitted' => 'Submitted', 'Under Review' => 'Under Review',
                        'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Archived' => 'Archived',
                    ])->default('Draft')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\FileUpload::make('file_path')
                        ->disk('public')->directory('edms/boqs')
                        ->openable()->downloadable()->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_number')->label('Doc #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('project.name')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('current_version')->label('Version'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => DocumentResource::getStatusColor($state)),
            ])
            ->filters([
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
            'index' => Pages\ListBoqDocuments::route('/'),
            'create' => Pages\CreateBoqDocument::route('/create'),
            'edit' => Pages\EditBoqDocument::route('/{record}/edit'),
        ];
    }
}
