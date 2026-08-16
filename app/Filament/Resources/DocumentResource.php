<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 15;

    public static function getCategoryOptions(): array
    {
        return [
            'Blueprint' => 'Blueprint',
            'Architectural Drawings' => 'Architectural Drawings',
            'Structural Drawings' => 'Structural Drawings',
            'BOQ' => 'BOQ',
            'Contract' => 'Contract',
            'Permit' => 'Permit',
            'Environmental Certificates' => 'Environmental Certificates',
            'Insurance' => 'Insurance',
            'Safety Plan' => 'Safety Plan',
            'Inspection Report' => 'Inspection Report',
            'Safety Report' => 'Safety Report',
            'Invoice' => 'Invoice',
            'Supporting Documents' => 'Supporting Documents',
            'Manuals' => 'Manuals',
            'Warranty' => 'Warranty',
            'Inspection Certificates' => 'Inspection Certificates',
            'Maintenance Records' => 'Maintenance Records',
            'Purchase Documents' => 'Purchase Documents',
            'Employment Contract' => 'Employment Contract',
            'National ID' => 'National ID',
            'Passport' => 'Passport',
            'KRA PIN Certificate' => 'KRA PIN Certificate',
            'NSSF Card' => 'NSSF Card',
            'SHA Card' => 'SHA Card',
            'Training Certificate' => 'Training Certificate',
            'Professional Certificate' => 'Professional Certificate',
            'Medical Certificate' => 'Medical Certificate',
            'Performance Review' => 'Performance Review',
            'Disciplinary Record' => 'Disciplinary Record',
            'Purchase Order' => 'Purchase Order',
            'Delivery Note' => 'Delivery Note',
            'GRN' => 'GRN',
            'Material Datasheet' => 'Material Datasheet',
            'Material Safety Datasheet' => 'Material Safety Datasheet',
            'Supplier Contract' => 'Supplier Contract',
            'Subcontract Agreement' => 'Subcontract Agreement',
            'Subcontractor Insurance' => 'Subcontractor Insurance',
            'Subcontractor License' => 'Subcontractor License',
            'Variation Order' => 'Variation Order',
            'Payment Certificate' => 'Payment Certificate',
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['project', 'site', 'equipment', 'staff', 'material', 'subcontractor', 'contract']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Details')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('equipment_id')
                        ->label('Equipment')
                        ->relationship('equipment', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('staff_id')
                        ->label('Employee')
                        ->relationship('staff', 'full_name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('grn_id')
                        ->label('Goods Received Note')
                        ->relationship('goodsReceivedNote', 'grn_number')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('subcontractor_id')
                        ->label('Subcontractor')
                        ->relationship('subcontractor', 'company_name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('contract_id')
                        ->label('Contract')
                        ->relationship('contract', 'contract_number')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('variation_id')
                        ->label('Variation')
                        ->relationship('variation', 'variation_number')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('category')
                        ->options(self::getCategoryOptions())
                        ->default('Contract')
                        ->required(),
                    Forms\Components\FileUpload::make('file_path')
                        ->label('File')
                        ->disk('public')
                        ->directory('construction-documents')
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
                Tables\Columns\TextColumn::make('project.name')->label('Project')->toggleable(),
                Tables\Columns\TextColumn::make('site.name')->label('Site')->toggleable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->toggleable(),
                Tables\Columns\TextColumn::make('staff.full_name')->label('Employee')->toggleable(),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->toggleable(),
                Tables\Columns\TextColumn::make('subcontractor.company_name')->label('Subcontractor')->toggleable(),
                Tables\Columns\TextColumn::make('contract.contract_number')->label('Contract')->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Blueprint' => 'info',
                        'Contract' => 'gray',
                        'Permit' => 'warning',
                        'Inspection Report' => 'success',
                        'Safety Report' => 'danger',
                        'Invoice' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record): string => Storage::disk($record->disk)->url($record->file_path))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
