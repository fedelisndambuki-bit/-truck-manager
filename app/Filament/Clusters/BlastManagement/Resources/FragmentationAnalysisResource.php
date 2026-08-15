<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\FragmentationAnalysisResource\Pages;
use App\Models\FragmentationAnalysis;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FragmentationAnalysisResource extends Resource
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

    protected static ?string $model = FragmentationAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $navigationLabel = 'Fragmentation Analysis';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Fragmentation Analysis')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('average_fragment_size')->numeric()->suffix('cm'),
                    Forms\Components\TextInput::make('maximum_fragment')->numeric()->suffix('cm'),
                    Forms\Components\TextInput::make('minimum_fragment')->numeric()->suffix('cm'),
                    Forms\Components\TextInput::make('oversized_percentage')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('fines_percentage')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('crusher_feed_suitability'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('average_fragment_size')->label('Avg Fragment')->numeric()->suffix(' cm')->placeholder('—'),
                Tables\Columns\TextColumn::make('oversized_percentage')->label('Oversized %')->numeric()->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('fines_percentage')->label('Fines %')->numeric()->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('crusher_feed_suitability')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListFragmentationAnalyses::route('/'),
            'create' => Pages\CreateFragmentationAnalysis::route('/create'),
            'edit' => Pages\EditFragmentationAnalysis::route('/{record}/edit'),
        ];
    }
}
