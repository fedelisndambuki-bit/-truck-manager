<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource\Pages;
use App\Models\BlastDrillHole;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlastDrillHoleResource extends Resource
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

    protected static ?string $model = BlastDrillHole::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationLabel = 'Drilling';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Drill Hole Details')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('hole_number')->required(),
                    Forms\Components\TextInput::make('diameter')->numeric()->suffix('mm'),
                    Forms\Components\TextInput::make('depth')->numeric()->suffix('m'),
                    Forms\Components\TextInput::make('inclination')->numeric()->suffix('°'),
                    Forms\Components\Select::make('drill_rig_id')->label('Drill Rig')->relationship('drillRig', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\DateTimePicker::make('start_time'),
                    Forms\Components\DateTimePicker::make('finish_time'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('hole_number')->label('Hole #'),
                Tables\Columns\TextColumn::make('diameter')->numeric()->suffix(' mm')->placeholder('—'),
                Tables\Columns\TextColumn::make('depth')->numeric()->suffix(' m')->placeholder('—'),
                Tables\Columns\TextColumn::make('drillRig.name')->label('Drill Rig')->placeholder('—'),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('start_time')->dateTime('d M Y H:i')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('blast_plan_id')->label('Blast Plan')->relationship('blastPlan', 'blast_number'),
                Tables\Filters\SelectFilter::make('drill_rig_id')->label('Drill Rig')->relationship('drillRig', 'name'),
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
            'index' => Pages\ListBlastDrillHoles::route('/'),
            'create' => Pages\CreateBlastDrillHole::route('/create'),
            'edit' => Pages\EditBlastDrillHole::route('/{record}/edit'),
        ];
    }
}
