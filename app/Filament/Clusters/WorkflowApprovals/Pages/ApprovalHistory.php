<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest;
use App\Models\ApprovalRequest;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ApprovalHistory extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Approval History';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Approval History';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.approval-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApprovalRequest::query()
                    ->whereIn('status', ['Approved', 'Rejected', 'Cancelled'])
                    ->with(['workflowDefinition', 'submittedBy'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->limit(40),
                Tables\Columns\TextColumn::make('workflowDefinition.name')->label('Workflow')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success', default => 'danger',
                }),
                Tables\Columns\TextColumn::make('submittedBy.name')->label('Submitted By')->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')->dateTime()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('completed_at')->dateTime()->sortable()->label('Completed'),
                Tables\Columns\TextColumn::make('duration')->label('Duration')
                    ->getStateUsing(fn (ApprovalRequest $record) => $record->submitted_at && $record->completed_at
                        ? $record->submitted_at->diffForHumans($record->completed_at, true)
                        : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Approved' => 'Approved', 'Rejected' => 'Rejected', 'Cancelled' => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('workflow_definition_id')->label('Workflow')->relationship('workflowDefinition', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalRequest $record) => ViewApprovalRequest::getUrl(['record' => $record])),
            ])
            ->defaultSort('completed_at', 'desc')
            ->striped();
    }
}
