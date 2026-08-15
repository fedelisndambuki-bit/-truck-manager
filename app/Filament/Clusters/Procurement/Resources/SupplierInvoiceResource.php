<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\SupplierInvoiceResource\Pages;
use App\Models\SupplierInvoice;
use App\Services\ThreeWayMatchService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierInvoiceResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = SupplierInvoice::class;

    protected static ?string $slug = 'supplier-invoices';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Supplier Invoices';

    protected static ?int $navigationSort = 12;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved', 'Paid' => 'success', 'Disputed' => 'danger', default => 'gray',
        };
    }

    public static function getMatchingColor(string $state): string
    {
        return match ($state) {
            'Matched' => 'success', 'Discrepancy' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice')->schema([
                    Forms\Components\TextInput::make('invoice_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('supplier_invoice_reference')->label("Supplier's Invoice #"),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('purchase_order_id')->label('Purchase Order')->relationship('purchaseOrder', 'po_number')->searchable()->preload(),
                    Forms\Components\Select::make('goods_received_note_id')->label('GRN')->relationship('goodsReceivedNote', 'grn_number')->searchable()->preload(),
                    Forms\Components\DatePicker::make('invoice_date')->default(now())->required(),
                    Forms\Components\DatePicker::make('due_date'),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                    Forms\Components\TextInput::make('tax_amount')->numeric()->default(0)->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('status')->options(array_combine(SupplierInvoice::STATUSES, SupplierInvoice::STATUSES))->default('Pending')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit_price')->numeric()->required()->prefix(session('currency', 'KES')),
                        ])->columns(6)->addActionLabel('Add Item')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Invoice #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('purchaseOrder.po_number')->label('PO #')->placeholder('—'),
                Tables\Columns\TextColumn::make('total_amount')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('due_date')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('matching_status')->label('Match')->badge()->color(fn (string $state): string => self::getMatchingColor($state)),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('matching_status')->options(array_combine(SupplierInvoice::MATCHING_STATUSES, SupplierInvoice::MATCHING_STATUSES)),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(SupplierInvoice::STATUSES, SupplierInvoice::STATUSES)),
                Tables\Filters\SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'company_name'),
            ])
            ->actions([
                Tables\Actions\Action::make('runMatch')
                    ->label('Run 3-Way Match')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->action(function (SupplierInvoice $record) {
                        $discrepancies = app(ThreeWayMatchService::class)->match($record);

                        if (empty($discrepancies)) {
                            Notification::make()->title('Matched — PO, GRN and Invoice agree')->success()->send();
                        } else {
                            Notification::make()->title('Discrepancies found')->body(implode(' ', $discrepancies))->danger()->persistent()->send();
                        }
                    }),
                Tables\Actions\Action::make('approveAndPost')
                    ->label('Approve & Post')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This posts the invoice to Accounts Payable in the Finance ledger.')
                    ->visible(fn (SupplierInvoice $record) => $record->matching_status === 'Matched' && $record->status === 'Pending')
                    ->action(function (SupplierInvoice $record) {
                        app(ThreeWayMatchService::class)->postApproved($record);
                        Notification::make()->title('Invoice approved and posted to Accounts Payable')->success()->send();
                    }),
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
            'index' => Pages\ListSupplierInvoices::route('/'),
            'create' => Pages\CreateSupplierInvoice::route('/create'),
            'edit' => Pages\EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
