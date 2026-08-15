<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource\Pages;
use App\Models\BlastDocument;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class BlastDocumentResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = BlastManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.view';
    }

    protected static ?string $model = BlastDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?int $navigationSort = 12;

    public static function getCategoryOptions(): array
    {
        return array_combine(BlastDocument::CATEGORIES, BlastDocument::CATEGORIES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('category')->options(self::getCategoryOptions())->required(),
                    Forms\Components\FileUpload::make('file_path')->label('File')->disk('public')->directory('blast-documents')->required(),
                    Forms\Components\Hidden::make('uploaded_by_id')->default(fn () => Auth::id()),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('file_path')->label('File')->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—'),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Uploaded By')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded')->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (BlastDocument $record) => \Illuminate\Support\Facades\Storage::disk('public')->url($record->file_path))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListBlastDocuments::route('/'),
            'create' => Pages\CreateBlastDocument::route('/create'),
            'edit' => Pages\EditBlastDocument::route('/{record}/edit'),
        ];
    }
}
