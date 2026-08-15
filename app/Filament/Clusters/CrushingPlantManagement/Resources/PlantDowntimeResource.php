<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantDowntimeResource\Pages;
use App\Models\ProductionDowntime;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * A plant-focused view/form over the same ProductionDowntime table Production
 * Management's own DowntimeResource uses — richer equipment FKs (production
 * line/crusher/screen/conveyor/feeder) plus approval_status, all nullable so
 * that existing site/blast-level downtime rows are unaffected.
 */
class PlantDowntimeResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = CrushingPlantManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'plant.view';
    }

    protected static ?string $model = ProductionDowntime::class;

    protected static ?string $navigationIcon = 'heroicon-o-pause-circle';

    protected static ?string $navigationLabel = 'Downtime';

    protected static ?int $navigationSort = 9;

    public static function getReasonOptions(): array
    {
        return array_combine(ProductionDowntime::REASONS, ProductionDowntime::REASONS);
    }

    public static function getApprovalStatusOptions(): array
    {
        return ['Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Downtime Details')->schema([
                    Forms\Components\Select::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name')->searchable()->preload()->live()->required(),
                    Forms\Components\Select::make('production_line_id')->label('Production Line')->relationship('productionLine', 'name', fn ($query, Forms\Get $get) => $query->where('crushing_plant_id', $get('crushing_plant_id')))->searchable()->preload(),
                    Forms\Components\Select::make('reason')->options(self::getReasonOptions())->required(),
                    Forms\Components\Select::make('crusher_id')->label('Affected Crusher')
                        ->relationship('crusher', 'crusher_code')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->crusher_code ?? ('Crusher #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('screen_id')->label('Affected Screen')->relationship('screen', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('conveyor_id')->label('Affected Conveyor')->relationship('conveyor', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('feeder_id')->label('Affected Feeder')->relationship('feeder', 'name')->searchable()->preload(),
                    Forms\Components\DateTimePicker::make('start_time')->required()->default(now()),
                    Forms\Components\DateTimePicker::make('end_time'),
                    Forms\Components\TextInput::make('duration_minutes')->label('Duration')->numeric()->suffix('min')->disabled()->dehydrated(false)->placeholder('Computed from start/end'),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')->relationship('reportedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('approval_status')->options(self::getApprovalStatusOptions())->default('Pending'),
                    Forms\Components\Textarea::make('root_cause')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_action')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('crushing_plant_id'))
            ->columns([
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant'),
                Tables\Columns\TextColumn::make('reason')->badge(),
                Tables\Columns\TextColumn::make('affected_equipment')
                    ->label('Affected Equipment')
                    ->state(fn (ProductionDowntime $record): string => $record->affectedEquipmentLabel() ?? '—'),
                Tables\Columns\TextColumn::make('start_time')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration')->numeric()->suffix(' min'),
                Tables\Columns\TextColumn::make('approval_status')->badge()->placeholder('—'),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options(self::getReasonOptions()),
                Tables\Filters\SelectFilter::make('approval_status')->options(self::getApprovalStatusOptions()),
                Tables\Filters\SelectFilter::make('crushing_plant_id')->label('Plant')->relationship('crushingPlant', 'name'),
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
            'index' => Pages\ListPlantDowntimes::route('/'),
            'create' => Pages\CreatePlantDowntime::route('/create'),
            'edit' => Pages\EditPlantDowntime::route('/{record}/edit'),
        ];
    }
}
