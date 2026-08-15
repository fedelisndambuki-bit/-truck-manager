<?php

namespace App\Filament\Clusters\BlastManagement\Resources;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource\Pages;
use App\Models\BlastSafetyZone;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlastSafetyZoneResource extends Resource
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

    protected static ?string $model = BlastSafetyZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Blast Zones';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Exclusion Zone')->schema([
                    Forms\Components\Select::make('blast_plan_id')->label('Blast Plan')
                        ->relationship('blastPlan', 'blast_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->blast_number ?? ('Blast #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('safety_radius')->numeric()->suffix('m'),
                    Forms\Components\Toggle::make('warning_sirens'),
                    Forms\Components\Toggle::make('clearance_confirmed')->live(),
                    Forms\Components\Select::make('clearance_confirmed_by_id')->label('Clearance Confirmed By')->relationship('clearanceConfirmedBy', 'full_name')->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => (bool) $get('clearance_confirmed')),
                    Forms\Components\Textarea::make('restricted_areas')->columnSpanFull(),
                    Forms\Components\Textarea::make('evacuation_routes')->columnSpanFull(),
                    Forms\Components\Textarea::make('assembly_points')->columnSpanFull(),
                    Forms\Components\Textarea::make('road_closures')->columnSpanFull(),
                    Forms\Components\Textarea::make('access_control')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('blastPlan.blast_number')->label('Blast #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('blastPlan.blast_name')->label('Blast Name'),
                Tables\Columns\TextColumn::make('safety_radius')->numeric()->suffix(' m')->placeholder('—'),
                Tables\Columns\IconColumn::make('warning_sirens')->boolean(),
                Tables\Columns\IconColumn::make('clearance_confirmed')->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('clearance_confirmed'),
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
            'index' => Pages\ListBlastSafetyZones::route('/'),
            'create' => Pages\CreateBlastSafetyZone::route('/create'),
            'edit' => Pages\EditBlastSafetyZone::route('/{record}/edit'),
        ];
    }
}
