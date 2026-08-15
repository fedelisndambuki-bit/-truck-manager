<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance;
use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class GeneralLedger extends Page implements HasTable
{
    use HasModuleAccess;
    use InteractsWithTable;

    public static function getModuleSlug(): ?string
    {
        return 'finance.general_ledger';
    }

    protected static ?string $cluster = Finance::class;

    protected static ?string $slug = 'general-ledger';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'General Ledger';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'General Ledger';

    protected static string $view = 'filament.clusters.finance.pages.general-ledger';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />',
            'title' => 'General Ledger',
            'subtitle' => 'Every posted debit and credit, drillable by account, reference and date.',
        ]);
    }

    public function table(Table $table): Table
    {
        $currency = session('currency', 'KES');

        return $table
            ->query(LedgerEntry::query())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('account_code')->label('Account')
                    ->formatStateUsing(function ($state) {
                        $account = ChartOfAccount::resolveByCode(Auth::user()?->company_id, (int) $state);

                        return $account ? "{$state} — {$account->name}" : $state;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('reference_id')->toggleable(),
                Tables\Columns\TextColumn::make('description')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('debit')->money($currency, divideBy: 100)->sortable(),
                Tables\Columns\TextColumn::make('credit')->money($currency, divideBy: 100)->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('account_code')
                    ->label('Account')
                    ->options(fn () => ChartOfAccount::withoutGlobalScope('company')
                        ->orderBy('account_code')
                        ->get()
                        ->mapWithKeys(fn (ChartOfAccount $a) => [$a->account_code => "{$a->account_code} — {$a->name}"])),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->options(fn () => LedgerEntry::query()->distinct()->pluck('reference_type', 'reference_type')->all()),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}
