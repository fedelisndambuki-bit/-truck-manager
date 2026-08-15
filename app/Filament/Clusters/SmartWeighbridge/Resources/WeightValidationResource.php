<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\WeightValidationResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only weight-validation view — target vs actual vs variance vs
 * tolerance, color-coded, over any DispatchOrder that has been gross
 * weighed at least once.
 */
class WeightValidationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = SmartWeighbridge::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-vertical';

    protected static ?string $navigationLabel = 'Weight Validation';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'weight-validation';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getWeightStatusColor(string $state): string
    {
        return match ($state) {
            'Within Tolerance' => 'success',
            'Underweight' => 'warning',
            'Overweight' => 'warning',
            'Critical' => 'danger',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('gross_weight'))
            ->defaultSort('gross_weighed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('target_net_weight')->label('Target')->suffix(' t'),
                Tables\Columns\TextColumn::make('net_weight')->label('Actual')->suffix(' t'),
                Tables\Columns\TextColumn::make('variance')->label('Difference')->suffix(' t')
                    ->color(fn (DispatchOrder $record): string => abs((float) $record->variance) <= (float) $record->tolerance_tonnes ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('variance_percent')->label('Variance %')->suffix('%'),
                Tables\Columns\TextColumn::make('tolerance_tonnes')->label('Tolerance')->suffix(' t'),
                Tables\Columns\TextColumn::make('weight_status')->label('Status')->badge()
                    ->color(fn (?string $state): string => $state ? self::getWeightStatusColor($state) : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('weight_status')->label('Status')
                    ->options(array_combine(DispatchOrder::WEIGHT_STATUSES, DispatchOrder::WEIGHT_STATUSES)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (DispatchOrder $record) => VehicleCheckInResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->visible(fn (DispatchOrder $record) => $record->currentApproval() !== null)
                    ->url(fn (DispatchOrder $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeightValidation::route('/'),
        ];
    }
}
