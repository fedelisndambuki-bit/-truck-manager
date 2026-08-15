<?php

namespace App\Filament\Clusters\WorkflowApprovals\Resources;

use App\Filament\Clusters\WorkflowApprovals;
use App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages;
use App\Models\ApprovalRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalRequestResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $model = ApprovalRequest::class;

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Approval Requests';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery()->with(['workflowDefinition', 'submittedBy']))
            ->columns(static::sharedColumns())
            ->filters(static::sharedFilters())
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ApprovalRequest $record) => Pages\ViewApprovalRequest::getUrl(['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function sharedColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('reference_number')->searchable()->sortable()->weight('semibold'),
            Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('workflowDefinition.module')->label('Module')->badge()->color('info'),
            Tables\Columns\TextColumn::make('workflowDefinition.name')->label('Workflow')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                'Approved' => 'success', 'Rejected', 'Cancelled' => 'danger',
                'Escalated' => 'warning', 'Returned', 'Suspended' => 'gray', default => 'info',
            }),
            Tables\Columns\TextColumn::make('submittedBy.name')->label('Submitted By')->toggleable(),
            Tables\Columns\TextColumn::make('submitted_at')->dateTime()->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('sla_due_at')->label('SLA Due')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function sharedFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')->options([
                'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                'Returned' => 'Returned', 'Cancelled' => 'Cancelled', 'Escalated' => 'Escalated', 'Suspended' => 'Suspended',
            ]),
            Tables\Filters\SelectFilter::make('workflow_definition_id')
                ->label('Workflow')
                ->relationship('workflowDefinition', 'name'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApprovalRequests::route('/'),
            'view' => Pages\ViewApprovalRequest::route('/{record}'),
        ];
    }
}
