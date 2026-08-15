<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource\Pages;
use App\Models\QualityHold;
use App\Models\Stockpile;
use App\Services\Quarry\QualityHoldService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Quality Holds" — held inventory awaiting release, scoped to status =
 * Active. Held stockpiles are unavailable for dispatch (real enforcement
 * via DispatchOrder::hasQualityHold(), not just a cosmetic badge). Creating
 * a hold here routes through QualityHoldService::place() — same audited
 * entry point StockpileQualityResource's own hold action now uses.
 */
class QualityHoldResource extends Resource
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

    protected static ?string $model = QualityHold::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Quality Holds';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'quality-holds';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'Active');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Place Quality Hold')->schema([
                    Forms\Components\Select::make('stockpile_id')->label('Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('lab_sample_id')->label('Related Sample')
                        ->relationship('sample', 'sample_number')->searchable()->preload(),
                    Forms\Components\Select::make('reason')->options(array_combine(QualityHold::REASONS, QualityHold::REASONS))->required(),
                    Forms\Components\Select::make('held_by_id')->label('Held By')
                        ->relationship('heldBy', 'full_name')->searchable()->preload(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('held_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('hold_number')->label('Hold #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('reason')->badge(),
                Tables\Columns\TextColumn::make('heldBy.full_name')->label('Held By')->placeholder('—'),
                Tables\Columns\TextColumn::make('held_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options(array_combine(QualityHold::REASONS, QualityHold::REASONS)),
            ])
            ->actions([
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('release_reason')
                            ->label('Release Reason')
                            ->options(array_combine(QualityHold::RELEASE_REASONS, QualityHold::RELEASE_REASONS))
                            ->required(),
                        Forms\Components\Select::make('released_by_id')->label('Released By')
                            ->relationship('releasedBy', 'full_name')->searchable()->preload(),
                        Forms\Components\Textarea::make('notes')->label('Notes'),
                    ])
                    ->action(function (QualityHold $record, array $data) {
                        app(QualityHoldService::class)->release($record, $data['release_reason'], $data['released_by_id'] ?? null, $data['notes'] ?? null);
                        Notification::make()->title("Hold released — stock is available again")->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['hold_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityHolds::route('/'),
            'create' => Pages\CreateQualityHold::route('/create'),
            'view' => Pages\ViewQualityHold::route('/{record}'),
        ];
    }
}
