<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest;
use App\Models\ApprovalAction;
use App\Services\WorkflowEngine;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PendingApprovals extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.approve';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Pending Approvals';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Pending Approvals';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.pending-approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApprovalAction::query()
                    ->where('approver_id', Auth::id())
                    ->whereNull('action')
                    ->whereHas('approvalStep', fn ($q) => $q->where('status', 'Pending'))
                    ->with(['approvalStep.approvalRequest.workflowDefinition'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('approvalStep.approvalRequest.reference_number')->label('Reference')->searchable(),
                Tables\Columns\TextColumn::make('approvalStep.approvalRequest.title')->label('Title')->limit(40),
                Tables\Columns\TextColumn::make('approvalStep.approvalRequest.workflowDefinition.module')->label('Module')->badge()->color('info'),
                Tables\Columns\TextColumn::make('assigned_at')->dateTime()->sortable()->label('Assigned'),
                Tables\Columns\TextColumn::make('approvalStep.due_at')->label('Due')->dateTime()
                    ->color(fn ($record) => $record->approvalStep?->due_at?->isPast() ? 'danger' : null),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalAction $record) => ViewApprovalRequest::getUrl(['record' => $record->approvalStep->approvalRequest])),
                Tables\Actions\Action::make('quickApprove')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (ApprovalAction $record) => $this->quickDecision($record, 'approve')),
                Tables\Actions\Action::make('quickReject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ApprovalAction $record) => $this->quickDecision($record, 'reject')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $this->quickDecision($record, 'approve', silent: true);
                        }
                        Notification::make()->title(count($records) . ' request(s) approved')->success()->send();
                    }),
            ])
            ->defaultSort('assigned_at', 'asc')
            ->striped();
    }

    protected function quickDecision(ApprovalAction $action, string $decision, bool $silent = false): void
    {
        $engine = app(WorkflowEngine::class);

        if ($decision === 'approve') {
            $engine->approve($action);
        } else {
            $engine->reject($action);
        }

        if (! $silent) {
            Notification::make()->title($decision === 'approve' ? 'Approved' : 'Rejected')->success()->send();
        }
    }
}
