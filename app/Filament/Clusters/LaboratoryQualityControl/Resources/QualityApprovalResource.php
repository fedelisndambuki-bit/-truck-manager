<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityApprovalResource\Pages;
use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest;
use App\Models\LabSample;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Quality Approvals" — 2nd Resource over LabSample, scoped to samples
 * currently moving through the 'lab-sample-quality-approval' multi-stage
 * workflow (Laboratory Review -> Quality Manager Approval -> Production
 * Approval -> Stock Release). Mirrors DispatchApprovalResource's exact
 * pattern — actioning the approval itself happens in the real Workflow &
 * Approvals cluster, this Resource surfaces status and links there.
 */
class QualityApprovalResource extends Resource
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

    protected static ?string $model = LabSample::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Quality Approvals';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'quality-approvals';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['material']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas(
                'approvalRequests',
                fn ($q) => $q->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled'])
            ))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sample_number')->label('Sample #')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('batch_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('sample_type')->badge(),
                Tables\Columns\TextColumn::make('approval_status')->label('Approval Status')->badge()
                    ->state(fn (LabSample $record) => $record->currentApproval()?->status ?? '—'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Sample')
                    ->icon('heroicon-o-eye')
                    ->url(fn (LabSample $record) => LabSampleResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->visible(fn (LabSample $record) => $record->currentApproval() !== null)
                    ->url(fn (LabSample $record) => ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualityApprovals::route('/'),
        ];
    }
}
