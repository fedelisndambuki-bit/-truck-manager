<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Traits\HasModuleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Purchase Orders';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 8;

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Pending' => 'warning',
            default => 'gray',
        };
    }

    public static function supplierTermsSchema(): array
    {
        return [
            Forms\Components\Select::make('supplier_id')
                ->label('Supplier')
                ->relationship('supplier', 'company_name')
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('purchase_request_id')
                ->label('From Purchase Request')
                ->relationship('purchaseRequest', 'pr_number')
                ->searchable()->preload(),
            Forms\Components\TextInput::make('payment_terms')->placeholder('e.g. Net 30'),
            Forms\Components\Select::make('currency')->options([
                'KES' => 'KES', 'USD' => 'USD', 'EUR' => 'EUR',
            ])->default('KES')->required(),
        ];
    }

    public static function itemsSchema(): array
    {
        return [
            Forms\Components\Repeater::make('items')
                ->relationship('items')
                ->schema([
                    Forms\Components\Select::make('material_id')
                        ->label('Material')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required()
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('quantity')->numeric()->required(),
                    Forms\Components\TextInput::make('unit_price')->numeric()->required()->prefix(session('currency', 'KES')),
                ])
                ->columns(4)
                ->columnSpanFull()
                ->defaultItems(1)
                ->addActionLabel('Add Line Item'),
        ];
    }

    public static function deliveryTaxSchema(): array
    {
        return [
            Forms\Components\Textarea::make('delivery_address')->columnSpanFull(),
            Forms\Components\DatePicker::make('expected_delivery'),
            Forms\Components\TextInput::make('tax_amount')->numeric()->default(0)->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('discount_amount')->numeric()->default(0)->prefix(session('currency', 'KES')),
        ];
    }

    public static function approvalSchema(): array
    {
        return [
            Forms\Components\Select::make('approval_status')->options([
                'Draft' => 'Draft', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
            ])->default('Draft')->required(),
            Forms\Components\Textarea::make('notes')->columnSpanFull(),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Overview')->schema([
                TextEntry::make('approval_status')->label('Status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                TextEntry::make('subtotal')->money(fn (PurchaseOrder $record): string => $record->currency),
                TextEntry::make('tax_amount')->label('Tax')->money(fn (PurchaseOrder $record): string => $record->currency),
                TextEntry::make('discount_amount')->label('Discount')->money(fn (PurchaseOrder $record): string => $record->currency),
                TextEntry::make('total_amount')->label('Total')->money(fn (PurchaseOrder $record): string => $record->currency),
            ])->columns(3),
            InfolistSection::make('Line Items')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->schema([
                    TextEntry::make('material.name')->label('Material'),
                    TextEntry::make('quantity')->numeric(2),
                    TextEntry::make('unit_price')->numeric(2),
                    TextEntry::make('total')->numeric(2),
                ])->columns(4),
            ]),
            InfolistSection::make('Delivery')->schema([
                TextEntry::make('delivery_address')->label('Address')->placeholder('—'),
                TextEntry::make('expected_delivery')->date()->placeholder('—'),
                TextEntry::make('payment_terms')->placeholder('—'),
            ])->columns(3),
            InfolistSection::make('Supplier')->schema([
                TextEntry::make('supplier.company_name')->label('Company'),
                TextEntry::make('supplier.contact_person')->label('Contact')->placeholder('—'),
                TextEntry::make('supplier.phone')->label('Phone')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Supplier & Terms')->schema(self::supplierTermsSchema())->columns(2),
                Forms\Components\Section::make('Line Items')->schema(self::itemsSchema()),
                Forms\Components\Section::make('Delivery & Tax')->schema(self::deliveryTaxSchema())->columns(2),
                Forms\Components\Section::make('Approval')->schema(self::approvalSchema()),
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
                Tables\Columns\TextColumn::make('currency')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('expected_delivery')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('approval_status')->label('Status')->badge()->color(fn (string $state) => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('approval_status')->options([
                    'Draft' => 'Draft', 'Pending' => 'Pending', 'Approved' => 'Approved', 'Rejected' => 'Rejected',
                ]),
                Tables\Filters\SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'company_name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (PurchaseOrder $record) => $record->update(['approval_status' => 'Approved']))
                    ->visible(fn (PurchaseOrder $record) => $record->approval_status === 'Pending'
                        && (Auth::user()?->hasRole('Super Admin') || Auth::user()?->can('materials.approve'))),
                Tables\Actions\ViewAction::make()
                    ->modalIcon('heroicon-o-eye')
                    ->modalIconColor('gray'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('printPo')
                    ->label('Print PO')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (PurchaseOrder $record) {
                        $record = $record->load(['supplier', 'items.material']);

                        $pdf = Pdf::loadView('reports.purchase-order', [
                            'title' => 'Purchase Order — ' . $record->po_number,
                            'date' => now()->format('d M Y'),
                            'order' => $record,
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'purchase-order-' . $record->po_number . '.pdf');
                    }),
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
