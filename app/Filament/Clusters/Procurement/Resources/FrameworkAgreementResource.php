<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\FrameworkAgreementResource\Pages;
use App\Filament\Clusters\Procurement\Resources\SupplierContractResource\RelationManagers;
use App\Models\SupplierContract;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Long-term negotiated-price supplier agreements — the same supplier_contracts
 * table as "Contracts", scoped to contract_type = Framework Agreement, so it
 * gets its own sidebar entry without a redundant table (matching the EAM
 * Corrective-Maintenance-vs-Work-Orders precedent).
 */
class FrameworkAgreementResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = SupplierContract::class;

    protected static ?string $slug = 'framework-agreements';

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Framework Agreements';

    protected static ?int $navigationSort = 9;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('contract_type', 'Framework Agreement')->with(['supplier']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Framework Agreement')->schema([
                    Forms\Components\Hidden::make('contract_type')->default('Framework Agreement'),
                    Forms\Components\TextInput::make('contract_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('supplier_id')->relationship('supplier', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('contract_value')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('status')->options(SupplierContractResource::getStatusOptions())->default('Draft')->required(),
                    Forms\Components\DatePicker::make('effective_date'),
                    Forms\Components\DatePicker::make('expiry_date'),
                    Forms\Components\DatePicker::make('renewal_date'),
                ])->columns(3),

                Forms\Components\KeyValue::make('service_levels')->keyLabel('Item')->valueLabel('Level')->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')->label('Agreement #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.company_name')->label('Supplier')->searchable(),
                Tables\Columns\TextColumn::make('contract_value')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('items_count')->label('Negotiated Items')->counts('items'),
                Tables\Columns\TextColumn::make('expiry_date')->date()->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => SupplierContractResource::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(SupplierContractResource::getStatusOptions()),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrameworkAgreements::route('/'),
            'create' => Pages\CreateFrameworkAgreement::route('/create'),
            'edit' => Pages\EditFrameworkAgreement::route('/{record}/edit'),
        ];
    }
}
