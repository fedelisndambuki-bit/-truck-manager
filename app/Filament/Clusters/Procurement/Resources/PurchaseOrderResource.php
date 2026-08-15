<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Traits\HasModuleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $slug = 'purchase-orders';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?int $navigationSort = 6;

    public static function getStatusOptions(): array
    {
        return ['Draft' => 'Draft', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success', 'Rejected' => 'danger', 'Pending' => 'warning', default => 'gray',
        };
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Purchase Order')->schema([
                TextEntry::make('po_number')->label('PO #'),
                TextEntry::make('supplier.company_name')->label('Supplier'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                TextEntry::make('total_amount')->money(fn ($record) => $record->currency ?: 'KES'),
                TextEntry::make('expected_delivery')->date()->placeholder('—'),
                TextEntry::make('payment_terms')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    protected static function streamPurchaseOrderPdf(PurchaseOrder $record)
    {
        $record->load(['supplier', 'items.material']);

        $pdf = Pdf::loadView('reports.purchase-order', [
            'title' => 'Purchase Order — ' . $record->po_number,
            'date' => now()->format('d M Y'),
            'order' => $record,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'purchase-order-' . $record->po_number . '.pdf');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier & Terms')->schema([
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('purchase_request_id')->label('From Requisition')->relationship('purchaseRequest', 'pr_number')->searchable()->preload(),
                    Forms\Components\Select::make('blanket_purchase_order_id')->label('Blanket PO (Release Against)')->relationship('blanketPurchaseOrder', 'bpo_number')->searchable()->preload(),
                    Forms\Components\Select::make('business_unit_id')->relationship('businessUnit', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('cost_centre_id')->label('Cost Center')->relationship('costCentre', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('project_id')->relationship('project', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('payment_terms')->placeholder('e.g. Net 30'),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                ])->columns(3),

                Forms\Components\Section::make('Line Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit_price')->numeric()->required()->prefix(session('currency', 'KES')),
                        ])->columns(6)->columnSpanFull()->defaultItems(1)->addActionLabel('Add Line Item'),
                ]),

                Forms\Components\Section::make('Delivery & Tax')->schema([
                    Forms\Components\Textarea::make('delivery_address')->columnSpanFull(),
                    Forms\Components\DatePicker::make('expected_delivery'),
                    Forms\Components\TextInput::make('tax_amount')->numeric()->default(0)->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('discount_amount')->numeric()->default(0)->prefix(session('currency', 'KES')),
                ])->columns(2),

                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Draft')->required(),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')->label('PO #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('Line Items'),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('expected_delivery')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'company_name'),
            ])
            ->actions([
                Tables\Actions\Action::make('submitForApproval')
                    ->label('Submit for Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (PurchaseOrder $record) => $record->status === 'Draft' && ! $record->isApprovalPending())
                    ->action(function (PurchaseOrder $record) {
                        $record->update(['status' => 'Pending']);
                        $record->submitForApproval('purchase-order-approval', [
                            'title' => 'Purchase Order — ' . $record->po_number,
                            'metadata' => ['amount' => (float) $record->total_amount],
                        ]);
                        Notification::make()->title('Submitted to the Workflow & Approvals engine')->success()->send();
                    }),
                Tables\Actions\Action::make('viewApproval')
                    ->label('View Approval')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (PurchaseOrder $record) => $record->currentApproval() !== null)
                    ->url(fn (PurchaseOrder $record) => \App\Filament\Clusters\WorkflowApprovals\Resources\ApprovalRequestResource\Pages\ViewApprovalRequest::getUrl(['record' => $record->currentApproval()])),
                Tables\Actions\Action::make('printPo')
                    ->label('Print PO')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (PurchaseOrder $record) => static::streamPurchaseOrderPdf($record)),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
