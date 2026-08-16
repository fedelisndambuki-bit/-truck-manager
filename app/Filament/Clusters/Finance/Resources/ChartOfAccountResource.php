<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance;
use App\Filament\Clusters\Finance\Resources\ChartOfAccountResource\Pages;
use App\Models\ChartOfAccount;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChartOfAccountResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'finance.chart_of_accounts';
    }

    protected static ?string $model = ChartOfAccount::class;

    protected static ?string $cluster = Finance::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTypeOptions(): array
    {
        return array_combine(
            $types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense', 'CostOfSales'],
            $types
        );
    }

    public static function form(Form $form): Form
    {
        $currency = session('currency', 'KES');

        return $form
            ->schema([
                Forms\Components\Section::make('Account Details')->schema([
                    Forms\Components\TextInput::make('account_code')
                        ->numeric()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('type')
                        ->options(self::getTypeOptions())->required(),
                    Forms\Components\Select::make('normal_balance')
                        ->options(['Debit' => 'Debit', 'Credit' => 'Credit'])->required(),
                    Forms\Components\Select::make('parent_account_id')
                        ->label('Parent Account')
                        ->relationship('parent', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('status')
                        ->options(['Active' => 'Active', 'Inactive' => 'Inactive'])
                        ->default('Active')->required(),
                ])->columns(2),
                Forms\Components\Section::make('Dimensions')->schema([
                    Forms\Components\Select::make('cost_centre_id')
                        ->label('Cost Centre')
                        ->relationship('costCentre', 'name')
                        ->searchable()->preload(),
                    Forms\Components\Select::make('profit_centre_id')
                        ->label('Profit Centre')
                        ->relationship('profitCentre', 'name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('currency')->default($currency)->maxLength(3),
                    Forms\Components\TextInput::make('tax_code'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account_code')->label('Code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('normal_balance')->toggleable(),
                Tables\Columns\TextColumn::make('costCentre.name')->label('Cost Centre')->toggleable(),
                Tables\Columns\TextColumn::make('profitCentre.name')->label('Profit Centre')->toggleable(),
                Tables\Columns\TextColumn::make('currency')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_system')->label('System')->boolean()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn (ChartOfAccount $record) => ! $record->is_system),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('account_code')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }

    /**
     * ChartOfAccount::resolveByCode() — what every real posting actually
     * uses — falls back to the shared is_system=true rows (company_id
     * NULL) whenever a company has no override for that code. The
     * model's own MultiTenantScope hides those NULL rows entirely, so
     * without this override the panel shows "No chart of accounts" even
     * though the company's own transactions are posting against them.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScope('company')->with(['costCentre', 'profitCentre']);
        $user = auth()->user();

        if ($user && ! $user->hasRole('Super Admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('company_id', $user->company_id)->orWhereNull('company_id');
            });
        }

        return $query;
    }
}
