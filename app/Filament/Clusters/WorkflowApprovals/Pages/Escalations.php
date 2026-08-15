<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest;
use App\Models\WorkflowEscalation;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Escalations extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Escalations';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Escalations';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.escalations';

    public function table(Table $table): Table
    {
        return $table
            ->query(WorkflowEscalation::query()->with(['escalatedFrom', 'escalatedTo', 'approvalStep.approvalRequest']))
            ->columns([
                Tables\Columns\TextColumn::make('approvalStep.approvalRequest.reference_number')->label('Reference')->searchable(),
                Tables\Columns\TextColumn::make('escalatedFrom.name')->label('From')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('escalatedTo.name')->label('To'),
                Tables\Columns\TextColumn::make('reason')->badge()->color(fn (string $state) => $state === 'auto_sla_breach' ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('rule_hours')->label('SLA (hrs)')->placeholder('—'),
                Tables\Columns\TextColumn::make('escalated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options([
                    'auto_sla_breach' => 'Automatic (SLA Breach)', 'manual' => 'Manual',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Request')
                    ->icon('heroicon-o-eye')
                    ->url(fn (WorkflowEscalation $record) => $record->approvalStep?->approvalRequest
                        ? ViewApprovalRequest::getUrl(['record' => $record->approvalStep->approvalRequest])
                        : null)
                    ->visible(fn (WorkflowEscalation $record) => $record->approvalStep?->approvalRequest !== null),
            ])
            ->defaultSort('escalated_at', 'desc')
            ->striped();
    }
}
