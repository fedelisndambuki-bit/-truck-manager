<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\SupplierQuotationResource\Pages;
use App\Models\SupplierQuotation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierQuotationResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = SupplierQuotation::class;

    protected static ?string $slug = 'supplier-quotations';

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static ?string $navigationLabel = 'Supplier Quotations';

    protected static ?int $navigationSort = 4;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Submitted' => 'gray', 'Under Review' => 'warning', 'Accepted' => 'success', 'Rejected' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quotation')->schema([
                    Forms\Components\TextInput::make('quotation_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('request_for_quotation_id')->label('RFQ')->relationship('requestForQuotation', 'rfq_number')->searchable()->preload()->required()->live(),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                    Forms\Components\TextInput::make('lead_time_days')->numeric()->suffix('days'),
                    Forms\Components\TextInput::make('delivery_terms'),
                    Forms\Components\TextInput::make('payment_terms'),
                    Forms\Components\TextInput::make('warranty'),
                    Forms\Components\DatePicker::make('validity_date'),
                    Forms\Components\Select::make('status')->options(array_combine(SupplierQuotation::STATUSES, SupplierQuotation::STATUSES))->default('Submitted')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('rfq_item_id')->label('RFQ Item')
                                ->options(fn (Forms\Get $get) => \App\Models\RfqItem::where('request_for_quotation_id', $get('../../request_for_quotation_id'))->pluck('description', 'id'))
                                ->searchable()->columnSpan(2),
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit_price')->numeric()->required()->prefix(session('currency', 'KES')),
                            Forms\Components\TextInput::make('discount_percent')->numeric()->suffix('%')->default(0),
                            Forms\Components\TextInput::make('tax_percent')->numeric()->suffix('%')->default(0),
                        ])->columns(6)->addActionLabel('Add Item')->columnSpanFull(),
                ]),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')->label('Quote #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('requestForQuotation.rfq_number')->label('RFQ')->searchable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('total_amount')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('lead_time_days')->suffix(' days')->placeholder('—'),
                Tables\Columns\TextColumn::make('validity_date')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(SupplierQuotation::STATUSES, SupplierQuotation::STATUSES)),
                Tables\Filters\SelectFilter::make('request_for_quotation_id')->label('RFQ')->relationship('requestForQuotation', 'rfq_number'),
                Tables\Filters\SelectFilter::make('supplier_id')->label('Supplier')->relationship('supplier', 'company_name'),
            ])
            ->actions([
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
            'index' => Pages\ListSupplierQuotations::route('/'),
            'create' => Pages\CreateSupplierQuotation::route('/create'),
            'edit' => Pages\EditSupplierQuotation::route('/{record}/edit'),
        ];
    }
}
