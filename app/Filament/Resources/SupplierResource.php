<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
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

    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Suppliers';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'company_name';

    public static function profileSchema(): array
    {
        return [
            Forms\Components\TextInput::make('company_name')->required(),
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
        ];
    }

    public static function contractSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('contract_start_date'),
            Forms\Components\DatePicker::make('contract_end_date'),
            Forms\Components\Select::make('status')->options([
                'Active' => 'Active', 'Inactive' => 'Inactive', 'Blacklisted' => 'Blacklisted',
            ])->default('Active')->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Supplier')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Profile')->schema(self::profileSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Tax & Bank')->schema(self::financialSchema())->columns(2),
                        Forms\Components\Tabs\Tab::make('Performance')->schema(self::performanceSchema())->columns(3),
                        Forms\Components\Tabs\Tab::make('Contract')->schema(self::contractSchema())->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contact_person')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->placeholder('—'),
                Tables\Columns\TextColumn::make('performance_rating')->label('Rating')->suffix(' / 5')->placeholder('—'),
                Tables\Columns\TextColumn::make('materials_count')->counts('materials')->label('Preferred Materials'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success',
                    'Blacklisted' => 'danger',
                    default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Blacklisted' => 'Blacklisted']),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
            'view' => Pages\ViewSupplier::route('/{record}'),
        ];
    }
}
