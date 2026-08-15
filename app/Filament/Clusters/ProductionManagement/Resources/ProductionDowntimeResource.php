<?php

namespace App\Filament\Clusters\ProductionManagement\Resources;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource\Pages;
use App\Models\ProductionDowntime;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductionDowntimeResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = ProductionManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $model = ProductionDowntime::class;

    protected static ?string $navigationIcon = 'heroicon-o-pause-circle';

    protected static ?string $navigationLabel = 'Downtime';

    protected static ?int $navigationSort = 10;

    public static function getReasonOptions(): array
    {
        return array_combine(ProductionDowntime::REASONS, ProductionDowntime::REASONS);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Downtime Details')->schema([
                    Forms\Components\Select::make('production_log_id')->label('Production Record')
                        ->relationship('productionLog', 'production_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->production_number ?? ('Record #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('crushing_plant_id')->label('Crushing Plant')->relationship('crushingPlant', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('reason')->options(self::getReasonOptions())->required(),
                    Forms\Components\DateTimePicker::make('start_time')->required()->default(now()),
                    Forms\Components\DateTimePicker::make('end_time'),
                    Forms\Components\TextInput::make('duration_minutes')->label('Duration')->numeric()->suffix('min')->disabled()->dehydrated(false)->placeholder('Computed from start/end'),
                    Forms\Components\Select::make('reported_by_id')->label('Reported By')->relationship('reportedBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Textarea::make('root_cause')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_action')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reason')->badge(),
                Tables\Columns\TextColumn::make('crushingPlant.name')->label('Plant')->placeholder('—'),
                Tables\Columns\TextColumn::make('productionLog.production_number')->label('Production Record')->placeholder('—'),
                Tables\Columns\TextColumn::make('start_time')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration')->numeric()->suffix(' min'),
                Tables\Columns\TextColumn::make('reportedBy.full_name')->label('Reported By')->placeholder('—'),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options(self::getReasonOptions()),
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
            'index' => Pages\ListProductionDowntimes::route('/'),
            'create' => Pages\CreateProductionDowntime::route('/create'),
            'edit' => Pages\EditProductionDowntime::route('/{record}/edit'),
        ];
    }
}
