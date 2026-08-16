<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource\Pages;
use App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource\RelationManagers;
use App\Models\BlanketPurchaseOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlanketPurchaseOrderResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = BlanketPurchaseOrder::class;

    protected static ?string $slug = 'blanket-purchase-orders';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Blanket Purchase Orders';

    protected static ?int $navigationSort = 7;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success', 'Suspended' => 'warning', 'Expired' => 'danger', 'Closed' => 'gray', default => 'gray',
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
                Forms\Components\Section::make('Blanket Order')->schema([
                    Forms\Components\TextInput::make('bpo_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('currency')->default('KES'),
                    Forms\Components\Select::make('status')->options(array_combine(BlanketPurchaseOrder::STATUSES, BlanketPurchaseOrder::STATUSES))->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Limits & Validity')->schema([
                    Forms\Components\TextInput::make('maximum_value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('maximum_quantity')->numeric(),
                    Forms\Components\DatePicker::make('valid_from'),
                    Forms\Components\DatePicker::make('valid_to'),
                ])->columns(4),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bpo_number')->label('BPO #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('maximum_value')->label('Max Value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('released_value')->label('Released')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('remaining')->label('Remaining')->state(fn (BlanketPurchaseOrder $record) => $record->remainingValue())->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('valid_to')->label('Valid Until')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(BlanketPurchaseOrder::STATUSES, BlanketPurchaseOrder::STATUSES)),
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
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReleaseOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlanketPurchaseOrders::route('/'),
            'create' => Pages\CreateBlanketPurchaseOrder::route('/create'),
            'edit' => Pages\EditBlanketPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
