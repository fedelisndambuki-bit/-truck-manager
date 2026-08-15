<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityReleaseResource\Pages;
use App\Models\QualityHold;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Quality Releases" — 2nd Resource over the same QualityHold table
 * QualityHoldResource manages, scoped to status = Released. A read-only
 * historical register of every hold that's already been resolved.
 */
class QualityReleaseResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-lock-open';

    protected static ?string $navigationLabel = 'Quality Releases';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'quality-releases';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', 'Released');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('released_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('hold_number')->label('Hold #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('reason')->label('Hold Reason')->badge(),
                Tables\Columns\TextColumn::make('release_reason')->label('Release Reason')->badge()->color('success'),
                Tables\Columns\TextColumn::make('releasedBy.full_name')->label('Released By')->placeholder('—'),
                Tables\Columns\TextColumn::make('held_at')->label('Held At')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('released_at')->label('Released At')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('release_reason')->options(array_combine(QualityHold::RELEASE_REASONS, QualityHold::RELEASE_REASONS)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityReleases::route('/'),
            'view' => Pages\ViewQualityRelease::route('/{record}'),
        ];
    }
}
