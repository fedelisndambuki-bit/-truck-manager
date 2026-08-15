<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource\Pages;
use App\Models\TestMethod;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Test Methods" — the laboratory's maintained procedures library (Sieve
 * Analysis, Flakiness Index, Los Angeles Abrasion, etc.), each tied to a
 * QualityStandard and referenced by LabTest::test_method_id.
 */
class TestMethodResource extends Resource
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

    protected static ?string $model = TestMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Test Methods';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Method')->schema([
                    Forms\Components\TextInput::make('method_code')->label('Method Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('method_name')->required(),
                    Forms\Components\Select::make('quality_standard_id')->label('Testing Standard')
                        ->relationship('qualityStandard', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('revision')->label('Revision'),
                    Forms\Components\Select::make('status')->options(array_combine(TestMethod::STATUSES, TestMethod::STATUSES))->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Procedure')->schema([
                    Forms\Components\Textarea::make('testing_steps')->label('Testing Steps')->rows(4)->columnSpanFull(),
                    Forms\Components\Textarea::make('required_equipment')->rows(2),
                    Forms\Components\Textarea::make('required_materials')->rows(2),
                ])->columns(2),

                Forms\Components\Section::make('Criteria')->schema([
                    Forms\Components\TagsInput::make('applicable_products')->label('Applicable Products')->columnSpanFull(),
                    Forms\Components\KeyValue::make('acceptance_criteria')->label('Acceptance Criteria')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('method_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('method_name')->searchable(),
                Tables\Columns\TextColumn::make('qualityStandard.name')->label('Standard')->placeholder('—'),
                Tables\Columns\TextColumn::make('revision')->placeholder('—'),
                Tables\Columns\TextColumn::make('tests_count')->label('Tests Run')->counts('tests'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(TestMethod::STATUSES, TestMethod::STATUSES)),
                Tables\Filters\SelectFilter::make('quality_standard_id')->label('Standard')->relationship('qualityStandard', 'name'),
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
        return ['method_code', 'method_name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestMethods::route('/'),
            'create' => Pages\CreateTestMethod::route('/create'),
            'edit' => Pages\EditTestMethod::route('/{record}/edit'),
        ];
    }
}
