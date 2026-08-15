<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SafetyDocumentResource\Pages;
use App\Models\Document;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafetyDocumentResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'safety.view';
    }

    protected static ?string $model = Document::class;

    protected static ?string $slug = 'safety-documents';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $navigationGroup = 'Safety & Incident Management';

    protected static ?int $navigationSort = 150;

    public const SAFETY_CATEGORIES = [
        'Risk Assessment', 'Method Statement', 'Safety Plan', 'Permit', 'Inspection Report',
        'Training Certificate', 'Medical Certificate', 'Incident Report', 'Emergency Plan', 'Safety Report',
    ];

    public static function getCategoryOptions(): array
    {
        return array_combine(self::SAFETY_CATEGORIES, self::SAFETY_CATEGORIES);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('category', self::SAFETY_CATEGORIES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Select::make('category')->options(self::getCategoryOptions())->default('Safety Plan')->required(),
                    Forms\Components\Select::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('site_id')->label('Site')->relationship('site', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('staff_id')->label('Employee')->relationship('staff', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('subcontractor_id')->label('Subcontractor')->relationship('subcontractor', 'company_name')->searchable()->preload(),
                    Forms\Components\FileUpload::make('file_path')
                        ->label('File')
                        ->disk('public')
                        ->directory('safety-documents')
                        ->openable()
                        ->downloadable()
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Safety Plan' => 'info', 'Permit' => 'warning', 'Inspection Report' => 'success',
                        'Incident Report', 'Safety Report' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('project.name')->label('Project')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable(),
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSafetyDocuments::route('/'),
            'create' => Pages\CreateSafetyDocument::route('/create'),
            'edit' => Pages\EditSafetyDocument::route('/{record}/edit'),
        ];
    }
}
