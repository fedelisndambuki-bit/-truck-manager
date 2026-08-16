<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockpileQualityResource\Pages;
use App\Models\PlantQualityTest;
use App\Models\QualityHold;
use App\Models\Stockpile;
use App\Services\Quarry\QualityHoldService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * A quality-focused view over the same Stockpile table — each stockpile's
 * current quality_status gates whether it should be considered available for
 * dispatch, cross-referencing the real PlantQualityTest detail records for
 * that material rather than duplicating particle/moisture/density data.
 */
class StockpileQualityResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = StockpileManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'stockpile.view';
    }

    protected static ?string $model = Stockpile::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Quality Status';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'quality-status';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['material']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stockpile_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->searchable(),
                Tables\Columns\TextColumn::make('quantity_available')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('quality_status')->badge()
                    ->color(fn (string $state): string => StockpileResource::getQualityStatusColor($state)),
                Tables\Columns\TextColumn::make('latest_test')
                    ->label('Latest Lab Test')
                    ->state(function (Stockpile $record) {
                        $test = PlantQualityTest::query()
                            ->where('material_id', $record->material_id)
                            ->latest('created_at')
                            ->first();

                        return $test ? $test->sample_number . ' — ' . ($test->pass_fail ? 'Pass' : 'Fail') : 'No test on record';
                    }),
                Tables\Columns\IconColumn::make('available_for_dispatch')
                    ->label('Dispatch Ready')
                    ->state(fn (Stockpile $record) => $record->quality_status === 'Approved')
                    ->boolean(),
            ])
            ->groups([
                Tables\Grouping\Group::make('quality_status')->label('Quality Status'),
            ])
            ->defaultGroup('quality_status')
            ->filters([
                Tables\Filters\SelectFilter::make('quality_status')->options(array_combine(Stockpile::QUALITY_STATUSES, Stockpile::QUALITY_STATUSES)),
                Tables\Filters\SelectFilter::make('material_id')->label('Product')->relationship('material', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('updateQualityStatus')
                    ->label('Update Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('quality_status')
                            ->label('Quality Status')
                            ->options(array_combine(Stockpile::QUALITY_STATUSES, Stockpile::QUALITY_STATUSES))
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('hold_reason')
                            ->label('Hold Reason')
                            ->options(array_combine(QualityHold::REASONS, QualityHold::REASONS))
                            ->visible(fn (Forms\Get $get) => $get('quality_status') === 'On Hold')
                            ->required(fn (Forms\Get $get) => $get('quality_status') === 'On Hold'),
                        Forms\Components\Select::make('release_reason')
                            ->label('Release Reason')
                            ->options(array_combine(QualityHold::RELEASE_REASONS, QualityHold::RELEASE_REASONS))
                            ->visible(fn (Forms\Get $get, Stockpile $record) => $record->quality_status === 'On Hold' && $get('quality_status') !== 'On Hold')
                            ->required(fn (Forms\Get $get, Stockpile $record) => $record->quality_status === 'On Hold' && $get('quality_status') !== 'On Hold'),
                    ])
                    ->fillForm(fn (Stockpile $record) => ['quality_status' => $record->quality_status])
                    ->action(function (Stockpile $record, array $data) {
                        $service = app(QualityHoldService::class);

                        // Route both directions through QualityHoldService so
                        // the Stockpile cluster's own UI can't silently bypass
                        // the Laboratory & Quality Control module's audit
                        // trail (a real Active QualityHold + StockMovement row
                        // for every hold/release, not just a bare status flip).
                        if ($data['quality_status'] === 'On Hold' && $record->quality_status !== 'On Hold') {
                            $service->place($record, $data['hold_reason']);
                        } elseif ($record->quality_status === 'On Hold' && $data['quality_status'] !== 'On Hold') {
                            $activeHold = QualityHold::where('stockpile_id', $record->id)->where('status', 'Active')->latest('id')->first();

                            if ($activeHold) {
                                $service->release($activeHold, $data['release_reason']);
                            }

                            $record->update(['quality_status' => $data['quality_status']]);
                        } else {
                            $record->update(['quality_status' => $data['quality_status']]);
                        }

                        Notification::make()->title('Quality status updated')->success()->send();
                    }),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockpileQuality::route('/'),
        ];
    }
}
