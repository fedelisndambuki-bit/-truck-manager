<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource\Pages;
use App\Models\QualityStandard;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Standards" — KEBS, ASTM, ISO, BS EN, AASHTO, Customer/Government
 * Specifications. Referenced by TestMethod::quality_standard_id.
 */
class QualityStandardResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = LaboratoryQualityControl::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $model = QualityStandard::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Standards';

    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quality Standard')->schema([
                    Forms\Components\TextInput::make('standard_code')->label('Standard Code')->required(),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('issuing_body')->label('Issuing Body'),
                    Forms\Components\TextInput::make('version'),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('standard_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('issuing_body')->label('Issuing Body')->placeholder('—'),
                Tables\Columns\TextColumn::make('version')->placeholder('—'),
                Tables\Columns\TextColumn::make('test_methods_count')->label('Test Methods')->counts('testMethods'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['standard_code', 'name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityStandards::route('/'),
            'create' => Pages\CreateQualityStandard::route('/create'),
            'view' => Pages\ViewQualityStandard::route('/{record}'),
            'edit' => Pages\EditQualityStandard::route('/{record}/edit'),
        ];
    }
}
