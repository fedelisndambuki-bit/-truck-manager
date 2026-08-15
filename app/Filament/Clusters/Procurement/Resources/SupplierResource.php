<?php

namespace App\Filament\Clusters\Procurement\Resources;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Resources\SupplierResource\Pages;
use App\Filament\Clusters\Procurement\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = Procurement::class;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $model = Supplier::class;

    protected static ?string $slug = 'suppliers';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Supplier Management';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function getCategoryOptions(): array
    {
        return array_combine(Supplier::CATEGORIES, Supplier::CATEGORIES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success', 'Blacklisted' => 'danger', default => 'gray',
        };
    }

    public static function getRiskColor(string $state): string
    {
        return match ($state) {
            'Low' => 'success', 'Medium' => 'warning', 'High' => 'danger', default => 'gray',
        };
    }

    public static function profileSchema(): array
    {
        return [
            Forms\Components\TextInput::make('supplier_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
            Forms\Components\TextInput::make('company_name')->required(),
            Forms\Components\TextInput::make('legal_name'),
            Forms\Components\TextInput::make('trading_name'),
            Forms\Components\Select::make('category')->options(self::getCategoryOptions()),
            Forms\Components\TextInput::make('contact_person'),
            Forms\Components\TextInput::make('phone')->tel(),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Textarea::make('address')->columnSpanFull(),
        ];
    }

    public static function financialSchema(): array
    {
        return [
            Forms\Components\TextInput::make('tax_number'),
            Forms\Components\TextInput::make('registration_number'),
            Forms\Components\TextInput::make('credit_limit')->numeric()->prefix(session('currency', 'KES')),
            Forms\Components\TextInput::make('payment_terms'),
            Forms\Components\TextInput::make('bank_name'),
            Forms\Components\TextInput::make('bank_account_number'),
            Forms\Components\TextInput::make('bank_branch'),
        ];
    }

    public static function performanceSchema(): array
    {
        return [
            Forms\Components\TextInput::make('performance_rating')->numeric()->step(0.1)->minValue(0)->maxValue(5)->suffix('/ 5'),
            Forms\Components\TextInput::make('delivery_performance_rating')->numeric()->step(0.1)->minValue(0)->maxValue(5)->suffix('/ 5'),
            Forms\Components\TextInput::make('quality_rating')->numeric()->step(0.1)->minValue(0)->maxValue(5)->suffix('/ 5'),
            Forms\Components\Select::make('risk_rating')->options(array_combine(Supplier::RISK_RATINGS, Supplier::RISK_RATINGS))->default('Low'),
        ];
    }

    public static function complianceSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('contract_start_date'),
            Forms\Components\DatePicker::make('contract_end_date'),
            Forms\Components\DatePicker::make('insurance_expiry'),
            Forms\Components\TagsInput::make('certificates')->placeholder('Add certificate'),
            Forms\Components\TagsInput::make('licenses')->placeholder('Add license'),
            Forms\Components\Select::make('status')->options([
                'Active' => 'Active', 'Inactive' => 'Inactive', 'Blacklisted' => 'Blacklisted',
            ])->default('Active')->required(),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Profile')->schema([
                TextEntry::make('supplier_number')->label('Supplier #'),
                TextEntry::make('company_name'),
                TextEntry::make('category')->badge()->placeholder('—'),
                TextEntry::make('contact_person')->placeholder('—'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('email')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])->columns(4),

            InfolistSection::make('Performance & Risk')->schema([
                TextEntry::make('performance_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('delivery_performance_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('quality_rating')->suffix(' / 5')->placeholder('—'),
                TextEntry::make('risk_rating')->badge()->color(fn (string $state): string => self::getRiskColor($state)),
            ])->columns(4),

            InfolistSection::make('Financial & Compliance')->schema([
                TextEntry::make('credit_limit')->money(session('currency', 'KES'))->placeholder('—'),
                TextEntry::make('payment_terms')->placeholder('—'),
                TextEntry::make('contract_start_date')->date()->placeholder('—'),
                TextEntry::make('contract_end_date')->date()->placeholder('—'),
                TextEntry::make('insurance_expiry')->date()->placeholder('—'),
            ])->columns(4),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Supplier')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Profile')->schema(self::profileSchema())->columns(3),
                        Forms\Components\Tabs\Tab::make('Tax & Bank')->schema(self::financialSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Performance')->schema(self::performanceSchema())->columns(4),
                        Forms\Components\Tabs\Tab::make('Compliance')->schema(self::complianceSchema())->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_number')->label('Supplier #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('performance_rating')->label('Rating')->suffix(' / 5')->placeholder('—'),
                Tables\Columns\TextColumn::make('risk_rating')->badge()->color(fn (string $state): string => self::getRiskColor($state)),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Blacklisted' => 'Blacklisted']),
                Tables\Filters\SelectFilter::make('category')->options(self::getCategoryOptions()),
                Tables\Filters\SelectFilter::make('risk_rating')->options(array_combine(Supplier::RISK_RATINGS, Supplier::RISK_RATINGS)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\BankAccountsRelationManager::class,
            RelationManagers\PerformanceReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
