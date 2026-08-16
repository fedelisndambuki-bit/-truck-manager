<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\PurchaseReturnResource\Pages;
use App\Models\PurchaseReturn;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseReturnResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = PurchaseReturn::class;

    protected static ?string $slug = 'returns';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Returns';

    protected static ?int $navigationSort = 11;

    public static function getReasonOptions(): array
    {
        return array_combine(PurchaseReturn::REASONS, PurchaseReturn::REASONS);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success', 'Rejected' => 'danger', 'Submitted' => 'warning', 'Closed' => 'gray', default => 'gray',
        };
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['supplier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Return')->schema([
                    Forms\Components\TextInput::make('return_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\Select::make('purchase_order_id')->label('Purchase Order')->relationship('purchaseOrder', 'po_number')->searchable()->preload(),
                    Forms\Components\Select::make('goods_received_note_id')->label('GRN')->relationship('goodsReceivedNote', 'grn_number')->searchable()->preload(),
                    Forms\Components\Select::make('reason')->options(self::getReasonOptions())->required(),
                    Forms\Components\Select::make('replacement_status')->options(array_combine(PurchaseReturn::REPLACEMENT_STATUSES, PurchaseReturn::REPLACEMENT_STATUSES))->default('Pending'),
                    Forms\Components\TextInput::make('credit_note_reference'),
                    Forms\Components\Select::make('status')->options(array_combine(PurchaseReturn::STATUSES, PurchaseReturn::STATUSES))->default('Draft')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photos')->image()->multiple()->disk('public')->directory('purchase-returns')->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Items')->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload()->columnSpan(2),
                            Forms\Components\TextInput::make('description')->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')->numeric()->required(),
                            Forms\Components\TextInput::make('unit_price')->numeric()->prefix(session('currency', 'KES')),
                        ])->columns(6)->addActionLabel('Add Item')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')->label('Return #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('reason')->badge(),
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items'),
                Tables\Columns\TextColumn::make('replacement_status')->badge()->color(fn (string $state) => match ($state) {
                    'Replaced', 'Credited' => 'success', 'Not Applicable' => 'gray', default => 'warning',
                }),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options(self::getReasonOptions()),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(PurchaseReturn::STATUSES, PurchaseReturn::STATUSES)),
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
            'index' => Pages\ListPurchaseReturns::route('/'),
            'create' => Pages\CreatePurchaseReturn::route('/create'),
            'edit' => Pages\EditPurchaseReturn::route('/{record}/edit'),
        ];
    }
}
