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
use Illuminate\Support\Facades\Auth;

class MyApprovals extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'My Approvals';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'My Approvals';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.my-approvals';

    public function table(Table $table): Table
    {
        $userId = Auth::id();

        return $table
            ->query(
                ApprovalRequest::query()
                    ->where(function ($q) use ($userId) {
                        $q->where('submitted_by_id', $userId)
                            ->orWhereHas('steps.actions', fn ($sub) => $sub->where('approver_id', $userId));
                    })
                    ->with('workflowDefinition')
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->limit(40),
                Tables\Columns\TextColumn::make('workflowDefinition.name')->label('Workflow')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Approved' => 'success', 'Rejected', 'Cancelled' => 'danger',
                    'Escalated' => 'warning', 'Returned', 'Suspended' => 'gray', default => 'info',
                }),
                Tables\Columns\IconColumn::make('submitted_by_id')->label('Mine')->boolean()
                    ->getStateUsing(fn (ApprovalRequest $record) => $record->submitted_by_id === $userId),
                Tables\Columns\TextColumn::make('submitted_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    'Returned' => 'Returned', 'Escalated' => 'Escalated', 'Cancelled' => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalRequest $record) => ViewApprovalRequest::getUrl(['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
