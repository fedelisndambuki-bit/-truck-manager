<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentDocument;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EquipmentDocumentResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = EquipmentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?int $navigationSort = 23;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['documentable', 'uploadedBy']);
    }

    public static function getCategoryOptions(): array
    {
        return array_combine(EquipmentDocument::CATEGORIES, EquipmentDocument::CATEGORIES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('documentable_id')
                    ->label('Equipment')
                    ->options(fn () => Equipment::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Hidden::make('documentable_type')->default(Equipment::class),
                Forms\Components\TextInput::make('title'),
                Forms\Components\Select::make('category')->options(self::getCategoryOptions())->required(),
                Forms\Components\FileUpload::make('file_path')->disk('public')->directory('equipment-documents')->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('documentable_type', Equipment::class))
            ->columns([
                Tables\Columns\TextColumn::make('title')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('documentable.name')->label('Equipment'),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Uploaded By')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (EquipmentDocument $record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentDocuments::route('/'),
            'create' => Pages\CreateEquipmentDocument::route('/create'),
            'edit' => Pages\EditEquipmentDocument::route('/{record}/edit'),
        ];
    }
}
