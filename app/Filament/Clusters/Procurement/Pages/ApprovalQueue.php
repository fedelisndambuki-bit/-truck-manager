<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\ApprovalRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchasePlan;
use App\Models\PurchaseRequest;
use App\Models\SupplierContract;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Live queue of every in-flight Workflow & Approvals request raised by a
 * Procurement document (Requisitions, Purchase Orders, Blanket POs,
 * Contracts, Purchase Plans) — a read/navigate view; the actual approve/
 * reject action happens on the Workflow & Approvals cluster's own
 * ApprovalRequestResource, linked to from here.
 */
class ApprovalQueue extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $slug = 'approval-queue';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Approval Queue';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Approval Queue';

    protected static string $view = 'filament.clusters.procurement.pages.approval-queue';

    protected function procurementApprovableTypes(): array
    {
        return [
            PurchaseRequest::class,
            PurchaseOrder::class,
            \App\Models\BlanketPurchaseOrder::class,
            SupplierContract::class,
            PurchasePlan::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />',
            'title' => 'Approval Queue',
            'subtitle' => 'Every in-flight approval raised across Purchase Planning, Requisitions, Orders, Blanket POs and Contracts.',
        ]);
    }

    public function table(Table $table): Table
    {
        $companyId = Auth::user()?->company_id;

        return $table
            ->query(
                ApprovalRequest::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereIn('approvable_type', $this->procurementApprovableTypes())
                    ->whereNotIn('status', ['Approved', 'Rejected', 'Cancelled'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')->label('Ref #')->searchable(),
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('approvable_type')->label('Document Type')->formatStateUsing(fn (string $state) => class_basename($state))->badge(),
                Tables\Columns\TextColumn::make('submittedBy.name')->label('Submitted By')->placeholder('—'),
                Tables\Columns\TextColumn::make('submitted_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Pending' => 'warning', 'In Progress' => 'info', default => 'gray',
                }),
            ])
            ->defaultSort('submitted_at')
            ->filters([
                Tables\Filters\SelectFilter::make('approvable_type')->label('Document Type')
                    ->options(collect($this->procurementApprovableTypes())->mapWithKeys(fn ($class) => [$class => class_basename($class)])->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ApprovalRequest $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record])),
            ])
            ->striped();
    }
}
