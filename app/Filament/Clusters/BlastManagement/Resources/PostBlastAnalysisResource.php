<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\PostBlastAnalysisResource\Pages;
use App\Models\PostBlastAnalysis;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostBlastAnalysisResource extends Resource
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

    protected static ?string $model = PostBlastAnalysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Post-Blast Analysis';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Post-Blast Analysis')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('rock_fragmentation'),
                    Forms\Components\TextInput::make('oversized_rocks')->numeric()->suffix('%'),
                    Forms\Components\Toggle::make('fly_rock')->live(),
                    Forms\Components\Textarea::make('fly_rock_notes')->visible(fn (Forms\Get $get) => (bool) $get('fly_rock')),
                    Forms\Components\TextInput::make('back_break')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('toe_condition'),
                    Forms\Components\TextInput::make('bench_stability'),
                    Forms\Components\TextInput::make('diggability'),
                    Forms\Components\TextInput::make('production_readiness'),
                    Forms\Components\Textarea::make('recommendations')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('rock_fragmentation')->placeholder('—'),
                Tables\Columns\TextColumn::make('oversized_rocks')->numeric()->suffix('%')->placeholder('—'),
                Tables\Columns\IconColumn::make('fly_rock')->boolean(),
                Tables\Columns\TextColumn::make('production_readiness')->placeholder('—'),
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
            'index' => Pages\ListPostBlastAnalyses::route('/'),
            'create' => Pages\CreatePostBlastAnalysis::route('/create'),
            'edit' => Pages\EditPostBlastAnalysis::route('/{record}/edit'),
        ];
    }
}
