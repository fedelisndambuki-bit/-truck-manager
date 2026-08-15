<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\DispatchApprovalResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * 4th Resource over DispatchOrder — scoped to orders awaiting (or already
 * through) approval. Submission itself is automatic (runDecisionEngine()
 * calls submitForApproval() with no manual step, per spec) — this Resource
 * surfaces the real approval status plus the pre-exit checklist.
 */
class DispatchApprovalResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Dispatch Approval';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'dispatch-approval';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereIn('status', ['Pending Approval', 'Approved', 'Rejected']))
            ->defaultSort('gross_weighed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('weight_status')->label('Weight Status')->badge()
                    ->color(fn (?string $state): string => WeightValidationResource::getWeightStatusColor($state ?? '')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
                Tables\Columns\IconColumn::make('can_exit')
                    ->label('Ready to Exit')
                    ->state(fn (DispatchOrder $record) => $record->canExit())
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['Pending Approval' => 'Pending Approval', 'Approved' => 'Approved', 'Rejected' => 'Rejected']),
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
            'index' => Pages\ListDispatchApprovals::route('/'),
        ];
    }
}
