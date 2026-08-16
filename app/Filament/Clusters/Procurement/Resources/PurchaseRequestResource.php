<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * "Purchase Requisitions" in the sidebar — the same underlying PurchaseRequest
 * model/table already used by Materials Management's low-stock auto-PR flow,
 * extended here with multi-line items and enterprise routing fields. Only the
 * user-facing label changes; the class/table stay PurchaseRequest so the
 * existing Materials Management integration keeps working untouched.
 */
class PurchaseRequestResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $slug = 'purchase-requisitions';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Purchase Requisitions';

    protected static ?int $navigationSort = 2;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Converted to PO' => 'info',
            'Cancelled' => 'gray',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['requester', 'department']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Requisition')->schema([
                    Forms\Components\TextInput::make('pr_number')->label('Requisition Number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('requester_id')->label('Requester')->relationship('requester', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('business_unit_id')->label('Business Unit')->relationship('businessUnit', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('extraction_site_id')->label('Quarry / Site')->relationship('extractionSite', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('workshop_id')->relationship('workshop', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('warehouse_id')->relationship('warehouse', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('cost_centre_id')->label('Cost Center')->relationship('costCentre', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('supplier_id')->label('Supplier (Optional)')->relationship('supplier', 'company_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                    Forms\Components\TextInput::make('budget_code'),
                    Forms\Components\DatePicker::make('required_delivery_date'),
                    Forms\Components\Select::make('priority')->options([
                        'Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent',
                    ])->default('Medium')->required(),
                    Forms\Components\Select::make('status')->options([
                        'Pending' => 'Draft/Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                        'Cancelled' => 'Cancelled', 'Converted to PO' => 'Converted to PO',
                    ])->default('Pending')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Reason & Expected Cost')->schema([
                    Forms\Components\Textarea::make('reason')->columnSpanFull(),
                    Forms\Components\TextInput::make('expected_cost')->numeric()->prefix(fn ($get) => $get('currency') ?: 'KES'),
                ])->columns(2),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit')->placeholder('e.g. pcs, kg, m³'),
                            Forms\Components\TextInput::make('estimated_price')->numeric()->prefix(session('currency', 'KES')),
                            Forms\Components\Textarea::make('specifications')->columnSpanFull(),
                        ])->columns(6)->addActionLabel('Add Item')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pr_number')->label('Requisition #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('requester.full_name')->label('Requester')->placeholder('—'),
                Tables\Columns\TextColumn::make('department.name')->label('Department')->placeholder('—'),
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items'),
                Tables\Columns\TextColumn::make('expected_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state) => match ($state) {
                    'Urgent' => 'danger', 'High' => 'warning', default => 'gray',
                }),
                Tables\Columns\TextColumn::make('source')->badge()->color(fn (string $state) => $state === 'Auto' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                    'Cancelled' => 'Cancelled', 'Converted to PO' => 'Converted to PO',
                ]),
                Tables\Filters\SelectFilter::make('priority')->options(['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High', 'Urgent' => 'Urgent']),
                Tables\Filters\SelectFilter::make('department_id')->label('Department')->relationship('department', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseRequest $record) => $record->status === 'Pending' && ! $record->isApprovalPending())
                    ->action(function (PurchaseRequest $record) {
                        $record->submitForApproval('purchase-request-approval', [
                            'title' => 'Purchase Requisition — ' . $record->pr_number,
                            'metadata' => ['amount' => (float) $record->totalEstimatedCost(), 'staff_id' => $record->requester_id],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (PurchaseRequest $record) => $record->currentApproval() !== null)
                    ->url(fn (PurchaseRequest $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
