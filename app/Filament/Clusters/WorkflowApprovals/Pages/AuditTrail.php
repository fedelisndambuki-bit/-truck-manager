<?php

namespace App\Filament\Clusters\WorkflowApprovals\Pages;

use App\Filament\Clusters\WorkflowApprovals;
use App\Models\WorkflowAuditLog;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AuditTrail extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'workflow.view';
    }

    protected static ?string $cluster = WorkflowApprovals::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Audit Trail';

    protected static string $view = 'filament.clusters.workflow-approvals.pages.audit-trail';

    public function table(Table $table): Table
    {
        return $table
            ->query(WorkflowAuditLog::query()->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('action')->badge()->searchable(),
                Tables\Columns\TextColumn::make('auditable_type')->label('Entity')->formatStateUsing(fn (string $state) => class_basename($state)),
                Tables\Columns\TextColumn::make('auditable_id')->label('Ref #'),
                Tables\Columns\TextColumn::make('user.name')->label('User')->placeholder('System'),
                Tables\Columns\TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')->options(fn () => WorkflowAuditLog::query()->distinct()->pluck('action', 'action')->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
